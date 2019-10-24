<?php
	global $CONFIG;
	global $DATABASE;
	if(isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$db=$DATABASE[$_REQUEST['db']];
	}
	switch(strtolower($_REQUEST['func'])){
		case 'monitor':
			$sql=sqlpromptBuildQuery($_REQUEST['db'],$_REQUEST['type']);
			//$recs=getDBRecords($sql);
			//echo printValue(array_keys($recs[0]));exit;
			setView('monitor_sql',1);
			return;
		break;
		case 'setdb':
			//echo printValue($db);exit;
			$tables=dbGetTables($db['name']);
			setView('tables_fields',1);
			return;
			switch(strtolower($db['dbtype'])){
				case 'postgresql':
				case 'postgres':
					loadExtras('postgresql');
					$tables=postgresqlGetDBTables();
					//echo printValue($tables);
				break;
				case 'oracle':
					loadExtras('oracle');
					$tables=oracleGetDBTables();
				break;
				case 'mssql':
					loadExtras('mssql');
					$tables=mssqlGetDBTables();
				break;
				case 'hana':
					loadExtras('hana');
					$tables=hanaGetDBTables();
				break;
				case 'sqlite':
					loadExtras('sqlite');
					$tables=sqliteGetDBTables();
				break;
				default:
					$tables=getDBTables();
				break;
			}
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
			$recs=dbGetRecords($db['name'],$_SESSION['sql_last']);
			setView('results',1);
			return;
		break;
		case 'export':
			//echo printValue($db).$_SESSION['sql_last'];exit;
			$recs=dbGetRecords($db['name'],$_SESSION['sql_last']);
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=dbGetTableFields($db['name'],$table);
			setView('fields',1);
			return;
		break;
		default:
			$tabs=array();
			foreach($DATABASE as $db){
				if(!isset($db['displayname'])){
					$db['displayname']=ucwords(str_replace('_',' ',$db['name']));
				}
				$tabs[]=$db;
			}
			//echo printValue($CONFIG).printValue($tabs);exit;
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
