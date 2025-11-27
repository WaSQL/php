<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	
	switch(strtolower($_REQUEST['func'])){
		case 'add_single':
			setView('add_single',1);
			return;
		break;
		case 'add_single_process':
			$errors=array();
			$tablename=str_replace('/[^a-z0-9]/is','_',$_REQUEST['tablename']);
			if(isDBTable($tablename)){
				$errors[]="{$tablename} already exists";
			}
			else{
				$ok=createDBTableFromText($tablename,$_REQUEST['tablefields']);
				if(!isNum($ok)){$errors[]=$ok;}
			}
			setView('tables_list',1);
			if(count($errors)){
				$message=implode('<br />',$errors);
			}
			else{
				$message='Success';
			}
			setView('message',1);
			return;
		break;
		case 'add_multiple':
			setView('add_multiple',1);
			return;
		break;
		case 'add_multiple_process':
			$recs=databaseAddMultipleTables($_REQUEST['tablefields']);
    		$message=databaseListRecords(array(
    			'-list'=>$recs,
    			'-hidesearch'=>1,
    			'-tableclass'=>'wacss_table striped bordered'
    		));
			setView('message',1);
			return;
		break;
		case 'add_prebuilt':
			$recs=tablesGetPreBuilt();
			setView('add_prebuilt',1);
			return;
		break;
		case 'add_prebuilt_process':
			if(isDBTable($_REQUEST['tablename'])){
            	dropDBTable($_REQUEST['tablename'],1);
            }
            $ok=createWasqlTables($_REQUEST['tablename']);
            $message=printValue($ok);
            setView('message',1);
			return;
		break;
		case 'tables_list':
			setView('tables_list',1);
			return;
		break;
		case 'change_charset':
			$title='Change Collation';
			if(!is_array($_REQUEST['select']) || !count($_REQUEST['select'])){
				$message='<h3 class="w_red">Error: Nothing selected to convert</h3>';
				setView('message_centerpop',1);
				return;
			}
			if(!strlen($_REQUEST['charset'])){
				$message='<h3 class="w_red">Error: Select a collation to convert to</h3>';
				setView('message_centerpop',1);
				return;
			}
			
			$charset=$_REQUEST['charset'];

			$messages=array();
			foreach($_REQUEST['select'] as $table){
				$runsql='ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.$charset;
				$cmessage = $runsql.' ...'.PHP_EOL;
				$ck=executeSQL($runsql);
				if(isset($ck['result'])){
					if($ck['result'] != 1){$cmessage .= "FAILED: {$ck['query']}";}
					else{$cmessage .= 'SUCCESS';}
					}
				elseif($ck !=1){$cmessage .= "FAILED: {$ck}";}
				else{$cmessage .= 'SUCCESS';}
				$messages[]=$cmessage;
			}
			$message=implode('<br />'.PHP_EOL,$messages);
			setView('message_centerpop',1);
			return;
		break;
		default:
			setView('default',1);
		break;
	}
	setView('default',1);
?>
