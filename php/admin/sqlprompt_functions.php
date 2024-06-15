<?php
loadExtras('translate');
function sqlpromptHTMLHead($title=''){
	$cssfile=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].minifyCssFile('wacss,bulma');
	$jsfile=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].minifyJsFile('wacss,bulma');
	return <<<ENDOFHTML
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>{$title}</title>
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link type="text/css" rel="stylesheet" href="{$cssfile}" />
  	<script type="text/javascript" src="{$jsfile}"></script>
</head>
<body>
	<div class="container">
ENDOFHTML;
}
function sqlpromptGetTables($dbname=''){
	global $CONFIG;
	global $DATABASE;
	if(strlen($dbname)){
		if(isset($_REQUEST['schema']) && strlen($_REQUEST['schema'])){
			$DATABASE[$dbname]['dbschema']=$_REQUEST['schema'];
		}
		$tables=dbGetTables($dbname);
	}
	else{
		$tables=getDBTables();
	}	
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
	$CONFIG['sqlprompt_tables_hide_wasql']=1;
	if(isset($CONFIG['sqlprompt_tables_hide_wasql'])){
		foreach($tables as $i=>$table){
			if(stringBeginsWith($table,'_')){
				unset($tables[$i]);
			}
		}
	}
	return $tables;
}
function sqlpromtFormatSQL($query){
	//format is messing up fields like updated_date and lastupdated so lets skip it for not
	return $query;
	$query=strtolower($query);
	$query=str_replace("\"",'',$query);
	$query=preg_replace('/\,/',','.PHP_EOL,$query);
	$query=preg_replace('/\,[\r\n]{0,2}([0-9])/is',',\1',$query);
	$start_keywords = array(
		'create table','create or replace table','create column table','create or replace column table','create view','create or replace view',
		"select", "from", "where", "order by", "group by", "insert into", "update"
	);
	$middle_keywords = array(
		'as','on','not','null','primary key',"delete","merge",
		"select", "from", "where", "order by", "group by", "insert into", "update"
	);
	$newlinewords=array(
		'from','order by','group by'
	);
	foreach ($keywords as $keyword) {
		if (preg_match("/($keyword *)/is", $query, $matches)) {
			$newval=strtoupper($matches[1]);
			if(in_array($keyword,$newlinewords)){$newval.=PHP_EOL;}
	  		$query = str_replace($matches[1], $newval, $query);
		}
	}
	$lines=preg_split('/[\r\n]/',$query);
	foreach($lines as $i=>$line){
		$line=trim($line);
		if(!strlen($line)){unset($lines[$i]);continue;}
		if(stringEndsWith($line,',')){
			$lines[$i]="\t{$line}";
		}
	}
	$query=implode(PHP_EOL,$lines);
	return trim($query);
}
function sqlpromptShowlist($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table bordered striped responsive',
		'-hidesearch'=>1,
		'-sorting'=>1
	);
	if(is_array($listopts) && count($listopts)){
		foreach($listopts as $k=>$v){
			$opts[$k]=$v;
		}
	}
	//unset($opts['-list']);echo printValue($opts);exit;
	return databaseListRecords($opts);
}
function sqlpromptListRecords($table,$listopts=array()){
	$opts=array(
		'-table'=>$table,
		'-tableclass'=>'table bordered striped responsive',
		'-sorting'=>1,
		'-export'=>1,
		'-onsubmit'=>"return pagingSubmit(this,'sqlprompt_results');",
		'_menu'=>'sqlprompt',
		'setprocessing'=>0,
		'func'=>'list_records',
		'db'=>$_SESSION['db']['name'],
		'table'=>$table
	);
	if(is_array($listopts) && count($listopts)){
		foreach($listopts as $k=>$v){
			$opts[$k]=$v;
		}
	}
	//return printValue($_SESSION['db']).printValue($opts);
	return dbListRecords($_SESSION['db']['name'],$opts);
}
function sqlpromptCaptureFirstRows($rec,$max=30){
	global $recs;
	global $sqlpromptCaptureFirstRows_count;
	$sqlpromptCaptureFirstRows_count+=1;
	if(count($recs) < $max){
		$recs[]=$rec;
	}
	return;
}
function sqlpromptListResults($recs){
	global $db;
	if(!is_array($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No Results','',1);
	}
	if(!count($recs)){
		return translateText('No Results','',1);
	}
	$opts=array(
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed responsive'
	);
	$sumfields=array();
	foreach($recs[0] as $k=>$v){
		if(preg_match('/\_(count|size)$/i',$k)){
			$opts["{$k}_class"]='align-right';
			$sumfields[]=$k;
		}
	}
	if(count($sumfields)){
		$opts['-sumfields']=implode(',',$sumfields);
	}
	if(isset($db['dbtype'])){
		switch(strtolower($db['dbtype'])){
			case 'postgres':
				if(isset($_SESSION['sql_last']) && stringContains($_SESSION['sql_last'],'pg_stat_activity')){
					foreach($recs as $i=>$rec){
						if(!isset($rec['pid'])){continue;}
						$recs[$i]['pid']='<div style="display:flex;"><a href="#" data-query="SELECT pg_cancel_backend('.$rec['pid'].')" data-div="nulldiv" data-nav="/php/admin.php" data-_menu="sqlprompt" data-func="toast_query" data-db="'.$db['name'].'" data-confirm="Kill this query?" onclick="return wacss.nav(this);"><span class="icon-erase w_danger w_small" style="margin-right:5px;"></span></a><div>'.$rec['pid'].'</div></div>';
					}
				}
			break;
			case 'mysql':
			case 'mysqli':
				if(isset($_SESSION['sql_last']) && stringContains($_SESSION['sql_last'],'processlist')){
					foreach($recs as $i=>$rec){
						if(!isset($rec['id'])){continue;}
						$recs[$i]['id']='<div style="display:flex;"><a href="#" data-query="KILL '.$rec['id'].'" data-div="nulldiv" data-nav="/php/admin.php" data-_menu="sqlprompt" data-func="toast_query" data-db="'.$db['name'].'" data-confirm="Kill this query?" onclick="return wacss.nav(this);"><span class="icon-erase w_danger w_small" style="margin-right:5px;"></span></a><div>'.$rec['id'].'</div></div>';
					}
				}
			break;
			case 'hana':
				if(isset($_SESSION['sql_last']) && stringContains($_SESSION['sql_last'],'m_connections')){
					foreach($recs as $i=>$rec){
						if(!isset($rec['connection_id'])){continue;}
						$recs[$i]['connection_id']='<div style="display:flex;"><a href="#" data-query="ALTER SYSTEM CANCEL SESSION \''.$rec['connection_id'].'\'" data-div="nulldiv" data-nav="/php/admin.php" data-_menu="sqlprompt" data-func="toast_query" data-db="'.$db['name'].'" data-confirm="Kill this query?" onclick="return wacss.nav(this);"><span class="icon-erase w_danger w_small" style="margin-right:5px;"></span></a><div>'.$rec['connection_id'].'</div></div>';
					}
				}
			break;
			case 'oracle':
				if(isset($_SESSION['sql_last']) && stringContains($_SESSION['sql_last'],'v$session')){
					foreach($recs as $i=>$rec){
						if(!isset($rec['sid'])){continue;}
						$recs[$i]['sid']='<div style="display:flex;"><a href="#" data-query="ALTER SYSTEM KILL SESSION \''.$rec['sid'].'\'" data-div="nulldiv" data-nav="/php/admin.php" data-_menu="sqlprompt" data-func="toast_query" data-db="'.$db['name'].'" data-confirm="Kill this query?" onclick="return wacss.nav(this);"><span class="icon-erase w_danger w_small" style="margin-right:5px;"></span></a><div>'.$rec['sid'].'</div></div>';
					}
				}
			break;
			case 'snowflake':
				if(isset($_SESSION['sql_last']) && stringContains($_SESSION['sql_last'],'query_history')){
					foreach($recs as $i=>$rec){
						if(!isset($rec['query_id'])){continue;}
						$recs[$i]['query_id']='<div style="display:flex;"><a href="#" data-query="SELECT SYSTEM$CANCEL_QUERY(\''.$rec['query_id'].'\')" data-div="nulldiv" data-nav="/php/admin.php" data-_menu="sqlprompt" data-func="toast_query" data-db="'.$db['name'].'" data-confirm="Kill this query?" onclick="return wacss.nav(this);"><span class="icon-erase w_danger w_small" style="margin-right:5px;"></span></a><div>'.$rec['query_id'].'</div></div>';
					}
				}
			break;
		}
	}
	$opts['-list']=$recs;
	return databaseListRecords($opts);
}
function sqlpromptListFields($recs){
	if(!is_array($recs) || !count($recs)){
		return translateText('No fields defined','',1);
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped condensed responsive',
		'-listfields'=>'_dbfield,_dbtype_ex',
		'-thclass'=>'w_smallest',
		'-tdclass'=>'w_smallest',
		'_dbfield_options'=>array(
			'displayname'=>'Field',
			'style'=>'max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;',
			'title'=>'%_dbfield%'
		),
		'_dbtype_ex_displayname'=>'Type'
	);
	return databaseListRecords($opts);
}
function sqlpromptListIndexes($recs){
	if(!is_array($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No indexes defined','',1);
	}
	if(!count($recs)){
		return translateText('No indexes defined','',1);
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped condensed responsive',
		'-listfields'=>'key_name,column_name,is_primary,is_unique,seq_in_index',
		'-thclass'=>'w_smallest',
		'-tdclass'=>'w_smallest',
		'is_primary_displayname'=>'Pk',
		'is_primary_checkmark'=>1,
		'key_name_options'=>array(
			'displayname'=>'Index',
			'style'=>'max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;',
			'title'=>'%key_name%'
		),
		'is_unique_displayname'=>'Un',
		'is_unique_checkmark'=>1,
		'column_name_displayname'=>'Column',
		'seq_in_index_displayname'=>'Seq',
		'index_type_displayname'=>'Type'
	);
	return databaseListRecords($opts);
}
function sqlpromptBuildQuery($db,$name,$str=''){
	global $DATABASE;
	//echo printValue($DATABASE[$db]);exit;
	switch(strtolower($DATABASE[$db]['dbtype'])){
		case 'mssql':
			loadExtras('mssql');
			global $dbh_mssql;
			$dbh_mssql='';
			return trim(mssqlNamedQuery($name,$str));
		break;
		case 'postgresql':
		case 'postgres':
		case 'postgre';
			loadExtras('postgresql');
			global $dbh_postgresql;
			$dbh_postgresql='';
			return trim(postgresqlNamedQuery($name,$str));
		break;
		case 'oracle':
			loadExtras('oracle');
			global $dbh_oracle;
			$dbh_oracle='';
			return trim(oracleNamedQuery($name,$str));
		break;
		case 'hana':
			loadExtras('hana');
			global $dbh_hana;
			$dbh_hana='';
			return trim(hanaNamedQuery($name,$str));
		break;
		case 'sqlite':
			loadExtras('sqlite');
			global $dbh_sqlite;
			$dbh_sqlite='';
			return trim(sqliteNamedQuery($name,$str));
		break;
		case 'snowflake':
			loadExtras('snowflake');
			global $dbh_snowflake;
			$dbh_snowflake='';
			return trim(snowflakeNamedQuery($name,$str));
		break;
		case 'firebird':
			loadExtras('firebird');
			global $dbh_firebird;
			$dbh_firebird='';
			return trim(firebirdNamedQuery($name,$str));
		break;
		case 'ctree':
			loadExtras('ctree');
			global $dbh_ctree;
			$dbh_ctree='';
			return trim(ctreeNamedQuery($name,$str));
		break;
		default:
			loadExtras('mysql');
			global $dbh_mysql;
			$dbh_mysql='';
			return trim(mysqlNamedQuery($name,$str));
		break;
	}
}
function sqlpromptNamedQueries(){
	global $DATABASE;
	$db=$_REQUEST['db'];
	$recs=array();
	switch(strtolower($DATABASE[$db]['dbtype'])){
		case 'mssql':
			loadExtras('mssql');
			global $dbh_mssql;
			$dbh_mssql='';
			$recs=mssqlNamedQueryList();
		break;
		case 'postgresql':
		case 'postgres':
		case 'postgre';
			loadExtras('postgresql');
			global $dbh_postgresql;
			$dbh_postgresql='';
			$recs=postgresqlNamedQueryList();
		break;
		case 'oracle':
			loadExtras('oracle');
			global $dbh_oracle;
			$dbh_oracle='';
			$recs=oracleNamedQueryList();
		break;
		case 'hana':
			loadExtras('hana');
			global $dbh_hana;
			$dbh_hana='';
			$recs=hanaNamedQueryList();
		break;
		case 'sqlite':
			loadExtras('sqlite');
			global $dbh_sqlite;
			$dbh_sqlite='';
			$recs=sqliteNamedQueryList();
		break;
		case 'gigya':
			loadExtras('gigya');
			$recs=gigyaNamedQueryList();
		break;
		case 'ccv2':
			loadExtras('ccv2');
			$recs=ccv2NamedQueryList();
		break;
		case 'snowflake':
			loadExtras('snowflake');
			global $dbh_snowflake;
			$dbh_snowflake='';
			$recs=snowflakeNamedQueryList();
		break;
		case 'firebird':
			loadExtras('firebird');
			global $dbh_firebird;
			$dbh_firebird='';
			$recs=firebirdNamedQueryList();
		break;
		case 'ctree':
			loadExtras('ctree');
			global $dbh_ctree;
			$dbh_ctree='';
			$recs=ctreeNamedQueryList();
		break;
		case 'msaccess':
			loadExtras('msaccess');
			global $dbh_msaccess;
			$dbh_msaccess='';
			$recs=msaccessNamedQueryList();
		break;
		case 'mscsv':
			loadExtras('mscsv');
			global $dbh_mscsv;
			$dbh_mscsv='';
			$recs=mscsvNamedQueryList();
		break;
		case 'msexcel':
			loadExtras('msexcel');
			global $dbh_msexcel;
			$dbh_msexcel='';
			$recs=msexcelNamedQueryList();
		break;
		default:
			loadExtras('mysql');
			global $dbh_mysql;
			$dbh_mysql='';
			$recs=mysqlNamedQueryList();
		break;
	}
	return $recs;
}
function sqlpromptMonitorTools(){
	$recs=sqlpromptNamedQueries();

	if(!count($recs)){return '';}
	return databaseListRecords(array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-listview'=>getView('sqlprompt_named_query'),
		'-pretable'=>'<div class="w_bold w_big" style="border-top:2px solid #f1f1f1;padding-top:10px;"><wtranslate>Admin Tools</wtranslate></div><ul style="margin-top:0px;" class="nav-list '.configValue('admin_color').'"  >',
		'-posttable'=>'</ul>'
	));
}
function sqlpromptBuildTop5Ctree($db,$table){
	$fields=dbGetTableFields($db['name'],$table);
	if(!is_array($fields) || !count($fields)){
		return "select top 5 * from admin.{$table}";
	}
	$lines=[];
	foreach($fields as $field=>$info){
		if($info['_dbtype']=='tinyint'){
			$lines[]="\tcast({$field} as int) as {$field}";
		}
		elseif($info['_dbtype']=='character' && $info['_dblength'] > 255){
			$lines[]="\ttrim({$field}) as {$field}";
		}
		else{
			$lines[]="\t{$field}";
		}
	}
	$sql="SELECT TOP 5".PHP_EOL;
	$sql.=implode(','.PHP_EOL,$lines).PHP_EOL;
	$sql.="FROM admin.{$table}";
	return $sql;
}
?>
