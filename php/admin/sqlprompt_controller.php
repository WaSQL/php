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
			$_SESSION['db_last']=$_REQUEST['db'];
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					//echo nl2br($_SESSION['sql_last']);exit;
					$recs=postgresqlGetDBRecords($_SESSION['sql_last']);
					if(!is_array($recs)){
						$recs=array(array('result'=>$ok));
					}
					//echo nl2br($_SESSION['sql_last']).printValue($recs);exit;
				break;
				case 'oracle':
					loadExtras('oracle');
					$recs=oracleGetDBRecords($_SESSION['sql_last']);
					//check for an oracle ref cursor
					if(count($recs)==1 && count(array_keys($recs[0])==1)){
						foreach($recs[0] as $k=>$v){
							if(is_array($v)){$recs=$v;}
							break;
						}
					}
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'mssql':
					loadExtras('mssql');
					$recs=mssqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'hana':
					loadExtras('hana');
					$recs=hanaGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'sqlite':
					loadExtras('sqlite');
					$sql=preg_replace('/[\r\n]+/',' ',$_SESSION['sql_last']);
					//echo $sql;exit;
					$recs=sqliteGetDBRecords($sql);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				default:
					$recs=getDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
			}
			setView('results',1);
			return;
		break;
		case 'export':
			switch(strtolower($_SESSION['db_last'])){
				case 'postgresql':
					loadExtras('postgresql');
					$recs=postgresqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'oracle':
					loadExtras('oracle');
					$recs=oracleGetDBRecords($_SESSION['sql_last']);
					//check for an oracle ref cursor
					if(count($recs)==1 && count(array_keys($recs[0])==1)){
						foreach($recs[0] as $k=>$v){
							if(is_array($v)){$recs=$v;}
							break;
						}
					}
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'mssql':
					loadExtras('mssql');
					$recs=mssqlGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'hana':
					loadExtras('hana');
					$recs=hanaGetDBRecords($_SESSION['sql_last']);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'sqlite':
					loadExtras('sqlite');
					$sql=preg_replace('/[\r\n]+/',' ',$_SESSION['sql_last']);
					//echo $sql;exit;
					$recs=sqliteGetDBRecords($sql);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				default:
					$recs=getDBRecords($_SESSION['sql_last']);
				break;
			}
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			//echo printValue($_REQUEST);exit;
			switch(strtolower($_REQUEST['db'])){
				case 'postgresql':
					loadExtras('postgresql');
					$fields=postgresqlGetDBFieldInfo($table);
					//echo printValue($fields);exit;
				break;
				case 'oracle':
					loadExtras('oracle');
					$fields=oracleGetDBFieldInfo($table);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'mssql':
					loadExtras('mssql');
					$fields=mssqlGetDBFieldInfo($table);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'hana':
					loadExtras('hana');
					$fields=hanaGetDBFieldInfo($table);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				case 'sqlite':
					loadExtras('sqlite');
					$fields=sqliteGetDBFieldInfo($table);
					//echo $_SESSION['sql_last'].printValue($recs);exit;
				break;
				default:
					$fields=getDBFieldInfo($table);
				break;
			}
			
			
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
			$dbs=array('postgresql','oracle','mssql','hana','sqlite');
			foreach($dbs as $db){
				if(isset($CONFIG["{$db}_dbname"]) || isset($CONFIG["{$db}_dbhost"])){
					$tabname=isset($CONFIG["{$db}_tabname"])?$CONFIG["{$db}_tabname"]:$db;
					$tabs[]=array('name'=>$db,'host'=>$CONFIG["{$db}_dbhost"],'tabname'=>$tabname);
				}
			}
			//echo printValue($CONFIG).printValue($tabs);exit;
			$tables=getDBTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
