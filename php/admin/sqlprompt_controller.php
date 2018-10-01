<?php
	global $CONFIG;
	switch(strtolower($_REQUEST['func'])){
		case 'setdb':
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					$tables=postgresqlGetDBTables();
				break;
				case 'oracle':
					loadExtras('postgresql');
					$tables=oracleGetDBTables();
				break;
				case 'mssql':
					loadExtras('postgresql');
					$tables=mssqlGetDBTables();
				break;
				case 'hana':
					loadExtras('postgresql');
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
			$view='results';
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
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					$recs=postgresqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'oracle':
					loadExtras('oracle');
					$recs=oracleGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'mssql':
					loadExtras('mssql');
					$recs=mssqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'hana':
					loadExtras('hana');
					$recs=mssqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'sqlite':
					loadExtras('sqlite');
					$recs=sqliteGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				default:
					$recs=getDBRecords($_SESSION['sql_last']);
				break;
			}
			setView($view,1);
			return;
		break;
		case 'export':
			$recs=getDBRecords($_SESSION['sql_last']);
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=getDBFieldInfo($table);
			//echo printValue($fields);exit;
			setView('fields',1);
			return;
		break;
		case 'export':
			$id=addslashes($_REQUEST['id']);
			$report=getDBRecord(array('-table'=>'_reports','active'=>1,'_id'=>$id));
			$report=reportsRunReport($report);
			$csv=arrays2CSV($report['recs']);
			pushData($csv,'csv',$report['name'].'.csv');
			exit;
			return;
		break;
		default:
			$tabs=array();
			if(isset($CONFIG['postgresql_dbname']) || isset($CONFIG['postgresql_dbhost'])){$tabs[]=array('name'=>'postgresql');}
			if(isset($CONFIG['oracle_dbname']) || isset($CONFIG['oracle_dbhost'])){$tabs[]=array('name'=>'oracle');}
			if(isset($CONFIG['mssql_dbname']) || isset($CONFIG['mssql_dbhost'])){$tabs[]=array('name'=>'mssql');}
			if(isset($CONFIG['hana_dbname']) || isset($CONFIG['hana_dbhost'])){$tabs[]=array('name'=>'hana');}
			if(isset($CONFIG['sqlite_dbname'])){$tabs[]=array('name'=>'sqlite');}
			//echo printValue($CONFIG).printValue($tabs);exit;
			$tables=getDBTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
