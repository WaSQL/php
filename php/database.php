<?php
$progpath=dirname(__FILE__);
error_reporting(E_ALL & ~E_NOTICE);
include_once("$progpath/config.php");
//include_once("$progpath/wasql.php");
//create a global variable for storing queries that have already happened
global $databaseCache;
if(!is_array($databaseCache)){$databaseCache=array();}
/* Connect to the database for this host */
global $dbh;
global $CONFIG;
try{
	$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
}
catch(Exception $e){
	$dbh=false;
}
if(!$dbh){
	$error=databaseError();
	if(isPostgreSQL()){$error .= "<br>PostgreSQL does not allow CREATE DATABASE inside a transaction block. Create the database first.";}
	$msg = '<div>'."\n";
	$msg .= '	<div class="w_bigger w_red"><img src="/wfiles/iconsets/32/abort.png" border="0" style="vertical-align:middle;"> Failed to connect to the <b>'.$CONFIG['dbtype'].'</b> service on <b>'.$CONFIG['dbhost'].'</b></div>'."\n";
	$msg .= "	<div>{$error}</div>\n";
	$msg .= '</div>'."\n";
	echo $msg;
	exit;
	}
//select database
$sel=databaseSelectDb($CONFIG['dbname']);
//create the db if it does not exist
if(!$sel){
	if(databaseQuery("create database {$CONFIG['dbname']}")){
		if(isPostgreSQL()){
        	$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
		}
		$sel=databaseSelectDb($CONFIG['dbname']);
	}
}
if(!$sel){
	$error=databaseError();
	$msg = '<div>'."\n";
	$msg .= '	<div class="w_bigger w_red"><img src="/wfiles/iconsets/32/abort.png" border="0" style="vertical-align:middle;"> Failed to select database named <b>'.$CONFIG['dbname'].'</b> in <b>'.$CONFIG['dbtype'].'</b> on <b>'.$CONFIG['dbhost'].'</b></div>'."\n";
	$msg .= "	<div>{$error}</div>\n";
	$msg .= '</div>'."\n";
	abort($msg);
	}
//up the memory limit to resolve the "allowed memory" error
if(isset($CONFIG['memory_limit']) && strlen($CONFIG['memory_limit'])){ini_set("memory_limit",$CONFIG['memory_limit']);}
else{ini_set("memory_limit","500M");}
/* Load_pages as specified in the conf settings */
if(isset($_REQUEST['_action']) && strtoupper($_REQUEST['_action'])=='EDIT' && strtoupper($_REQUEST['_return'])=='XML' && isset($_REQUEST['apikey'])){}
elseif(isset($_REQUEST['apimethod']) && $_REQUEST['apimethod']=='posteditxml' && isset($_REQUEST['apikey'])){}
elseif(isset($CONFIG['load_pages']) && strlen($CONFIG['load_pages'])){
	$loads=explode(',',$CONFIG['load_pages']);
	foreach($loads as $load){
		$getopts=array('-table'=>'_pages','-field'=>"body");
		if(isNum($load)){$getopts['-where']="_id={$load}";}
		else{$getopts['-where']="name = '{$load}'";}
		$ok=includeDBOnce($getopts);
		if(!isNum($ok) || $ok==0){abort("Load_Pages failed to load {$load} - {$ok}");}
		}
	}
//---------- begin function checkDBTableSchema
/**
* @describe function to check for required fields in certain wasql pages
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function checkDBTableSchema($wtable){
	global $CONFIG;
	$finfo=getDBFieldInfo($wtable);
	$recs=getDBIndexes(array($wtable));
	$index=array();
	foreach($recs as $rec){
    	$key=$rec['column_name'];
    	$index[$key]=$rec;
	}
	$rtn='';
    if($wtable=='_pages'){
		if(!isset($finfo['_amem'])){
			$query="ALTER TABLE {$wtable} ADD _amem ".databaseDataType('bigint')." NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _amem to _pages table<br />\n";
        }
        if(isset($CONFIG['minify_css']) && $CONFIG['minify_css']==1 && !isset($finfo['css_min'])){
			$query="ALTER TABLE {$wtable} ADD css_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added css_min to {$wtable} table<br />\n";
        }
        if(isset($CONFIG['minify_js']) && $CONFIG['minify_js']==1 && !isset($finfo['js_min'])){
			$query="ALTER TABLE {$wtable} ADD js_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added js_min to {$wtable} table<br />\n";
        }
        if(!isset($finfo['_cache'])){
			$query="ALTER TABLE {$wtable} ADD _cache ".databaseDataType('tinyint')." NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added _cache to _pages table<br />\n";
        }
        if(!isset($finfo['controller'])){
			$query="ALTER TABLE {$wtable} ADD controller text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='controller'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'controller','displayname'=>'Controller',
				'inputtype'=>'textarea','width'=>700,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added controller to _pages table<br />\n";
        }
        if(!isset($finfo['functions'])){
			$query="ALTER TABLE {$wtable} ADD functions ".databaseDataType('mediumtext')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='functions'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'functions','displayname'=>'Controller Functions',
				'inputtype'=>'textarea','width'=>700,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added functions to _pages table<br />\n";
        }
        if(!isset($finfo['permalink'])){
			$query="ALTER TABLE {$wtable} ADD permalink varchar(255) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='permalink'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'permalink','displayname'=>'Permalink',
				'inputtype'=>'text','width'=>700,'inputmax'=>255
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added permalink to _pages table<br />\n";
        }
        if(!isset($finfo['js'])){
			$query="ALTER TABLE {$wtable} ADD js text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='js'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'js','displayname'=>'Javascript',
				'inputtype'=>'textarea','width'=>700,'height'=>120,'behavior'=>"jseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added js to _pages table<br />\n";
        }
        if(!isset($finfo['css'])){
			$query="ALTER TABLE {$wtable} ADD css text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='css'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'css','displayname'=>'CSS / Styles',
				'inputtype'=>'textarea','width'=>700,'height'=>120,'behavior'=>"csseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added css to _pages table<br />\n";
        }
        if(!isset($finfo['title'])){
			$query="ALTER TABLE {$wtable} ADD title varchar(255) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='title'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'title','displayname'=>'Page Title',
				'inputtype'=>'text','width'=>200,'inputmax'=>255
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added title to _pages table<br />\n";
        }
        if(!isset($finfo['sort_order'])){
			$query="ALTER TABLE {$wtable} ADD sort_order int NOT NULL Default 0;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='sort_order'"));

			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'sort_order','displayname'=>'Sort Order',
				'inputtype'=>'text','width'=>200,'inputmax'=>255,'mask'=>'integer'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added sort_order to _pages table<br />\n";
        }
        if(!isset($finfo['parent'])){
			$query="ALTER TABLE {$wtable} ADD parent int NOT NULL Default 0;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='parent'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'parent','displayname'=>'Parent Page',
				'inputtype'=>'select',
				'tvals'=>"select _id from _pages order by permalink,name,_id",
				'dvals'=>"select name,permalink from _pages order by permalink,name,_id"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added parent to _pages table<br />\n";
        }
        if(!isset($finfo['user_content'])){
			$query="ALTER TABLE {$wtable} ADD user_content text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='user_content'"));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $wtable,
				'fieldname'		=> 'user_content','displayname'=>'User Content',
				'description'	=> 'A place for user driven content without logic',
				'inputtype'		=> 'textarea',
				'width'			=> '700',
				'height'		=> '120'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added user_content to _pages table<br />\n";
        }
        if(!isset($finfo['postedit'])){
			$query="ALTER TABLE {$wtable} ADD postedit ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='postedit'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'postedit',
				'description'=>'if not checked then do not show in postedit',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added postedit to _pages table<br />\n";
        }
        if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize',
				'description'=>'if not checked then do not synchronize with live db',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _pages table<br />\n";
        }
        if(!isset($finfo['_env'])){
			$query="ALTER TABLE {$wtable} ADD _env text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_env'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_env','displayname'=>'Environment',
				'inputtype'=>'textarea','width'=>500,'height'=>100
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _env to _pages table<br />\n";
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _pages table<br />\n";
        }
        if(!isset($finfo['_auser'])){
			$query="ALTER TABLE {$wtable} ADD _auser integer NOT NULL Default 0;";
			$ok=executeSQL($query);;
			$rtn .= " added _auser to _pages table<br />\n";
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _pages table<br />\n";
        }
        //check indexes
        if(!isset($index['permalink'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>"permalink"));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
	}
	//make sure _templates table has functions
    if($wtable=='_templates'){
		if(isset($CONFIG['minify_css']) && $CONFIG['minify_css']==1 && !isset($finfo['css_min'])){
			$query="ALTER TABLE {$wtable} ADD css_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added css_min to {$wtable} table<br />\n";
        }
        if(isset($CONFIG['minify_js']) && $CONFIG['minify_js']==1 && !isset($finfo['js_min'])){
			$query="ALTER TABLE {$wtable} ADD js_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added js_min to {$wtable} table<br />\n";
        }
        if(!isset($finfo['functions'])){
			$query="ALTER TABLE {$wtable} ADD functions ".databaseDataType('mediumtext')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='functions'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'functions','displayname'=>'Controller Functions',
				'inputtype'=>'textarea','width'=>400,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added functions to _templates table<br />\n";
        }
        if(!isset($finfo['js'])){
			$query="ALTER TABLE {$wtable} ADD js text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='js'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'js','displayname'=>'Javascript',
				'inputtype'=>'textarea','width'=>400,'height'=>120,'behavior'=>"jseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added js to _templates table<br />\n";
        }
        if(!isset($finfo['css'])){
			$query="ALTER TABLE {$wtable} ADD css text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='css'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'css','displayname'=>'CSS / Styles',
				'inputtype'=>'textarea','width'=>400,'height'=>120,'behavior'=>"csseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added css to _templates table<br />\n";
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _templates table<br />\n";
        }
        if(!isset($finfo['_auser'])){
			$query="ALTER TABLE {$wtable} ADD _auser integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added _auser to _templates table<br />\n";
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _templates table<br />\n";
        }
        if(!isset($finfo['postedit'])){
			$query="ALTER TABLE {$wtable} ADD postedit ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='postedit'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'postedit',
				'description'=>'if not checked then do not show in postedit',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added postedit to _templates table<br />\n";
        }
        if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize',
				'description'=>'if not checked then do not synchronize with live db',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _templates table<br />\n";
        }
	}
	//make sure _users table has _env
    if($wtable=='_users'){
        if(!isset($finfo['_env'])){
			$query="ALTER TABLE {$wtable} ADD _env text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_env'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_env','displayname'=>'Environment',
				'inputtype'=>'textarea','width'=>500,'height'=>100
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _env to _users table<br />\n";
        }
        if(!isset($finfo['_sid'])){
			$query="ALTER TABLE {$wtable} ADD _sid varchar(150) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _sid to _users table<br />\n";
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _users table<br />\n";
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _users table<br />\n";
        }
        if(!isset($finfo['_apage'])){
			$query="ALTER TABLE {$wtable} ADD _apage INT NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _apage to _users table<br />\n";
        }
        //check indexes
        if(!isset($index['guid'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>"active,guid"));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
	}
	//make sure _synchronize table has review_user, review_pass, review_user_id
    if($wtable=='_synchronize'){
		$finfo=getDBFieldInfo($wtable);
        if(!isset($finfo['review_user'])){
			$query="ALTER TABLE {$wtable} ADD review_user varchar(255) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='review_user'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'review_user','displayname'=>'Username',
				'inputtype'=>'text','width'=>140
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added review_user to _synchronize table<br />\n";
        }
        if(!isset($finfo['review_pass'])){
			$query="ALTER TABLE {$wtable} ADD review_pass varchar(25) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='review_pass'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'review_pass','displayname'=>'Password',
				'inputtype'=>'password','width'=>140
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added review_pass to _synchronize table<br />\n";
        }
        if(!isset($finfo['review_user_id'])){
			$query="ALTER TABLE {$wtable} ADD review_user_id int NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added review_user_id to _synchronize table<br />\n";
        }
	}
	//make sure _cron table has a cron_pid field
	if($wtable=='_cron'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['cron_pid'])){
			$query="ALTER TABLE {$wtable} ADD cron_pid integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added cron_pid to _cron table<br />\n";
        }
        if(!isset($finfo['logfile'])){
			$query="ALTER TABLE {$wtable} ADD logfile varchar(255) NULL;";
			$ok=executeSQL($query);
			$query="ALTER TABLE {$wtable} ADD logfile_maxsize integer NULL;";
			$ok=executeSQL($query);
			$rtn .= " added logfile to _cron table<br />\n";
        }
        //check indexes
        if(!isset($index['name'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'active,name'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
    }
    if($wtable=='_cronlog'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['count_crons_inactive'])){
			$query="drop TABLE {$wtable};";
			$ok=executeSQL($query);
			createWasqlTables(array($wtable));
			$rtn .= " added count_crons_inactive to _cronlog table<br />\n";
        }
        //check indexes
        if(!isset($index['name'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'name'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
    }
    //make sure _queries table has a tablename field
    if($wtable=='_queries'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['tablename'])){
			$query="ALTER TABLE {$wtable} ADD tablename varchar(255) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tablename to _queries table<br />\n";
        }
        //check indexes
        if(!isset($index['tablename'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'tablename'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
    }
    //make sure _tabledata table has a tablename field
    if($wtable=='_tabledata'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['tablegroup'])){
			$query="ALTER TABLE {$wtable} ADD tablegroup varchar(255) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tablegroup to _tabledata table<br />\n";
        }
		if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 0";
			$ok=executeSQL($query);
			global $SETTINGS;
			$stables=array('_cron','_reports','_pages','_templates','_tabledata','fielddata');
			$oldstables=preg_split('/[\r\n]+/',trim($SETTINGS['wasql_synchronize_tables']));
			if(is_array($oldstables)){
				foreach($oldstables as $stable){
	            	if(!in_array($stable,$stables)){$stables[]=$stable;}
				}
			}
			$stablestr=implode("','",$stables);
			$query="update _tabledata set synchronize=1 where tablename in ('{$stablestr}')";
			$ok=executeSQL($query);
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $wtable,
				'fieldname'		=> 'synchronize',
				'description'	=> 'if not checked then do not synchronize with live db',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _tabledata table<br />\n";
        }
        if(!isset($finfo['tabledesc'])){
			$query="ALTER TABLE {$wtable} ADD tabledesc varchar(500) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tabledesc to _tabledata table<br />\n";
        }
    }
    //make sure _fielddata table has a description field
    if($wtable=='_fielddata'){
		$finfo=getDBFieldInfo($wtable,1);
		//check for slider control
		if(!stringContains($finfo['inputtype']['tvals'],'slider')){
			$id=editDBRecord(array('-table'=>'_fielddata',
				'-where'		=> "tablename='_fielddata' and fieldname='inputtype'",
				'tvals'			=> "checkbox\r\ncolor\r\ncombo\r\ndate\r\ndatetime\r\nfile\r\nformula\r\nhidden\r\nmultiselect\r\npassword\r\nradio\r\nselect\r\nslider\r\ntext\r\ntextarea\r\ntime",
				'dvals'			=> "Checkbox\r\nColor\r\nComboBox\r\nDate\r\nDate&Time\r\nFileUpload\r\nFormula\r\nHidden\r\nMultiSelect\r\nPassword\r\nRadio\r\nSelect\r\nSlider\r\nText\r\nTextarea\r\nTime",
				'onchange'		=> "fielddataChange(this);"
			));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " updated dvals and dvals of inputtype field in _fielddata table<br />\n";
		}
		if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize','defaultval'=>1,
				'inputtype'=>'checkbox','tvals'=>1,'editlist'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$cronfields=array('active','cron_pid','run_date','run_date_utime','running','run_length','run_result');
			foreach($cronfields as $cronfield){
				$query="update _fielddata set synchronize=0 where tablename='_cron' and fieldname='{$cronfield}'";
				$ok=executeSQL($query);
			}
			$id=editDBRecord(array('-table'=>'_tabledata',
				'-where'		=> "tablename='_fielddata'",
				'formfields'	=> "tablename fieldname\r\ndescription\r\ndisplayname\r\ninputtype mask behavior\r\nwidth height inputmax required editlist synchronize\r\nonchange\r\ntvals\r\ndvals\r\ndefaultval\r\nhelp",
				'listfields'	=> "tablename\r\nfieldname\r\ninputtype\r\nwidth\r\nheight\r\ninputmax\r\neditlist\r\nsynchronize\r\ndescription"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _fielddata table<br />\n";
        }
		if(!isset($finfo['description'])){
			$query="ALTER TABLE {$wtable} ADD description varchar(255) NULL;";
			$ok=executeSQL($query);
			//modify tabledata to include description
			$id=editDBRecord(array('-table'=>'_tabledata',
				'-where'		=> "tablename='_fielddata'",
				'formfields'	=> "tablename fieldname\r\ndescription\r\ndisplayname\r\ninputtype related_table behavior\r\nwidth height inputmax required editlist mask\r\nonchange\r\ntvals\r\ndvals\r\ndefaultval\r\nhelp",
				'listfields'	=> "tablename\r\nfieldname\r\ninputtype\r\nwidth\r\nheight\r\ninputmax\r\neditlist\r\ndescription"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			//add fielddata metadata and make it in editlist
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'editlist'		=> 1,
				'width'			=> 400,
				'inputmax'		=> 255,
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added description to _fielddata table<br />\n";
        }
        //check indexes
        if(!isset($index['tablename'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'tablename,fieldname','-unique'=>true));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
    }
    //make sure _queries table has a tablename field
    if($wtable=='_settings'){
		$finfo=getDBFieldInfo($wtable);
		//echo printValue($finfo);
		//exit;
		if($finfo['key_value']['_dblength'] > 0 && $finfo['key_value']['_dblength'] < 5000){
			if(isPostgreSQL()){
				$query="ALTER TABLE {$wtable} ALTER COLUMN key_value TYPE varchar(5000);";
			}
			else{
				$query="ALTER TABLE {$wtable} MODIFY key_value varchar(5000) NULL;";
			}
			$ok=executeSQL($query);
			$rtn .= " changed key_value field length from {$finfo['key_value']['_dblength']} to 5000 in _settings table<br />\n";
			$rtn .= $query;
        }
        //check indexes
        if(!isset($index['key_name'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'key_name,user_id'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />\n";
		}
    }
    return $rtn;
}
//---------- begin function clearDBCache
/**
* @describe clears the database cache array used so the same query does not happen twice
* @exclude  - this function is for internal use only and thus excluded from the manual
* @usage 
*	clearDBCache();
*	clearDBCache('getDBRecords');
*/
function clearDBCache($names){
	global $databaseCache;
	if(is_array($names)){}
	elseif(strlen($names)){$names=array($names);}
	else{
    	$databaseCache=array();
    	return 1;
	}
	foreach($names as $name){
		if(isset($databaseCache[$name])){unset($databaseCache[$name]);}
	}
	return 1;
}
//---------- begin function addDBAccess
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function addDBAccess(){
	global $PAGE;
	global $SETTINGS;
	if(!isset($SETTINGS['wasql_access']) || $SETTINGS['wasql_access']!=1){return;}
	//ignore bot requests
	if((!isset($SETTINGS['wasql_access_bot']) || $SETTINGS['wasql_access_bot']!=1) && strlen($_SERVER['REMOTE_OS']) && stringBeginsWith($_SERVER['REMOTE_OS'],"BOT:")){return;}
	$access_days=32;
	$access_log=confValue('access_log');
	$access_dbname=confValue('access_dbname');
	$table='_access';
	$sumtable='_access_summary';
	if(isset($SETTINGS['wasql_access_dbname']) && strlen($SETTINGS['wasql_access_dbname'])){
		$table="{$SETTINGS['wasql_access_dbname']}.{$table}";
		$sumtable="{$SETTINGS['wasql_access_dbname']}.{$sumtable}";
    	}
	$fields=getDBFields($table);
	$opts=array();
	foreach($fields as $field){
		$ufield=strtoupper($field);
		if(isset($_REQUEST[$field])){$opts[$field]=$_REQUEST[$field];}
		elseif(isset($_REQUEST[$ufield])){$opts[$field]=$_REQUEST[$ufield];}
		elseif(isset($_SERVER[$field])){$opts[$field]=$_SERVER[$field];}
		elseif(isset($_SERVER[$ufield])){$opts[$field]=$_SERVER[$ufield];}
        }
    $opts['page']=$PAGE['name'];
    $opts['session_id']=session_id();
    $opts['xml']=request2XML($_REQUEST);
    $opts['-table']=$table;
	$id=addDBRecord($opts);
	if(!isNum($id)){
		setWasqlError(debug_backtrace(),$id);
		}
	//add this request to the summary table
	$finfo=getDBFieldInfo($sumtable,1);
	$parts=array();
	foreach($fields as $field){
		if(isset($finfo["{$field}_unique"])){
			$parts[]="count(distinct({$field})) as {$field}_unique";
        	}
		}
	if(in_array('guid',$fields)){$parts[]="count(distinct(guid)) as visits_unique";}
	$query="select http_host,count(_id) as visits,".implode(',',$parts)." from {$table} where YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW()) group by http_host";
	$recs=getDBRecords(array('-query'=>$query));
	if(is_array($recs)){
        foreach($recs as $rec){
			$opts=array('-table'=>$sumtable);
			foreach($rec as $key=>$val){
				$opts[$key]=$val;
	        	}
			$rec=getDBRecord(array('-table'=>$sumtable,'-where'=>"http_host = '{$rec['http_host']}' and YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW())"));
			if(is_array($rec)){
				$opts['-where']="_id={$rec['_id']}";
				$ok=editDBRecord($opts);
				}
			else{
				$opts['accessdate']=date("Y-m-d");
				$id=addDBRecord($opts);
				if(!isNum($id)){
					setWasqlError(debug_backtrace(),$id);
					}
				}
			}
		}
	//remove _access records older than 32 days old
	if(!isset($_SERVER['addDBAccess'])){
		$query="delete from {$table} where _cdate < DATE_ADD(NOW(), INTERVAL -32 DAY)";
		$x=executeSQL($query);
		$_SERVER['addDBAccess']=1;
		}
    return true;
}
//---------- begin function addDBHistory
/**
* @describe - add an action to the _history table
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function addDBHistory($action,$table,$where){
	if(!isDBTable("_history")){return false;}
	//is history turned off in th config file?
	$history_log=confValue('history_log');
	if(strlen($history_log) && preg_match('/^(false|0|off)$/i',$history_log)){return;}
	if(preg_match('/^_(history|users|access)$/is',$table)){return false;}
	global $PAGE;
	//valid action values: add,edit,del,
	if(isDBTable("_history")){
		$info=getDBFieldInfo($table,1);
		if(!isset($info['xmldata'])){return false;}
		$action=strtolower(trim($action));
		$recs=getDBRecords(array('-table'=>$table,'-where'=>$where));
		if(is_array($recs)){
			foreach($recs as $rec){
				ksort($rec);
				$md5=md5(implode('',array_values($rec)));
				//don't update it nothing changed
				if(getDBCount(array('-table'=>"_history",'tablename'=>$table,'record_id'=>$rec['_id'],'md5'=>$md5))){continue;}
				$opts=array(
					'-table'	=> "_history",
					'action'	=> $action,
					'tablename'	=> $table,
					'record_id'	=> $rec['_id'],
					'xmldata'	=> request2XML($rec,array()),
					'md5'		=> $md5
					);
				if(isNum($PAGE['_id'])){$opts['page_id']=$PAGE['_id'];}
				$id=addDBRecord($opts);
				if(!isNum($id)){
					setWasqlError(debug_backtrace(),$id);
					}
            	}
        	}
        }
	return false;
}
//---------- begin function addDBIndex--------------------
/**
* @describe add an index to a table
* @param params array
*	-table
*	-fields
*	[-fulltext]
*	[-unique]
*	[-name] name of the index
* @return boolean
* @usage
*	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function addDBIndex($params=array()){
	if(!isset($params['-table'])){return 'addDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'addDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//fulltext or unique
	$fulltext=$params['-fulltext']?' FULLTEXT':'';
	$unique=$params['-unique']?' UNIQUE':'';
	//prefix
	$prefix='';
	if(strlen($unique)){$prefix .= 'U';}
	if(strlen($fulltext)){$prefix .= 'F';}
	$prefix.='IDX';
	//name
	$fieldstr=implode('_',$params['-fields']);
	if(!isset($params['-name'])){$params['-name']="{$prefix}_{$params['-table']}_{$fieldstr}";}
	//build and execute
	$fieldstr=implode(", ",$params['-fields']);
	$query="alter table {$params['-table']} add{$fulltext}{$unique} index {$params['-name']} ({$fieldstr})";
	return executeSQL($query);
}
//---------- begin function dropDBIndex--------------------
/**
* @describe drop an index previously created
* @param params array
*	-table
*	-name
* @return boolean
* @usage $ok=addDBIndex(array('-table'=>$table,'-name'=>"myindex"));
*/
function dropDBIndex($params=array()){
	if(!isset($params['-table'])){return 'dropDBIndex Error: No table';}
	if(!isset($params['-name'])){return 'dropDBIndex Error: No name';}
	//build and execute
	$query="alter table {$params['-table']} drop index {$params['-name']}";
	return executeSQL($query);
}
//---------- begin function addEditDBForm--------------------
/**
* @describe 
*	returns html form used to enter data into specified table
*	to add/override and event add {field}_{event}=>"function" as a param
*	to override class for checkbox fields, add {field}_checkclass=>"classname",{field}_checkclasschecked=>"checkedClassName" as params
* @param params array
*	-table
*	[_id] integer - turns the form into an edit form, editing the record with this ID
*	[-where] string - turns the form into an edit form, editing the record that matches the where clause
*	[-fields] string - specifies the fields to use in the form.  A comma denotes a new row, a colon denotes on the same row
*	[-method] string - POST or GET - defaults to POST
*	[-class] string - class attribute for form tag - defaults to w_form
*	[-name] string - name attribute for form tag - defaults to addedit
*	[-action] string - action attribute for form tag - defaults to $PAGE['name']
*	[-onsubmit] string - onsubmit attribute for form tag - defaults to 'return submitForm(this);'
*	[-ajax] string - changes the onsubmit attribute to ajaxSubmitForm(this,'{$params['-ajax']}');return false;"
*	[-enctype] string - defaults to application/x-www-form-urlencoded. If the form has an file upload field changes to multipart/form-data
*	[-id] string - sets the id attribute for form tag
*	[-accept-charset] - sets the accept-charset attribute - defaults to the database charset
*	[-utf8] - sets the accept-charset attribute to "utf-8"
*	[-template] - adds _template to the form. On ajax calls set to the blank template, normally 1
*	[-honeypot] string - name of the honeypot.  Use this to eliminate spam posts
*	[-save] string - name of the submit button - defaults to Save
*	[-hide] string - comma separated list of submit buttons to hide. i.e reset, delete, clone
*	[-focus] string - field name to set focus to
* @return string - HTML form
* @usage <?=addEditDBForm(array('-table'=>"comments"));?>
*/
function addEditDBForm($params=array(),$customcode=''){
	if(!isset($params['-table'])){return 'addEditDBForm Error: No table';}
	unset($rec);
	if(isset($params['_id']) && isNum($params['_id'])){
		$rec=getDBRecord(array('-table'=>$params['-table'],'_id'=>$params['_id']));
    }
    elseif(isset($params['-where']) && strlen($params['-where'])){
		$rec=getDBRecord(array('-table'=>$params['-table'],'-where'=>$params['-where']));
    }
    $preview='';
    if(isset($rec) && is_array($rec)){
		if($params['-table']=='_pages'){$preview=$rec['name'];}
		foreach($rec as $key=>$val){$_REQUEST[$key]=$val;}
    }
	global $USER;
	global $PAGE;
	$includeFields=array();
	$rtn='';
	//get table info for this table
	$info=getDBTableInfo(array('-table'=>$params['-table'],'-fieldinfo'=>1));
	//echo printValue($info);exit;
	if(isset($params['-formfields'])){$params['-fields']=$params['-formfields'];}
	if(isset($params['-fields']) && is_array($params['-fields']) && count($params['-fields']) > 0){
		$info['formfields']=$params['-fields'];
	}
    elseif(isset($params['-fields']) && strlen($params['-fields']) > 0){
		$info['formfields']=array();
		$rows=preg_split('/[\r\n\,]+/',$params['-fields']);
		foreach($rows as $row){
			if(isXML((string)$row)){$line=$row;}
			else{$line=preg_split('/[\t\s\:]+/',$row);}
			array_push($info['formfields'],$line);
        }
    }
    //if formfields is not set - use the default in the backend table metadata
    if(!is_array($info['formfields']) || count($info['formfields'])==0){
		$info['formfields']=$info['default_formfields'];
	}
    //Build the form fields
    $rtn .= "\n";
    $method=isset($params['-method'])?$params['-method']:'POST';
    //form class
    $formclass=isset($params['-class'])?$params['-class']:'w_form';
    //form name
    if(isset($params['-name'])){$formname=$params['-name'];}
    elseif(isset($params['-formname'])){$formname=$params['-formname'];}
    else{$formname='addedit';}
    //form action
    $action=isset($params['-action'])?$params['-action']:'/'.$PAGE['name'].'.phtm';
    //form onsubmit
    $onsubmit='return submitForm(this);';
	if(isset($params['-onsubmit'])){$onsubmit=$params['-onsubmit'];}
	elseif(isset($params['-ajax']) && strlen($params['-ajax'])){$onsubmit="ajaxSubmitForm(this,'{$params['-ajax']}');return false;";}
    //form enctype
    if(isset($params['-enctype'])){$enctype=$params['-enctype'];}
	else{$enctype="application/x-www-form-urlencoded";}
    //check to see if there are any file upload fields, if so change the enctype
    foreach($info['formfields'] as $fields){
		if(is_array($fields)){
			foreach($fields as $field){
				if($info['fieldinfo'][$field]['inputtype']=='file'){
	                $enctype="multipart/form-data";
	                break;
	            	}
	        	}
			}
		elseif($info['fieldinfo'][$fields]['inputtype']=='file'){
	        $enctype="multipart/form-data";
	        break;
	        }
		if($enctype=="multipart/form-data"){break;}
		}
    $rtn .= '<form name="'.$formname.'" class="'.$formclass.'" method="'.$method.'" action="'.$action.'" ';
    //id
    if($params['-id']){
		$rtn .= ' id="'.$params['-id'].'"';
	}

	//enctype
	if($enctype != "none"){
    	$rtn .= ' enctype="'.$enctype.'"';
	}
    //charset - if not set, look at the database and see what it is using to set charset
    //charset reference: http://www.w3schools.com/tags/ref_charactersets.asp
	if($params['-utf8']){
		$rtn .= ' accept-charset="utf-8"';
	}
	elseif($params['-accept-charset']){
		$rtn .= ' accept-charset="'.$params['-accept-charset'].'"';
	}
	else{
    	$charset=getDBCharset();
    	switch(strtolower($charset)){
            case 'utf8':
            case 'utf-8':
            	//Unicode Standard - is the preferred encoding for e-mail and web pages
            	$rtn .= ' accept-charset="UTF-8"';
            	break;
            case 'latin1':
            case 'iso-8859-1':
            	//North America, Western Europe, Latin America, the Caribbean, Canada, Africa
            	$rtn .= ' accept-charset="ISO-8859-1"';
            	break;
            case 'latin2':
            case 'iso-8859-2':
            	//Eastern Europe
            	$rtn .= ' accept-charset="ISO-8859-2"';
            	break;
		}
	}
	$rtn .= ' onsubmit="'.$onsubmit.'"';
	$rtn .= '>'."\n";
	if(isset($params['-template'])){
		$rtn .= '<input type="hidden" name="_template" value="'.$params['-template'].'">'."\n";
		}
	elseif(isset($params['_template'])){
		$rtn .= '<input type="hidden" name="_template" value="'.$params['_template'].'">'."\n";
		}
    $rtn .= '<input type="hidden" name="_table" value="'.$params['-table'].'">'."\n";
    $rtn .= '<input type="hidden" name="_formname" value="'.$formname.'">'."\n";
    $rtn .= '<input type="hidden" name="_enctype" value="'.$enctype.'">'."\n";
    $rtn .= '<input type="hidden" name="_action" value="">'."\n";
	if($params['-auth_required']){
		$rtn .= '<input type="hidden" name="_auth_required" value="1">'."\n";
	}
    if(strlen($preview)){$rtn .= '<input type="hidden" name="_preview" value="'.$preview.'">'."\n";}
    $fieldlist=array();

    $used=array();
    if(isset($_REQUEST['_sort'])){
    	$rtn .= '<input type="hidden" name="_sort" value="'.$_REQUEST['_sort'].'">'."\n";

    	$used['_sort']=1;
		}
	$hasBehaviors=0;
	if(isset($params['-honeypot'])){
		$honeypot=$params['-honeypot'];
		$rtn .= '<input type="hidden" name="_honeypot" value="'.$honeypot.'">'."\n";
		$rtn .= '<div style="display:none"><input type="text" name="'.$honeypot.'" value=""></div>'."\n";
		}
	$editable_fields=array();
    foreach($info['formfields'] as $fields){
		if(!is_array($fields) && isXML((string)$fields)){
			$customrow=trim((string)$fields);
			if(preg_match('/^\<\?(.+?)\?\>$/is',$customrow)){$customrow = trim(evalPHP($customrow));}
			//convert [{field}] to getDBFieldTags
			unset($cm);
			preg_match_all('/\[(.+?)\]/sm',$customrow,$cm);
			$cnt=count($cm[1]);
			for($ex=0;$ex<$cnt;$ex++){
				$cfield=$cm[1][$ex];
				$value=isset($params[$cfield])?$params[$cfield]:$_REQUEST[$cfield];
				if(isset($params[$cfield.'_viewonly'])){
					$cval=nl2br($value);
				}
				else{
					$cval=getDBFieldTag(array('-field'=>$cfield,'-table'=>$params['-table'],'value'=>$value));
				}
				$customrow=str_replace($cm[0][$ex],$cval,$customrow);
				if(!isset($params[$cfield.'_viewonly'])){$fieldlist[]=$cfield;}
				if(!isset($used[$cfield])){$used[$cfield]=1;}
				else{$used[$cfield]+=1;}
            }
			$rtn .= $customrow;
			continue;
			}
		//set required string
		$required_char=isset($params['-required'])?$params['-required']:'*';
		$required = '			<b class="w_required" title="Required Field">'.$required_char.'</b>'."\n";
		//row
		$rtn .= '<table class="w_formtable" cellspacing="0" cellpadding="2" border="0">'."\n";
		$rtn .= '	<tr valign="top">'."\n";
		if(is_array($fields)){
			foreach($fields as $field){
				if(!isset($field) || !strlen($field)){continue;}
				$includeFields[$field]=1;
				$opts=array('-table'=>$params['-table'],'-field'=>$field,'-formname'=>$formname);
				if(isset($params['_id']) && isNum($params['_id'])){$opts['-editmode']=true;}
				if(isset($params['-class_all'])){$opts['class']=$params['-class_all'];}
				if(isset($params['-style_all'])){$opts['style']=$params['-style_all'];}
				if(isset($params[$field])){$opts['value']=$params[$field];}
				if(!isset($params[$field.'_viewonly'])){$fieldlist[]=$field;}
				if(isset($params[$field.'_dname'])){
					$dname=$params[$field.'_dname'];
					$used[$field.'_dname']=1;
					}
				elseif(isset($params[$field.'_displayname'])){
					$dname=$params[$field.'_displayname'];
					$used[$field.'_displayname']=1;
					}
				elseif(isset($info['fieldinfo'][$field]['displayname']) && strlen($info['fieldinfo'][$field]['displayname'])){$dname=$info['fieldinfo'][$field]['displayname'];}
				else{
					$dname=str_replace('_',' ',ucfirst($field));
					}
				//if it is a slider control build a data map from tvals and dvals if given
				if($info['fieldinfo'][$field]['inputtype']=='slider'){
                	if(strlen($info['fieldinfo'][$field]['tvals']) && strlen($info['fieldinfo'][$field]['tvals'])){
                    	$opts['data-labelmap']=mapDBDvalsToTvals($params['-table'],$field);
					}
				}
				//opts
				$forcedatts=array(
					'id','name','class','style','onclick','onchange','onmouseover','onmouseout','onkeypress','onkeyup','onkeydown','onblur','_behavior','data-behavior','display','onfocus','title','alt','tabindex',
					'accesskey','required','readonly','requiredmsg','mask','maskmsg','displayname','size','maxlength','wrap',
					'behavior','defaultval','tvals','dvals','width','height','inputtype','message','inputmax','mask','required','tablename','fieldname','help',
					'group_id','group_class','group_style','checkclass','checkclasschecked',
					'spellcheck','max','min','pattern','placeholder','readonly','step','min_displayname','max_displayname','data-labelmap'
					);
				foreach($forcedatts as $copt){
					if(isset($params[$field.'_'.$copt])){
						$opts[$copt]=$params[$field.'_'.$copt];
						$used[$field.'_'.$copt]=1;
						}
					}
				//check for field_options array - the easier, new way to override options
				if(isset($params[$field.'_options']) && is_array($params[$field.'_options'])){
					$used[$field.'_options']=1;
					foreach($params[$field.'_options'] as $okey=>$oval){
						if(in_array($okey,$forcedatts)){
							$opts[$okey]=$oval;
							$used[$field.'_'.$okey]=1;
							}
					}
				}
				//column
				//add to displayname class
				if(isset($params[$field.'_displayname_class'])){
                	$class = ' '.$params[$field.'_displayname_class'];
                	$used[$field.'_displayname_class']=1;
				}
				elseif(isset($params['-class'])){$class=$params['-class'];}
				else{$class="w_arial w_smaller";}
				if($info['fieldinfo'][$field]['inputtype']=='slider'){}
				elseif(isset($info['fieldinfo'][$field]['_required']) && $info['fieldinfo'][$field]['_required']==1){
	                $class .= ' w_required';
	            }
	            elseif(isset($info['fieldinfo'][$field]['required']) && $info['fieldinfo'][$field]['required']==1){
	                $class .= ' w_required';
	            }
				$rtn .= '		<td class="'.$class.'">'."\n";
	            //default value for add forms
	            if((!isset($rec) || !is_array($rec))){
					if(isset($params[$field.'_defaultval'])){
						$opts['value']=$params[$field.'_defaultval'];
						$used[$field.'_defaultval']=1;
					}
					elseif(strlen($info['fieldinfo'][$field]['defaultval'])){
						$opts['value']=$info['fieldinfo'][$field]['defaultval'];
						if(preg_match('/^\<\?(.+?)\?\>$/is',$opts['value'])){$opts['value'] = trim(evalPHP($opts['value']));}
					}
                }
	            //behaviors?
	            $current_value=strlen($opts['value'])?$opts['value']:'';
	            if(!strlen($current_value) && isset($_REQUEST[$field])){$current_value=$_REQUEST[$field];}
	            if(strlen($info['fieldinfo'][$field]['behavior'])){$opts['behavior']=$info['fieldinfo'][$field]['behavior'];}
		     	if(!strlen($opts['behavior']) && strlen($current_value)){
					//echo $info[$field]['value'];exit;
					if(stringContains($current_value,'/*filetype:css*/')){
		            	$opts['behavior']='csseditor';
					}
					elseif(stringContains($current_value,'/*filetype:js*/')){
		            	$opts['behavior']='jseditor';
					}
					elseif(stringContains($current_value,'/*filetype:php*/')){
		            	$opts['behavior']='phpeditor';
					}
					elseif(stringContains($current_value,'/*filetype:pl*/')){
		            	$opts['behavior']='perleditor';
					}
					elseif(stringContains($current_value,'/*filetype:rb*/')){
		            	$opts['behavior']='rubyeditor';
					}
					elseif(stringContains($current_value,'/*filetype:xml*/')){
		            	$opts['behavior']='xmleditor';
					}
					elseif(stringContains($current_value,'/*filetype:sql*/')){
		            	$opts['behavior']='sqleditor';
					}
				}
				switch(strtolower($opts['behavior'])){
					case 'csseditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using CSS Code editor. Press F1 for help menu.)</span>';
						break;
					case 'jseditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using Javascript Code editor. Press F1 for help menu.)</span>';
						break;
					case 'phpeditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using PHP Code editor. Press F1 for help menu.)</span>';
						break;
					case 'perleditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using Perl Code editor. Press F1 for help menu.)</span>';
						break;
					case 'rubyeditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using Ruby Code editor. Press F1 for help menu.)</span>';
						break;
					case 'xmleditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using XML Code editor. Press F1 for help menu.)</span>';
						break;
					case 'sqleditor':
						$dname .= ' <span style="font-size:.8em;color:#7d7d7d;">(Using SQL Code editor. Press F1 for help menu.)</span>';
						break;
				}
				if(stringContains($opts['behavior'],'editor')){
					loadExtrasCss('codemirror');
					loadExtrasJs('codemirror');
					$opts['data-ajaxid']='centerpopSQL';
				}
				//debugValue($dname);
				//debugValue($opts);
				if(strlen($preview)){$opts['-preview']=$preview;}
	            if(strlen($info['fieldinfo'][$field]['behavior'])){
					$hasBehaviors++;
	            	if($info['fieldinfo'][$field]['behavior']=='html'){
						//show html preview
						$previewID=$dname.'_previw';
						$dname .= ' <img title="Click to preview html" onclick="popUpDiv(document.'.$formname.'.'.$field.'.value,{center:1,drag:1});" src="/wfiles/iconsets/16/webpage.png" border="0" width="16" height="16" style="cursor:pointer;vertical-align:middle;">';
	                }
				}
                if(isset($params[$field.'_tvals'])){
					$opts['tvals']=$params[$field.'_tvals'];
					$used[$field.'_tvals']=1;
					}
				if(isset($params[$field.'_dvals'])){
					$opts['dvals']=$params[$field.'_dvals'];
					$used[$field.'_dvals']=1;
					}
				if(!strlen($opts['id'])){
					$opts['id']="{$formname}_{$field}";
				}
				$field_dname=$opts['id'].'_dname';
				$field_content=$opts['id'].'_content';
				if(isset($params[$field.'_viewonly'])){
					$value=isset($opts['value'])?$opts['value']:$_REQUEST[$field];
                	$rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
					$rtn .= '			<div id="'.$field_content.'">'.nl2br($value).'</div>'."\n";
				}
				elseif(isset($params[$field.'_group_id'])){
					$group_id = $params[$field.'_group_id'];
					$used[$field.'_group_id']=1;
					$rtn .= '		<div id="'.$group_id.'"';
					if(isset($params[$field.'_group_style'])){
						$rtn .= ' style="'.$params[$field.'_group_style'].'"';
						$used[$field.'_group_style']=1;
						}
					if(isset($params[$field.'_group_class'])){
						$rtn .= ' class="'.$params[$field.'_group_class'].'"';
						$used[$field.'_group_class']=1;
						}
					$rtn .= '>'."\n";
					if(isset($params[$field.'_group_custom'])){
						$rtn .= $params[$field.'_group_custom'];
						$used[$field.'_group_custom']=1;
						}
					if($info['fieldinfo'][$field]['inputtype']!='signature'){
						$rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
					}
					$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'."\n";
					$rtn .= '		</div>'."\n";
					}
				else{
					if($info['fieldinfo'][$field]['inputtype']!='signature'){
						$rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
					}
					$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'."\n";
                	}
				$rtn .= '		</td>'."\n";
				if(!isset($used[$field])){$used[$field]=1;}
				else{$used[$field]+=1;}
	        	}
        	}
        else{
			$field=(string)$fields;
			if(!strlen($field)){continue;}
			$includeFields[$field]=1;
			$opts=array('-table'=>$params['-table'],'-field'=>$field,'-formname'=>$formname);
			if(isNum($params['_id'])){$opts['-editmode']=true;}
			if(isset($params['-class_all'])){$opts['class']=$params['-class_all'];}
			if(isset($params['-style_all'])){$opts['style']=$params['-style_all'];}
			if(isset($params[$field])){$opts['value']=$params[$field];}
			if(!isset($params[$field.'_viewonly'])){$fieldlist[]=$field;}
			if(isset($params[$field.'_dname'])){
				$dname=$params[$field.'_dname'];
				$used[$field.'_dname']=1;
				}
			elseif(isset($params[$field.'_displayname'])){
				$dname=$params[$field.'_displayname'];
				$used[$field.'_displayname']=1;
				}
			elseif(isset($info['fieldinfo'][$field]['displayname']) && strlen($info['fieldinfo'][$field]['displayname'])){$dname=$info['fieldinfo'][$field]['displayname'];}
			else{
				$dname=str_replace('_',' ',ucfirst($field));
				}
			//opts
			$forcedatts=array(
				'id','name','class','style','onclick','onchange','onmouseover','onmouseout','onkeypress','onkeyup','onkeydown','onblur','_behavior','data-behavior','display','onfocus','title','alt','tabindex',
				'accesskey','required','readonly','requiredmsg','mask','maskmsg','displayname','size','maxlength','wrap',
				'behavior','defaultval','tvals','dvals','width','height','inputtype','message','inputmax','mask','required','tablename','fieldname','help',
				'group_id','group_class','group_style','checkclass','checkclasschecked',
				'spellcheck','max','min','pattern','placeholder','readonly','step'
				);
			foreach($forcedatts as $copt){
				if(isset($params[$field.'_'.$copt])){
					$opts[$copt]=$params[$field.'_'.$copt];
					$used[$field.'_'.$copt]=1;
					}
				}
			//check for field_options array - the easier, new way to override options
			if(isset($params[$field.'_options']) && is_array($params[$field.'_options'])){
				foreach($params[$field.'_options'] as $okey=>$oval){
					$used[$field.'_options']=1;
					if(in_array($okey,$forcedatts)){$opts[$okey]=$oval;}
				}
			}
			if(!is_array($rec) && strlen($info['fieldinfo'][$field]['defaultval'])){
				$opts['value']=$info['fieldinfo'][$field]['defaultval'];
				if(preg_match('/^\<\?(.+?)\?\>$/is',$opts['value'])){$opts['value'] = trim(evalPHP($opts['value']));}
                }
			//column
			$class="w_arial w_smaller";
			if(isset($params['-class'])){$class=$params['-class'];}
			elseif($info['fieldinfo'][$field]['inputtype']=='slider'){}
			elseif(isset($info['fieldinfo'][$field]['_required']) && $info['fieldinfo'][$field]['_required']==1){
                $class .= ' w_required';
            }
            elseif(isset($info['fieldinfo'][$field]['required']) && $info['fieldinfo'][$field]['required']==1){
                $class .= ' w_required';
            }
			$rtn .= '		<td class="'.$class.'">'."\n";
            //behaviors?
            if(strlen($info['fieldinfo'][$field]['behavior'])){
				$hasBehaviors++;
            	if($info['fieldinfo'][$field]['behavior']=='html'){
					//show html preview
					$previewID=$dname.'_previw';
					$dname .= ' <img title="Click to preview html" onclick="popUpDiv(document.'.$formname.'.'.$field.'.value,{center:1,drag:1});" src="/wfiles/iconsets/16/webpage.png" border="0" width="16" height="16" style="cursor:pointer;vertical-align:middle;">';
                    }
				}

			if(!strlen($opts['id'])){
				$opts['id']="{$formname}_{$field}";
			}
			$field_dname=$opts['id'].'_dname';
			$field_content=$opts['id'].'_content';
			if(isset($params[$field.'_viewonly'])){
				$value=isset($opts['value'])?$opts['value']:$_REQUEST[$field];
                $rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
				$rtn .= '			<div id="'.$field_content.'">'.nl2br($value).'</div>'."\n";
			}
            elseif(isset($params[$field.'_group_id'])){
				$used[$field.'_group_id']=1;
				$group_id = $params[$field.'_group_id'];
				$rtn .= '		<div id="'.$group_id.'"';
				if(isset($params[$field.'_group_style'])){
					$rtn .= ' style="'.$params[$field.'_group_style'].'"';
					$used[$field.'_group_style']=1;
					}
				if(isset($params[$field.'_group_class'])){
					$rtn .= ' class="'.$params[$field.'_group_class'].'"';
					$used[$field.'_group_class']=1;
					}
				$rtn .= '>'."\n";
				if(isset($params[$field.'_group_custom'])){
					$rtn .= $params[$field.'_group_custom'];
					$used[$field.'_group_custom']=1;
					}
				if($info['fieldinfo'][$field]['inputtype']!='signature'){
					$rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
				}
				$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'."\n";
				$rtn .= '		</div>'."\n";
				}
			else{
				if($info['fieldinfo'][$field]['inputtype']!='signature'){
					$rtn .= '			<div id="'.$field_dname.'">'.$dname.'</div>'."\n";
				}
				$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'."\n";
				}
			$rtn .= '		</td>'."\n";
			$used[$field]+=1;
            }
        $rtn .= '	</tr>'."\n";
        $rtn .= '</table>'."\n";
    	}
    if(is_array($rec) && isNum($rec['_id'])){

		$rtn .= '<input type="hidden" name="_id" value="'.$rec['_id'].'">'."\n";
		if(isset($params['-editfields'])){
        	if(is_array($params['-editfields'])){$params['-editfields']=implode(',',$params['-editfields']);}
        	$rtn .= '<input type="hidden" name="_fields" value="'.$params['-editfields'].'">'."\n";
		}
		else{
			$rtn .= '<input type="hidden" name="_fields" value="'.implode(',',$fieldlist).'">'."\n";
		}
    }
    //Add any other valid inputs
    $rtn .= '<div id="other_inputs" style="display:none;">'."\n";
    if(is_array($params)){
	    foreach($params as $key=>$val){
			if(isset($used[$key])){
				//$rtn .= '<!--Skipped Used:'.$key.'-->'."\n";
				continue;
			}
			if(preg_match('/^[_-]/',$key) && !preg_match('/^\_(menu|search|sort|start|table\_)$/is',$key)){
				//$rtn .= '<!--Skipped Reserved:'.$key.'-->'."\n";
				continue;
			}
			if(preg_match('/^(GUID|PHPSESSID|AjaxRequestUniqueId)$/i',$key)){
				//$rtn .= '<!--Skipped PHPID:'.$key.'-->'."\n";
				continue;
			}
			if(!is_array($val) && strlen(trim($val))==0){
				$rtn .= '	<!--skipped '.$key.': blank value -->'."\n";
				continue;
				}
			if(!isset($used[$key])){$used[$key]=1;}
			else{$used[$key]+=1;}
			if(is_array($val)){
            	foreach($val as $cval){
                	$rtn .= '	<input type="hidden" name="'.$key.'[]" value="'.$cval.'">'."\n";
				}
			}
			elseif(isNum($val)){
            	$rtn .= '	<input type="hidden" name="'.$key.'" value="'.$val.'">'."\n";
			}
			else{
				$rtn .= '	<textarea name="'.$key.'">'.$val.'</textarea>'."\n";
			}
	    }
	}
    $rtn .= '</div>'."\n";
    //buttons
    $rtn .= '<table class="w_formtable" cellspacing="0" cellpadding="2" border="0">'."\n";
	$rtn .= '	<tr>'."\n";
    $save=isset($params['-save'])?$params['-save']:'Save';
    if(isset($params['-savebutton'])){
		$rtn .= '		<td>'.$params['-savebutton'].'</td>'."\n";
	}
    elseif(is_array($rec) && isNum($rec['_id'])){
		if(!isset($params['-hide']) || !preg_match('/save/i',$params['-hide'])){
			$action=isset($params['-nosave'])?'':'Edit';
			$rtn .= '		<td><input type="submit" id="savebutton" onClick="document.'.$formname.'._action.value=\''.$action.'\';" value="'.$save.'"></td>'."\n";
			}
		if(!isset($params['-hide']) || !preg_match('/reset/i',$params['-hide'])){
			$rtn .= '		<td><input type="reset" id="resetbutton" value="Reset"></td>'."\n";
			}
		if(!isset($params['-hide']) || !preg_match('/delete/i',$params['-hide'])){
			$action=isset($params['-nosave'])?'':'Delete';
			$rtn .= '		<td><input type="submit" id="deletebutton" onClick="if(!confirm(\'Delete this record?\')){return false;}document.'.$formname.'._action.value=\''.$action.'\';" value="Delete"></td>'."\n";
			}
		if(!isset($params['-hide']) || !preg_match('/clone/i',$params['-hide'])){
			$action=isset($params['-nosave'])?'':'Add';
			$rtn .= '		<td><input type="submit" id="clonebutton" onClick="if(!confirm(\'Clone this record?\')){return false;}document.'.$formname.'._id.value=\'\';document.'.$formname.'._action.value=\''.$action.'\';" value="Clone"></td>'."\n";
			}
		}
	elseif(!isset($params['-hide']) || !preg_match('/save/i',$params['-hide'])){
		$action=isset($params['-nosave'])?'':'Add';
    	$rtn .= '		<td><input type="submit" id="savebutton" onClick="document.'.$formname.'._action.value=\''.$action.'\';" value="'.$save.'"></td>'."\n";
    	//$rtn .= '		<td><input type="reset" value="Reset"></td>'."\n";
    	}
    $rtn .= '	</tr>'."\n";
    $rtn .= '</table>'."\n";
    $rtn .= $customcode;
    $rtn .= '</form>'."\n";
    //initBehaviors?
    if($hasBehaviors && isset($_REQUEST['AjaxRequestUniqueId'])){
		$rtn .= buildOnLoad("initBehaviors();");
    }
    //set focus field?
    if(isset($params['-focus'])){
		$rtn .= buildOnLoad("document.{$formname}.{$params['-focus']}.focus();");
	}
    return $rtn;
	}
//---------- begin function addDBRecord--------------------
/**
* @describe adds record to table and returns the ID of record added
* @param params array
*	-table string - name of table
*	[-model] boolean - set to false to disable model functionality
*	treats other params as field/value pairs
* @return array
* @usage
*	$id=addDBRecord(array(
*		'-table'		=> '_tabledata',
*		'tablename'		=> '_history',
*		'formfields'	=> "_cuser action page_id record_id\r\ntablename\r\nxmldata",
*		'listfields'	=> '_cuser action page_id record_id tablename',
*		'sortfields'	=> '_cdate desc'
*	));
*/
function addDBRecord($params=array()){
	$function='addDBRecord';
	if(!isset($params['-table'])){return 'addDBRecord Error: No table';}
	$table=$params['-table'];
	global $USER;
	global $CONFIG;
	//model
	if(!isset($params['-model']) || ($params['-model'])){
		$model=getDBTableModel($table);
		$model_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$model_table=implode('.',$table_parts);
		}
	//echo printValue($model);exit;
	if(isset($model['functions']) && strlen(trim($model['functions']))){
    	$ok=includePHPOnce($model['functions'],"{$model_table}-model_functions");
    	//look for Before trigger
    	$model['check']=1;
    	if(function_exists("{$model_table}AddBefore")){
        	$params=call_user_func("{$model_table}AddBefore",$params);
        	if(!isset($params['-table'])){return "{$model_table}AddBefore Error: No Table".printValue($params);}
		}
	}
	//get field info for this table
	unset($info);
	$info=getDBFieldInfo($params['-table'],1);
	if(!is_array($info)){return $info;}
	if(isset($info['_cuser']) && !isset($params['_cuser'])){
		$params['_cuser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
    	}
    if(isset($info['_cdate']) && (!isset($params['_cdate']) || !strlen(trim($params['_cdate'])))){
		$params['_cdate']=date("Y-m-d H:i:s");
    	}
    /* Add values for fields that match $_SERVER keys */
    foreach($info as $field=>$rec){
		if(stringBeginsWith($field,'_')){continue;}
		if(isset($params[$field])){continue;}
    	$ucfield=strtoupper($field);
		if(isset($_SERVER[$ucfield])){$params[$field]=$_SERVER[$ucfield];}
	}
	/* Filter the query based on params */
	$fields=array();
	$vals=array();
	foreach($params as $key=>$val){
		//ignore params that do not match a field
		if(!isset($info[$key]['_dbtype']) || !strlen($info[$key]['_dbtype'])){continue;}
		//skip keys that begin with a dash
		if(preg_match('/^\-/',$key)){continue;}
		//null check
		if(!is_array($val) && strlen($val)==0 && preg_match('/not_null/',$info[$key]['_dbflags'])){
			return 'addDBRecord Datatype Null Error: Field "'.$key.'" cannot be null';
        	}
		array_push($fields,$key);
		//date field?
		if(strlen($val) && preg_match('/^<sql>(.+)<\/sql>$/i',$val,$pm)){
			array_push($vals,$pm[1]);
			}
		elseif(($info[$key]['_dbtype'] =='date') && preg_match('/^([0-9]{2,2})-([0-9]{2,2})-([0-9]{4,4})$/s',$val,$dmatch)){
			$val=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
			array_push($vals,"'$val'");
			}
		elseif(isset($info[$key]['inputtype']) && $info[$key]['inputtype'] =='date'){
			//date field :  03-09-2009, 2009-01-26
			if(preg_match('/^([0-9]{1,2}?)[\/\-]([0-9]{1,2}?)[\/\-]([0-9]{4,4})$/',$_REQUEST[$key],$datematch)){
				$val=$datematch[3].'-'.$datematch[1].'-'.$datematch[2];
            	}
			array_push($vals,"'$val'");
        	}
		elseif($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='tinyint' || $info[$key]['_dbtype'] =='real'){
			if(is_array($val)){$val=(integer)$val[0];}
			if(!is_numeric($val) && strtolower($val) != 'null'){return 'addDBRecord Datatype Mismatch: numeric field "'.$key.'" is type "'.$info[$key]['_dbtype'].'" and requires a numeric value';}
			array_push($vals,$val);
			}
		elseif($info[$key]['_dbtype'] =='datetime'){
			$newval='';
			unset($dmatch);
			if(preg_match('/\:/',$val)){
				$val=preg_split('/[\:\ ]+/',$val);
				if(!isset($val[3])){
					$val[3]=$val[1]>12?'pm':'am';
				}
			}
			if(is_array($val)){
				if(preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})$/s',$val[0],$dmatch)){
					if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
					if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
					$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
				}
				else{$newval=$val[0];}
				if($val[3]=='pm' && $val[1] < 12){$val[1]+=12;}
				elseif($val[3]=='am' && $val[1] ==12){$val[1]='00';}
				$newval .=" {$val[1]}:{$val[2]}:00";
            	}
            	//07-19-2011 20:52:59
            	elseif(preg_match('/([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})\ ([0-9]{1,2}?)\:([0-9]{1,2}?)\:(am|pm)/i',$val,$dmatch)){
				if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
				if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
				$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
				if(strtolower($tmatch[6])=='pm' && $tmatch[4] < 12){$tmatch[4]+=12;}
				elseif(strtolower($tmatch[6])=='am' && $tmatch[4] == 12){$tmatch[4]='00';}
				$newval .=" {$tmatch[4]}:{$tmatch[5]}:00";
            	}
            else{$newval=$val;}
            $val=$newval;
            array_push($vals,"'$val'");
			}
		elseif($info[$key]['_dbtype'] =='time'){
			if(is_array($val)){
				if($val[2]=='pm' && $val[0] < 12){$val[0]+=12;}
				elseif($val[2]=='am' && $val[0] ==12){$val[0]='00';}
				$val="{$val[0]}:{$val[1]}:00";
            	}
            elseif(preg_match('/([0-9]{1,2}?)\:([0-9]{1,2}?)\:(am|pm)/i',$val,$tmatch)){
				if(strtolower($tmatch[3])=='pm' && $tmatch[1] < 12){$tmatch[1]+=12;}
				elseif(strtolower($tmatch[3])=='am' && $tmatch[1] == 12){$tmatch[1]='00';}
				$val="{$tmatch[1]}:{$tmatch[2]}:00";
            	}
            array_push($vals,"'$val'");
			}

		else{
			if($val != 'NULL'){
				$val=databaseEscapeString($val);
				array_push($vals,"'$val'");
				}
			else{
            	array_push($vals,$val);
			}
        	}
        if(isset($info[$key.'_sha1']) && !isset($params[$key.'_sha1'])){
			$val=sha1($val);
			array_push($fields,$key.'_sha1');
			array_push($vals,"'$val'");
			}
		if(isset($info[$key.'_size']) && !isset($params[$key.'_size'])){
			$val=strlen($val);
			array_push($fields,$key.'_size');
			array_push($vals,"'$val'");
			}
    	}
    //return if no updates were found
	if(!count($fields)){
		//failure
		if(isset($model['functions'])){
	    	//look for Failure trigger
	    	if(function_exists("{$model_table}AddFailure")){
				$params['-error']="addDBRecord Error: No Fields";
	        	$params=call_user_func("{$model_table}AddFailure",$params);
			}
		}
		return "addDBRecord Error: No Fields" . printValue($params) . printValue($info);
	}
    $fieldstr=implode(",",$fields);
    $valstr=implode(",",$vals);
    $table=$params['-table'];
    if(isMssql()){$table="[{$table}]";}
    $query = 'insert into ' . $table . ' (' . $fieldstr . ') values (' . $valstr .')';
	// execute sql - return the number of rows affected
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
    	$id=databaseInsertId($query_result);
    	databaseFreeResult($query_result);
    	if(isset($model['functions'])){
	    	//look for Success trigger
	    	if(function_exists("{$model_table}AddSuccess")){
				$params['-record']=getDBRecord(array('-table'=>$table,'_id'=>$id));
	        	$params=call_user_func("{$model_table}AddSuccess",$params);
			}
		}
    	//if queries are turned on, log this query
    	if($params['-table'] != '_queries' && (!isset($params['-nolog']) || $params['-nolog'] != 1)){
			logDBQuery($query,$start,$function,$params['-table']);
			}
    	return $id;
  		}
  	else{
		$error=getDBError();
		if(isset($model['functions'])){
	    	//look for Failure trigger
	    	if(function_exists("{$model_table}AddFailure")){
				$params['-error']="addDBRecord Error:".printValue($error);
	        	$params=call_user_func("{$model_table}AddFailure",$params);
			}
		}
		return setWasqlError(debug_backtrace(),$error,$query);
  		}
	}
//---------- begin function alterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=alterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function alterDBTable($table='',$params=array(),$engine=''){
	$function='alterDBTable';
	if(!isDBTable($table)){return "No such table: {$table}";}
	if(count($params)==0 && !strlen($engine)){return "No params";}
	global $CONFIG;
	//get current database fields
	$current=array();
	$fields=getDBSchema(array($table));

	foreach($fields as $field){
		$name=strtolower(trim($field['field']));
		$name=str_replace(' ','_',$name);
		$type=$field['type'];
		if(preg_match('/^_/',$name)){
			$type=preg_replace('/unsigned$/i','',trim($type));
			$type=trim($type);
			}
		if($field['null']=='NO'){$type .= ' NOT NULL';}
		else{$type .= ' NULL';}
		if($field['key']=='PRI'){$type .= ' Primary Key';}
		elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
		if(strlen($field['default'])){
			$type .= ' Default '.$field['default'];
			}
		if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
		$current[$name]=$type;
        }
    $currentSet=$current;
    $ori_table=$table;
    /*
		MSSQL Syntax:
			ALTER TABLE table ALTER COLUMN column_name new_data_type
		NOTE: in MSSQL you cannot alter multiple columns with a single statement
	*/
    if(isMssql()){$table="[{$table}]";}
	$query = "alter table {$table} ";
	if(count($params)==0 && strlen($engine)){
    	$query .= " ENGINE = {$engine}";
		$query_result=@databaseQuery($query);
		if($query_result==true){return 1;}
		return 0;
	}
	$sets=array();
	$changed=array();
	foreach($params as $field=>$type){
		if(isset($current[$field])){
			if(isWasqlField($field) && stringBeginsWith($current[$field],'int') && stringBeginsWith($type,'int')){
				unset($current[$field]);
				continue;
				}
			if(strtolower($current[$field]) != strtolower($type)){
				if(isPostgreSQL()){$sets[]="ALTER COLUMN {$field} TYPE {$type}";}
				else{$sets[]="modify {$field} {$type}";}
				$changed[$field]=$type;
				}
			unset($current[$field]);
			}
		else{
			$sets[]="add {$field} {$type}";
			$changed[$field]=$type;
        	}
    	}
    foreach($current as $field=>$type){
		array_push($sets,"drop {$field}");
    	}
    if(count($sets)==0 && !strlen($engine)){return "Nothing changed";}
    //echo "sets".printValue($sets);
	$query .= implode(",",$sets);
	if(strlen($engine) && (isMysql() || isMysqli())){$query .= " ENGINE = {$engine}";}
	$query_result=@databaseQuery($query);
	///echo "query_result".printValue($query_result);
  	if($query_result==true){
		foreach($changed as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
    	return 1;
  		}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function createDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=createDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function createDBTable($table='',$fields=array(),$engine=''){
	global $databaseCache;
	$function='createDBTable';
	if(strlen($table)==0){return "createDBTable error: No table";}
	if(count($fields)==0){return "createDBTable error: No fields";}
	global $CONFIG;
	//verify the wasql fields are there. if not add them
	if(!isset($fields['_id'])){$fields['_id']=databasePrimaryKeyFieldString();}
	if(!isset($fields['_cdate'])){
		$fields['_cdate']=databaseDataType('datetime').databaseDateTimeNow();
		}
	if(!isset($fields['_cuser'])){$fields['_cuser']="INT NOT NULL";}
	if(!isset($fields['_edate'])){
		$fields['_edate']=databaseDataType('datetime')." NULL";
		}
	if(!isset($fields['_euser'])){$fields['_euser']="INT NULL";}
	//lowercase the tablename and replace spaces with underscores
	$table=strtolower(trim($table));
	$table=str_replace(' ','_',$table);
	$ori_table=$table;
	if(isMssql()){$table="[{$table}]";}
	$query="create table {$table} (";
	foreach($fields as $field=>$attributes){
		//lowercase the fieldname and replace spaces with underscores
		$field=strtolower(trim($field));
		$field=str_replace(' ','_',$field);
		$query .= "{$field} {$attributes},";
    	}
    $query=preg_replace('/\,$/','',$query);
    $query .= ")";
    if(strlen($engine) && (isMysql() || isMysqli())){$query .= " ENGINE = {$engine}";}
	$query_result=@databaseQuery($query);
	//echo $query . printValue($query_result);
	//clear the cache
	clearDBCache(array('databaseTables','getDBFieldInfo','isDBTable'));
  	if(!isset($query_result['error']) && $query_result==true){
		//success creating table.  Now to through the fields and create any instant meta data found
		foreach($fields as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
		return 1;
  		}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function insertDBFile
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function insertDBFile($params,$e=false){
	global $USER;
	if(!is_array($params) && isNum($params)){
    	$id=$params;
    	$params=array('_id'	=> $id,'-edit'=>$e);
	}
	$fields=getDBFields('_files',1);
	$getopts=array('-table'=>"_files");
	foreach($fields as $field){
    	if(isset($params[$field])){$getopts[$field]=$params[$field];}
	}
	$rec=getDBRecord($getopts);
	if(!is_array($rec)){return null;}
	$rtn='';
	if(preg_match('/^image/i',$rec['file_type'])){
    	//image
    	if(!isset($params['width']) && isNum($rec['file_width'])){$params['width']=$rec['file_width'];}
    	if(!isset($params['height']) && isNum($rec['file_height'])){$params['height']=$rec['file_height'];}
    	if(!isset($params['border'])){$params['border']=0;}
		if(!isset($params['class'])){$params['class']='w_middle';}
		$rtn .= '<img src="'.$rec['file'].'" ';
		$rtn .= setTagAttributes($params);
		$rtn .= '>';
		//build an html5 upload window to replace this image - exclude IE since they do not support it.
		if(isset($params['-edit']) && $params['-edit']==true && $_SERVER['REMOTE_BROWSER'] != 'msie'){
			$path=getFilePath($rec['file']);
        	$rtn .= '<div id="fileupload" data-behavior="fileupload" _table="_files" _action="EDIT" _onsuccess="location.reload(true);" file_remove="1" _id="'.$rec['_id'].'"  path="'.$path.'" style="width:'.round(($params['width']-5),0).'px;height:30px;border:1px inset #000;background:#eaeaea;">Upload to Replace</div>'."\n";
		}
	}
	return $rtn;
}
//---------- begin function instantDBMeta
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function instantDBMeta($tablename,$fieldname,$attributes){
	if(stringContains($tablename,'.')){return false;}
	//skip if already exists
	if(getDBCount(array('-table'=>"_fielddata",'tablename'=>$tablename,'fieldname'=>$fieldname))){return 0;}
	//required value
	$required=0;
	if(preg_match('/NOT NULL/i',$attributes) && !preg_match('/Default/i',$attributes) && !preg_match('/bit\(1\)/i',$attributes)){
    	$required=1;
	}
	//defaultval value if any
	$defaultval='';
	if(preg_match('/default(.+)/i',$attributes,$m)){
		$defaultval=trim($m[1]);
    	$defaultval=preg_replace('/^[\'\"]/','',$defaultval);
    	$defaultval=preg_replace('/[\'\"]$/','',$defaultval);
	}
	switch(strtolower($fieldname)){
		case 'user_id':
		case 'users_id':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'inputtype'		=> "select",
				'required'		=> $required,
				'displayname'	=> "User",
				'defaultval'	=> $defaultval,
				'tvals'			=> "select _id from _users order by firstname,lastname,_id",
				'dvals'			=> "select firstname,lastname from _users order by firstname,lastname,_id"
				));
			return 1;
			break;
		case 'state':
		case 'state_code':
			if(preg_match('/char\(2\)/i',$attributes)){
				$id=addDBRecord(array('-table'=>'_fielddata',
					'tablename'		=> $tablename,
					'fieldname'		=> $fieldname,
					'inputtype'		=> "select",
					'required'		=> $required,
					'defaultval'	=> $defaultval,
					'tvals'			=> 'select code from states order by name,_id',
					'dvals'			=> 'select name from states order by name,_id'
					));
			}
			return 1;
			break;
		case 'country':
		case 'country_code':
			if(preg_match('/char\(2\)/i',$attributes)){
				$id=addDBRecord(array('-table'=>'_fielddata',
					'tablename'		=> $tablename,
					'fieldname'		=> $fieldname,
					'inputtype'		=> "select",
					'required'		=> $required,
					'defaultval'	=> $defaultval,
					'tvals'			=> 'select code from countries order by name,_id',
					'dvals'			=> 'select name from countries order by name,_id'
					));
			}
			return 1;
			break;
		case 'email':
		case 'email_address':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'defaultval'	=> $defaultval,
				'mask'			=> 'email',
				));
			return 1;
			break;
		case 'zip':
		case 'zipcode':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'text',
				'width'			=> '80',
				'inputmax'		=> 15,
				'defaultval'	=> $defaultval
				));
			return 1;
			break;
		case 'active':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'checkbox',
				'defaultval'	=> $defaultval,
				'tvals'			=> '1',
				));
			return 1;
			break;
	}
	//check for tinyint fields
	if(preg_match('/tinyint\(1\)/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'checkbox',
			'defaultval'	=> $defaultval,
			'tvals'			=> 1,
			));
		return 1;
	}
	//check for date and datetime datatypes
	if(preg_match('/datetime/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'datetime',
			'defaultval'	=> $defaultval,
			'tvals'			=> '1',
			));
		return 1;
	}
	elseif(preg_match('/date/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'date',
			'defaultval'	=> $defaultval,
			'tvals'			=> '1',
			));
		return 1;
	}
	return 0;
}
//---------- begin function buildDBPaging
/**
* @describe
* 	builds the html for data paging
*	requires you to pass in the result of getDBPaging so call that first
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBPaging($paging=array()){
	$rtn='';
	global $PAGE;
	//action
	if(isset($paging['-action'])){$action=$paging['-action'];}
	elseif(preg_match('/\.php$/i',$PAGE['name'])){$action="/{$PAGE['name']}";}
	else{$action="/{$PAGE['name']}.phtm";}
	//_search
	if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){$paging['_search']=$_REQUEST['_search'];}
	//_sort
	if(isset($paging['-order'])){$paging['_sort']=$paging['-order'];}
	elseif(isset($_REQUEST['_sort']) && strlen($_REQUEST['_sort'])){$paging['_sort']=$_REQUEST['_sort'];}
	$start=isNum($_REQUEST['start'])?$_REQUEST['start']:0;
	//formname
	if(isset($paging['-formname'])){
		$formname=$paging['-formname'];
		$rtn .= '<input type="hidden" name="_start" value="'.$start.'">'."\n";
	}
	elseif(isset($paging['-ajaxid'])){
		if(isset($paging['-pagingformname'])){$formname=$paging['-pagingformname'];}
		else{$formname='form_' . $paging['-ajaxid'];}
		$onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:"ajaxSubmitForm(this,'{$paging['-ajaxid']}');return false;";
		$rtn .= buildFormBegin($action,array('-name'=>$formname,'-onsubmit'=>$onsubmit,'_start'=>$start));
	}
	else{
		if(isset($paging['-pagingformname'])){$formname=$paging['-pagingformname'];}
		else{$formname='s' . time();}
		$onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:'return true;';
		$rtn .= buildFormBegin($action,array('-name'=>$formname,'-onsubmit'=>$onsubmit,'_start'=>$start));
	}
	//hide other inputs
	$rtn .= '<div style="display:none;" id="inputs">'."\n";
	foreach($paging as $pkey=>$pval){
		if(preg_match('/^\-/',$pkey)){continue;}
		if($pkey=='_action' && $pval=='multi_update'){continue;}
		if(preg_match('/^(x|y)$/i',$pkey)){continue;}
		if(preg_match('/^\_(start|id\_href|search)$/i',$pkey)){continue;}
		if(preg_match('/\_(onclick|href|eval)$/i',$pkey)){continue;}
		$rtn .= '	<textarea name="'.$pkey.'">'.$pval.'</textarea>'."\n";
    	}
    $rtn .= '</div>'."\n";

	//search?
	if(isset($paging['-search'])){
		if(isset($paging['-table'])){
			$fields=getDBFields($paging['-table'],1);
			$indexes=getDBIndexes(array($paging['-table']));
			$opts=array();
			foreach($indexes as $rec){
				if(!in_array($rec['column_name'],$fields)){continue;}
				$tval=$rec['column_name'];
				$dval=str_replace('_',' ',$tval);
				$dval=ucwords(trim($dval));
            	$opts[$tval]=$dval;
			}
			$rtn .= '<table cellspacing="0" cellpadding="0" border="0"><tr>'."\n";
			$rtn .= '	<td>'.buildFormSelect('_searchfield',$opts,array('message'=>"-- Search --")).'</td>'."\n";
			$rtn .= '	<td><input type="text" name="_search" onFocus="this.select();" style="width:200px;" value="'.requestValue('_search').'"></td>'."\n";
			$rtn .= '	<td>'.buildFormSubmit('Search').'</td>'."\n";
			$rtn .= '</tr></table>'."\n";
		}
		else{
        	$rtn .= '<input type="text" name="_search" onFocus="this.select();" style="width:250px;" value="'.requestValue('_search').'">'."\n";
			$rtn .= buildFormSubmit('Search');
		}

		if(isset($paging['-daterange']) && $paging['-daterange']==1){
			$rangeid='dr'.time();
			$rtn .= '<table><tr><td>'."\n";
			$checked=(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1)?' checked':'';
			$rtn .= '<div style="font-size:9pt;"><input type="checkbox" name="date_range" value="1"'.$checked.' onClick="showHide(\''.$rangeid.'\',this.checked);"> Filter by Date Range</div>'."\n";
			if(strlen($checked)){
				$rtn .= '<div style="font-size:9pt;" align="center" id="'.$rangeid.'">'."\n";
				}
			else{
				$rtn .= '<div style="font-size:9pt;display:none;" align="center" id="'.$rangeid.'">'."\n";
	        	}
			$rtn .= '<table>'."\n";
			$rtn .= '	<tr>'."\n";
			if(is_array($paging['-datefield'])){
				$paging['-formname']=$formname;
				$rtn .= '		<td>'.getDBFieldTag($paging['-datefield']).'</td>'."\n";
	        	}
			$rtn .= '		<td>'.getDBFieldTag(array('-formname'=>$formname,'-table'=>'_users','inputtype'=>'date',"-field"=>'_cdate','name'=>'date_from')).'</td>'."\n";
			$rtn .= '		<td>To</td>'."\n";
			$rtn .= '		<td>'.getDBFieldTag(array('-formname'=>$formname,'-table'=>'_users','inputtype'=>'date',"-field"=>'_cdate','name'=>'date_to')).'</td>'."\n";
			$rtn .= '	</tr>'."\n";
			$rtn .= '</table>'."\n";
			$rtn .= '</div>'."\n";
			$rtn .= '</td></tr></table>'."\n";
			}
    	}
    //limit?
    $onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:'';
    if(stringContains($onsubmit,'(this')){
    	$onsubmit=str_replace('(this',"(document.{$formname}",$onsubmit);
	}
	if(isset($paging['-limit'])){
		$rtn .= '<table cellspacing="0" cellpadding="0" border="0" ><tr valign="middle">'."\n";
		$rtn .= '	<th><div style="width:35px;">';
		if(isset($paging['-first'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$arr['_start']=$paging['-first'];
			$rtn .= '<input type="image" onclick="document.'.$formname.'._start.value='.$paging['-first'].';'.$onsubmit.'" src="/wfiles/icons/first.png">'."\n";
            }
        $rtn .= '</div></th>'."\n";
		$rtn .= '	<th><div style="width:35px;">';
		if(isset($paging['-prev'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$rtn .= '<input type="image" onclick="document.'.$formname.'._start.value='.$paging['-prev'].';'.$onsubmit.'" src="/wfiles/icons/prev.png">'."\n";
            }
        $rtn .= '</div></th>'."\n";

        if(isset($paging['-text'])){
            	$rtn .= '		<td align="center"><div class="w_paging">'.$paging['-text'].' records</div></td>'."\n";
			}
        if(isset($paging['-next'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$rtn .= '<td><input type="image" onclick="document.'.$formname.'._start.value='.$paging['-next'].';'.$onsubmit.'" src="/wfiles/icons/next.png"></td>'."\n";
            }
        if(isset($paging['-last'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$rtn .= '<td><input type="image" onclick="document.'.$formname.'._start.value='.$paging['-last'].';'.$onsubmit.'" src="/wfiles/icons/last.png"></td>'."\n";
            }
        $rtn .= '</tr></table>'."\n";
		}
	if(!isset($paging['-formname'])){
		$rtn .= buildFormEnd();
		}
	if(isset($paging['-search'])){
		$rtn .= buildOnLoad("document.{$formname}._search.focus();");
	}
	//$rtn .= printValue($_REQUEST);
	return $rtn;
	}
//---------- begin function buildDBProgressChart
/**
* @describe builds an html progress chart based on database values
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBProgressChart($params=array()){
	if(!isParams(array('-table','-field'),$params)){return "buildDBProgressChart Error: missing required param";}
	$sum=setValue($params['-sum'],"sum({$params['-field']})");
	$where=buildDBWhere($params);
	//return $target . '<hr>' . $where;
	$query="select {$sum} as qsum from {$params['-table']} $where";
	$recs=getDBRecords(array('-query'=>$query));
	$sum=0;
	if(is_array($recs)){
		if(isParams(array('error','query'),$recs)){return printValue($recs);}
		foreach($recs as $rec){$sum += $rec['qsum'];}
    	}
    //build the progress control
    $rtn='';
    $background=setValue($params['-background'],'#c4c5c6');
    $forground=setValue($params['-forground'],'#007100');
    $color=setValue($params['-color'],'#FFFFFF');
	$border=setValue($params['-border'],'1px solid #000000');
	$direction=strtolower(setValue($params['-direction'],'horizontal'));
	$format=setValue($params['-format'],'integer');
	$id=setValue($params['-id'],'w_progresschart');
	if($direction=='horizontal'){
		//horizontal bar
		$height=setValue($params['-height'],20);
		$width=setValue($params['-width'],200);
		//if target is specified
		$rtn .= '<div id="'.$id.'">'."\n";
		$rtn .= '	<div>'."\n";
		if(isset($params['-addlink'])){
			$rtn .= '		<a title="Add" href="#" onclick="'.$params['-addlink'].'return false;"><img src="/wfiles/iconsets/16/plus.png" border="0"></a>'."\n";
			}
		if(isset($params['-listlink'])){
			$rtn .= '		<a title="List" href="#" onclick="'.$params['-listlink'].'return false;"><img src="/wfiles/iconsets/16/list.png" border="0"></a>'."\n";
			}
		if(isset($params['-title'])){$rtn .= '		<b>'.$params['-title'].'</b>'."\n";}
		$rtn .= '	</div>'."\n";
		if(isNum($params['-target'])){
			$pcnt=round(($sum/$params['-target']),2);
			$pwidth=round($pcnt*$width,0);
			if($format=='money'){$sum="$" . formatMoney($sum);}
			$rtn .= '<div style="position:relative;width:'."{$width}px;height:{$height}px;border:{$border};background:{$background};".'">'."\n";
			$rtn .= '	<div style="position:absolute;left:0px;bottom:0px;width:'."{$pwidth}px;background:{$forground};height:{$height}px;color:{$color}".'" align="center">'.$sum.'</div>'."\n";
			$rtn .= '</div>'."\n";
			}
		else{
			$pcnt=round(($sum/$width),2);
			$pwidth=round($pcnt*$width,0);
			$rtn .= '	<div style="width:'."{$pwidth}px;background:{$forground};height:{$height}px;".'"></div>'."\n";
			}
		$rtn .= '</div>'."\n";
    	}
    else{
		//vertical bar
		$height=setValue($params['-height'],200);
		$width=setValue($params['-width'],20);
		$rwidth=$width+20;
		$rtn .= '<div id="'.$id.'" style="position:relative;width:'.$rwidth.'px;height:'.$height.'px;">'."\n";
		$bottom=0;
		if(isset($params['-listlink'])){
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:18px;width:18px;"><a title="List" href="#" onclick="'.$params['-listlink'].'return false;"><img src="/wfiles/iconsets/16/list.png" border="0"></a></div>'."\n";
			$bottom+=18;
			}
		if(isset($params['-addlink'])){
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:18px;width:18px;"><a title="Add" href="#" onclick="'.$params['-addlink'].'return false;"><img src="/wfiles/iconsets/16/plus.png" border="0"></a></div>'."\n";
			$bottom+=18;
			}
		if(isset($params['-title'])){
			$pheight=$height-$bottom;
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:'.$pheight.'px;width:18px;"><b class="w_rotatetext w_bold">'.$params['-title'].'</b></div>'."\n";
			$bottom+=18;
			}
		//if target is specified
		if(isNum($params['-target'])){
			$pcnt=round(($sum/$params['-target']),2);
			$pheight=round($pcnt*$height,0);
			$rtn .= '<div id="progresscontrol" style="position:absolute;bottom:0px;left:20px;width:'."{$width}px;height:{$height}px;border:{$border};background:{$background};".'">'."\n";
			$rtn .= '	<div style="position:absolute;bottom:0px;left:0px;width:'."{$width}px;background:{$forground};height:{$pheight}px;".'"></div>'."\n";
			$rtn .= '</div>'."\n";
			}
		else{
			$pcnt=round((($sum/$width)*100),2);
			$pwidth=round($pcnt*$width,0);
			$rtn .= '	<div style="width:'."{$width}px;background:{$forground};height:{$pheight}px;".'"></div>'."\n";
			}
		$rtn .= '</div>'."\n";
    	}
    return $rtn;
	}
//---------- begin function buildDBWhere
/**
* @describe returns the where clause including the where text based on params.
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBWhere($params=array()){
	$info=getDBFieldInfo($params['-table']);
	$query='';
	if(isset($params['-where'])){$query .= $params['-where'];}
	else{
		$ands=array();
		/* Filter the query based on params */
		foreach($params as $key=>$val){
			//skip keys that begin with a dash
			if(preg_match('/^\-/',$key)){continue;}
			if(!isset($info[$key]['_dbtype'])){continue;}
			if($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='real'){
				$ands[] = "$key=$val";
				}
			else{
				//like
				$ands[] = "{$key} = '{$val}'";
	        	}
	    	}
	    if(count($ands)){$query .= implode(" and ",$ands);}
	    }
    if(isset($params['-search'])){
		if(preg_match('/^where (.+)/i',$params['-search'],$smatch)){
			$query .= ' and ('.$smatch[1].')';
            }
		else{
			$ors=array();
			foreach(array_keys($info) as $field){
				if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
					if(isNum($params['-search'])){}
                	}
                else{array_push($ors,"{$field} like '%{$params['-search']}%'");}
				}
			if(count($ors)){
				$query .= ' and ('.implode(' or ',$ors).')';
            	}
            }
        }
    if(strlen($query)){$query=" where {$query}";}
    return $query;
	}
//---------- begin function delDBRecord--------------------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
*	[-model] boolean - set to false to disable model functionality
* @return boolean
* @usage
*	$id=delDBRecord(array(
*		'-table'		=> '_tabledata',
*		'-where'		=> "_id=4"
*	));
*/
function delDBRecord($params=array()){
	$function='delDBRecord';
	if(!isset($params['-table'])){return 'editDBRecord Error: No table';}
	if(!isset($params['-where'])){return 'editDBRecord Error: No where';}
	$table=$params['-table'];
	//model
	if(!isset($params['-model']) || ($params['-model'])){
		$model=getDBTableModel($table);
		$model_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$model_table=implode('.',$table_parts);
	}
	//echo printValue($model);exit;
	if(isset($model['functions']) && strlen(trim($model['functions']))){
    	$ok=includePHPOnce($model['functions'],"{$model_table}-model_functions");
    	//look for Before trigger
    	if(function_exists("{$model_table}DeleteBefore")){
			$model['check']=1;
        	$params=call_user_func("{$model_table}DeleteBefore",$params);
        	if(!isset($params['-table'])){return "{$model_table}DeleteBefore Error: No Table".printValue($params);}
		}
	}
	if(isMssql()){$table="[{$table}]";}
	$query="delete from {$table} where " . $params['-where'];
	// execute sql - return the number of rows affected
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
		databaseFreeResult($query_result);
		if(!isset($params['-nolog']) || $params['-nolog'] != 1){logDBQuery($query,$start,$function,$params['-table']);}
    	if(isset($model['functions'])){
	    	//look for Success trigger
	    	if(function_exists("{$model_table}DeleteSuccess")){
	        	$params=call_user_func("{$model_table}DeleteSuccess",$params);
			}
		}
    	return true;
  		}
  	else{
		$error=getDBError();
		if(isset($model['functions'])){
	    	//look for Failure trigger
	    	if(function_exists("{$model_table}DeleteFailure")){
				$params['-error']="No updates found";
	        	$params=call_user_func("{$model_table}DeleteFailure",$params);
			}
		}
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function dropDBTable--------------------
/**
* @describe drops the specified table
* @param table string - name of table to drop
* @param [meta] boolean - also remove metadata in _fielddata and _tabledata tables associated with this table. defaults to false
* @return 1
* @usage $ok=dropDBTable('comments',1);
*/
function dropDBTable($table='',$meta=0){
	if(!isDBTable($table)){return "No such table: {$table}";}
	if(isMssql()){$table="[{$table}]";}
	$result=executeSQL("drop table {$table}");
	if(isset($result['error'])){
		return $result['error'];
        }
    if($meta){
		$ok=delDBRecord(array('-table'=>'_tabledata','-where'=>"tablename = '{$table}'"));
		$ok=delDBRecord(array('-table'=>"_fielddata",'-where'=>"tablename = '{$table}'"));
    	}
    return 1;
	}
//---------- begin function dumpDB--------------------
/**
* @describe performs a mysqldump and saves file in the sh/backups directory
* @param [table] string - name of table to limit dump to
* @return array - $dump['success'] on success
* @usage $dump=dumpDB();
*/
function dumpDB($table=''){
	global $CONFIG;
	$dump=array();
	$dump['path']=getWasqlPath('sh/backups');
	if(!is_dir($dump['path'])){buildDir($dump['path']);}
	$dump['file'] = $CONFIG['dbname'].'__' . date("Y-m-d_H-i-s")  . '.sql';
	$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
	if(isMysql() || isMysqli()){
		//mysqldump
		$dump['command'] = isWindows()?"mysqldump.exe":"mysqldump";
		$dump['command'] .= " --host={$CONFIG['dbhost']}";
		if(strlen($CONFIG['dbuser'])){
			$dump['command'] .= " --user={$CONFIG['dbuser']}";
			}
		if(strlen($CONFIG['dbuser'])){
			$dump['command'] .= " --password={$CONFIG['dbpass']}";
			}
		$dump['command'] .= " --max_allowed_packet=128M {$CONFIG['dbname']}";
		if(strlen($table)){
			$dump['command'] .= " {$table}";
			$dump['file'] = $CONFIG['dbname'].'.'.$table.'_' . date("Y-m-d_H-i-s")  . '.sql';
			$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
		}
	}
	elseif(isPostgreSQL()){
    	//PostgreSQL - pg_dump dbname > outfile
    	$dump['command'] = isWindows()?"pg_dump.exe":"pg_dump";
		$dump['command'] .= " -h {$CONFIG['dbhost']}";
		if(strlen($table)){
			$dump['command'] .= " -t {$table}";
			$dump['file'] = $CONFIG['dbname'].'.'.$table.'_' . date("Y-m-d_H-i-s")  . '.sql';
			$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
		}
	}
	if(!isWindows() || $CONFIG['gzip']=1){
    	$dump['command'] .= " | gzip -9";
    	$dump['afile']=preg_replace('/\.sql$/i','.sql.gz',$dump['afile']);
	}
	$dump['command'] .= "  > \"{$dump['afile']}\"";
	$dump['result']=cmdResults($dump['command']);
/* 		
	ob_start();
	passthru($dump['command']);
	$dump['result'] = ob_get_contents();
*/
	//echo printValue($dump);
	if(is_file($dump['afile']) && !filesize($dump['afile'])){
    	unlink($dump['afile']);
	}
	if(is_file($dump['afile'])){
		$sql=getFileContents($dump['afile']);
		if(preg_match('/^Usage\:/i',$sql)){$dump['error']=$sql;}
		else{$dump['success']=1;}
		}
	else{$dump['error']='Unable to create database dump.';}
	return $dump;
	}
//---------- begin function dumpDB--------------------
/**
* @describe performs a mysqlcheck -o -v -h
* @return array
* @usage $ok=optimizeDB();
*/
function optimizeDB(){
	if(!isMysql() && !isMysqli()){
		//only supported in Mysql
		return false;
		}
	global $CONFIG;
	$progpath=dirname(__FILE__);
	$rtn=array();
	$rtn['command'] = "mysqlcheck -o -v -h {$CONFIG['dbhost']}";
	if(strlen($CONFIG['dbuser'])){
		$rtn['command'] .= " -u {$CONFIG['dbuser']}";
		}
	if(strlen($CONFIG['dbuser'])){
		$rtn['command'] .= " -p{$CONFIG['dbpass']}";
		}
	$rtn['command'] .= " {$CONFIG['dbname']}";
	ob_start();
	passthru($rtn['command']);
	$rtn['result'] = ob_get_contents();
	ob_end_clean();
	return $rtn;
	}
//---------- begin function editDBRecord--------------------
/**
* @describe adds record to table and returns the ID of record added
* @param params array
*	-table string - name of table
*	-where string - where clause to determine what record(s) to edit
*	[-model] boolean - set to false to disable model functionality
*	treats other params as field/value pairs to edit
* @return array
* @usage
*	<?php
*	$ok=editDBRecord(array(
*		'-table'	=> 'notes',
*		'-where'	=> "_id=3",
*		'title'		=> 'Test Note Title',
*		'category'	=> 'QA'
*	));
*	?>
*/
function editDBRecord($params=array()){
	$function='editDBRecord';
	if(!isset($params['-table'])){return 'editDBRecord Error: No table <br>' . printValue($params);}
	if(!isset($params['-where'])){return 'editDBRecord Error: No where <br>' . printValue($params);}
	global $USER;
	$table=$params['-table'];
	//model
	if(!isset($params['-model']) || ($params['-model'])){
		$model=getDBTableModel($table);
		$model_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$model_table=implode('.',$table_parts);
	}
	if(isset($model['functions']) && strlen(trim($model['functions']))){
    	$ok=includePHPOnce($model['functions'],"{$model_table}-model_functions");
    	//look for Before trigger
    	if(function_exists("{$model_table}EditBefore")){
			$model['check']=1;
        	$params=call_user_func("{$model_table}EditBefore",$params);
        	if(!isset($params['-table'])){return "{$model_table}EditBefore Error: No Table".printValue($params);}
		}
	}
	//get field info for this table
	$info=getDBFieldInfo($params['-table']);
	if(!isset($params['-noupdate'])){
		if(isset($info['_euser'])){
			$params['_euser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
	    	}
	    if(isset($info['_edate'])){
			$params['_edate']=date("Y-m-d H:i:s");
	    	}
		}
	/* Filter the query based on params */
	$updates=array();
	foreach($params as $key=>$val){
		//ignore params that do not match a field
		if(!isset($info[$key]['_dbtype'])){continue;}
		//skip keys that begin with a dash
		if(preg_match('/^\-/',$key)){continue;}
		if(!is_array($val) && preg_match('/^<sql>(.+)<\/sql>$/i',$val,$pm)){
			array_push($updates,"{$key}={$pm[1]}");
			}
		else{
			if(isset($info[$key.'_size'])){$opts[$field.'_size']=setValue(array($_REQUEST[$field.'_size'],strlen($_REQUEST[$field])));}
			if(($info[$key]['_dbtype']=='date')){
				if(!strlen(trim($val))){$val='NULLDATE';}
				elseif(preg_match('/^([0-9]{2,2})-([0-9]{2,2})-([0-9]{4,4})$/s',$val,$dmatch)){
					$val=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
					}
				}
			elseif($info[$key]['_dbtype'] =='time'){
				if(!is_array($val) && !strlen(trim($val))){$val='NULLDATE';}
				elseif(is_array($val)){
					if($val[2]=='pm' && $val[0] < 12){$val[0]+=12;}
					elseif($val[2]=='am' && $val[0] ==12){$val[0]='00';}
					$val="{$val[0]}:{$val[1]}:00";
	            	}
	            elseif(preg_match('/([0-9]{1,2}?)\:([0-9]{1,2}?)\:(am|pm)/i',$val,$tmatch)){
					if(strtolower($tmatch[3])=='pm' && $tmatch[1] < 12){$tmatch[1]+=12;}
					elseif(strtolower($tmatch[3])=='am' && $tmatch[1] == 12){$tmatch[1]='00';}
					$val="{$tmatch[1]}:{$tmatch[2]}:00";
	            	}
				}
			elseif($info[$key]['_dbtype'] =='datetime'){
				$newval='';
				unset($dmatch);
				if(!is_array($val) && !strlen(trim($val))){$newval='NULLDATE';}
				elseif(is_array($val)){
					if(preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})$/s',$val[0],$dmatch)){
						if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
						if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
						$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
					}
					else{$newval=$val[0];}
					if($val[3]=='pm' && $val[1] < 12){$val[1]+=12;}
					elseif($val[3]=='am' && $val[1] ==12){$val[1]='00';}
					if(!strlen(trim($val[1])) && !strlen(trim($val[2]))){$newval='NULLDATE';}
					else{
						$newval .=" {$val[1]}:{$val[2]}:00";
					}
	            }
	            //2011-07-19 20:52:59
	            elseif(preg_match('/([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})\ ([0-9]{1,2}?)\:([0-9]{1,2}?)\:(am|pm)/i',$val,$dmatch)){
					if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
					if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
					$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
					if(strtolower($tmatch[6])=='pm' && $tmatch[4] < 12){$tmatch[4]+=12;}
					elseif(strtolower($tmatch[6])=='am' && $tmatch[4] == 12){$tmatch[4]='00';}
					$newval .=" {$tmatch[4]}:{$tmatch[5]}:00";
	            	}
	            else{$newval=$val;}
	            $val=$newval;
				}
			if($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='tinyint' || $info[$key]['_dbtype'] =='real'){
				if(is_array($val)){$val=(integer)$val[0];}
				if(strlen($val)==0){
					if(isset($info[$key]['_dbflags']) && strlen($info[$key]['_dbflags']) && stristr("not_null",$info[$key]['_dbflags'])){$val=0;}
					else{$val='NULL';}
					}
				array_push($updates,"$key=$val");
				}
			else{
				if(is_array($val)){$val=implode(':',$val);}
				$val=databaseEscapeString($val);
				if(strlen($val)==0){$val='NULL';}
				if($val=='NULLDATE' || $val=='NULL'){array_push($updates,"$key=NULL");}
				else{array_push($updates,"$key='$val'");}
	        	}
	        //add sha and size if needed
	        if(isset($info[$key.'_sha1']) && !isset($params[$key.'_sha1'])){
				$sha=sha1($val);
				array_push($updates,"{$key}_sha1='{$sha}'");
				}
			if(isset($info[$key.'_size']) && !isset($params[$key.'_size'])){
				$size=strlen($val);
				array_push($updates,"{$key}_size={$size}");
				}
	        }
    	}
    //return if no updates were found
	if(!count($updates)){
		if(isset($model['functions'])){
	    	//look for Failure trigger
	    	if(function_exists("{$model_table}EditFailure")){
				$params['-error']="No updates found";
	        	$params=call_user_func("{$model_table}EditFailure",$params);
			}
		}
		return 0;
	}
    $fieldstr=implode(",",$updates);
    $table=$params['-table'];
    if(isMssql()){$table="[{$table}]";}
	$query="update {$table} set $fieldstr where " . $params['-where'];
	if(isset($params['-limit'])){$query .= ' limit '.$params['-limit'];}
	// execute sql - return the number of rows affected
	if(isset($params['-echo'])){echo $query;}
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
    	$id=databaseAffectedRows($query_result);
    	databaseFreeResult($query_result);
    	logDBQuery($query,$start,$function,$params['-table']);
    	//addDBHistory('edit',$params['-table'],$params['-where']);
    	if(isset($model['functions'])){
	    	//look for Success trigger
	    	if(function_exists("{$model_table}EditSuccess")){
				$params['-records']=getDBRecords(array('-table'=>$table,'-where'=>$params['-where']));
	        	$params=call_user_func("{$model_table}EditSuccess",$params);
			}
		}
    	return $id;
  		}
  	else{
		$error=getDBError();
		if(isset($model['functions'])){
	    	//look for Failure trigger
	    	if(function_exists("{$model_table}EditFailure")){
				$params['-error']=$error;
	        	$params=call_user_func("{$model_table}EditFailure",$params);
			}
		}
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function editDBUser--------------------
/**
* @describe edits the specified _user record with specified parameters
* @param id integer - _user record ID to edit
* @param params array - field/value pairs to edit
* @return boolean
* @usage $ok=editDBUser(34,array('lastname'=>'Smith'));
*/
function editDBUser($id='',$opts=array()){
	if(isNum($id)){
		$editopts=array('-table'=>'_users','-where'=>"_id={$id}");
    	}
    else{
		$editopts=array('-table'=>'_users','-where'=>"username = '{$id}'");
    	}
    foreach($opts as $key=>$val){$editopts[$key]=$val;}
    return editDBRecord($editopts);
	}
//---------- begin function executeSQL--------------------
/**
* @describe execute a SQL statement. returns an array with 'error' on failure
* @param query string - query to execute
* @return array
* @usage $ok=executeSQL($query);
*/
function executeSQL($query=''){
	$rtn=array();
	$rtn['query'] = '<div style="font-size:9pt;margin-left:15px;"><pre><xmp>'.$query.'</xmp></pre></div>'."\n";
	$function='executeSQL';
	$query_result=@databaseQuery($query);
  	if($query_result){
		$rtn['result']=$query_result;
		return $rtn;
  		}
  	else{
		//echo $query.printValue($query_result).getDBError();exit;
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function expandDBKey
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function expandDBKey($key='',$val='',$table='default'){
	if(!strlen(trim($key))){return null;}
	if(!strlen(trim($val))){return null;}
	$cachekey=$key.$val.$table;
	if(isset($_SERVER['_cache_']['expandDBKey'][$cachekey])){
		return $_SERVER['_cache_']['expandDBKey'][$cachekey];
		}
	unset($rmatch);
	if($table != 'default'){
		unset($tmp);
		$tmp=getDBRecord(array('-table'=>$table,'_id'=>$val));
		if(is_array($tmp)){
			foreach($tmp as $tkey=>$tval){
				if(preg_match('/^\_/',$tkey) || preg_match('/^(password|apikey|guid|utype)$/i',$tkey) || !strlen($tval)){unset($tmp[$tkey]);}
				}
			$tmp['_table']=$table;
			ksort($tmp);
			$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
			return $tmp;
			}
        }
	elseif(preg_match('/^(.+?)\_id$/',$key,$rmatch)){
		$rfield=$rmatch[1];
		$rfield2="{$rfield}s";
		$rfield3=preg_replace('/y$/i','ies',$rfield);
		$rfield4="_{$rfield}s";
		$list[$x][$key.'_ex']=$related[$key][$val];
		if(isDBTable($rfield)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif(isDBTable($rfield2)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield2,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield2=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield2;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif($rfield3 !== $rfield && isDBTable($rfield3)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield3,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield3=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield3;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif(isDBTable($rfield4)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield4,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield4=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield4;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        }
    elseif(preg_match('/^\_(cuser|euser)$/',$key,$rmatch)){
		unset($tmp);
		$tmp=getDBRecord(array('-table'=>'_users','_id'=>$val));
		if(is_array($tmp)){
			foreach($tmp as $tkey=>$tval){
				if(preg_match('/^\_/',$tkey) || preg_match('/^(password|apikey|guid|utype)$/i',$tkey) || !strlen($tval)){unset($tmp[$tkey]);}
				}
			$tmp['_table']="_users";
			ksort($tmp);
			$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
			return $tmp;
			}
        }
    return null;
	}
//---------- begin function exportDBRecords ----------
/**
* @describe exports getDBRecords results into csv, tab, or xml format
* @param param array - same parameters as getDBRecords except for:
*	-format - csv,tab, or xml - defaults to csv
*	-filename - name of the exported file  - defaults to output
* @return file
*	file pushed to client
* @usage $ok=exportDBRecords(array('-table'=>"_users",'active'=>1));
*/
function exportDBRecords($params=array()){
	global $PAGE;
	global $USER;
	global $CONFIG;
	$idfield=isset($params['-id'])?$params['-id']:'_id';
    //determine sort
    $possibleSortVals=array($params['-order'],$params['-orderby'],$_REQUEST['_sort'],'none');
    $sort=setValue($possibleSortVals);
    if($sort=='none'){
		$sort='';
    	if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(isAdmin()){
				if(is_array($tinfo['sortfields'])){$sort=implode(',',$tinfo['sortfields']);}
				}
			else{
				if(is_array($tinfo['sortfields_mod'])){$sort=implode(',',$tinfo['sortfields_mod']);}
            	}
			}
		}
	if(strlen($sort)){$params['-order']=$sort;}
	if(isset($_REQUEST['_sort']) && !strlen(trim($_REQUEST['_sort']))){unset($_REQUEST['_sort']);}
	if(isset($_REQUEST['_sort'])){$params['-order']=$_REQUEST['_sort'];}
	if(isset($params['-list']) && is_array($params['-list'])){$list=$params['-list'];}
	else{
		if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
			$params['-search']=$_REQUEST['_search'];
        	}
        if(isset($_REQUEST['_searchfield']) && strlen($_REQUEST['_searchfield'])){
			$params['-searchfield']=$_REQUEST['_searchfield'];
        	}
        if(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1){
			$filterfield="_cdate";
			$wheres=array();
			if(isset($_REQUEST['date_from'])){
				$sdate=date2Mysql($_REQUEST['date_from']);
				$wheres[] = "DATE({$filterfield}) >= '{$sdate}'";
				}
			if(isset($_REQUEST['date_to'])){
				$sdate=date2Mysql($_REQUEST['date_to']);
				$wheres[] = "DATE({$filterfield}) <= '{$sdate}'";
				}
			$opts=array('_formname'=>"ticket");
			if(count($wheres)){
				$params['-where']=implode(' and ',$wheres);
				}
        	}
        if(!isset($_REQUEST['_sort']) && !isset($_REQUEST['-order']) && !isset($params['-order'])){
			$params['-order']="{$idfield} desc";
        	}
		if(!isset($params['-fields']) && isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(is_array($tinfo)){
				$xfields=array();
				if(isset($tinfo['listfields']) && is_array($tinfo['listfields'])){
					$xfields=$tinfo['listfields'];
					}
				elseif(isset($tinfo['default_listfields']) && is_array($tinfo['default_listfields'])){
					$xfields=$tinfo['default_listfields'];
					}
				if(count($xfields)){
					array_unshift($xfields,$idfield);
					$params['-fields']=implode(',',$xfields);
					}
	            }
	        if(isset($params['-fields'])){
				$params['-fields']=preg_replace('/\,+$/','',$params['-fields']);
				$params['-fields']=preg_replace('/^\,+/','',$params['-fields']);
	        	$params['-fields']=preg_replace('/\,+/',',',$params['-fields']);
				}
	        }
		$list=getDBRecords($params);
		}
	if(isset($list['error'])){
		echo $list['error'];
		exit;
		}
	if(!is_array($list)){
		$no_records=isset($params['-norecords'])?$params['-norecords']:'No records found';
		echo $no_records;
		exit;
		}
	$filename='output';
	if(isset($params['-filename'])){$filename=$params['-filename'];}
	elseif(isset($params['-table'])){$filename=$params['-table'].'_output';}
	if(!isset($params['-format'])){$params['-format']='csv';}
	//echo "here".printValue($params).printValue($list);
	switch(strtolower($params['-format'])){
    	case 'tab':
    		$filename.='.txt';
    		$data=arrays2TAB($list,$params);
    		break;
    	case 'xml':
    		$filename.='.xml';
    		$data=arrays2XML($list,$params);
    		break;
    	default:
    		//default to csv
    		$filename.='.csv';
    		$data=arrays2CSV($list,$params);
    		break;
	}
	pushData($data,$params['-format'],$filename);
	return;
}

//---------- begin function getDBTableModel
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBTableModel($table){
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	$originaltable=$table;
	$model_table='_models';
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$table=implode('.',$table_parts);
		$model_table="{$dbname}._models";
		}
	if(!isDBTable($model_table)){return null;}
	if(isset($_SERVER['_cache_']['getDBTableModel'][$originaltable]['name'])){
		return $_SERVER['_cache_']['getDBTableModel'][$originaltable];
	}
	$recopts=array('-table'=>$model_table,'name'=>$table,'active'=>1);
	$rec=getDBRecord($recopts);
	//echo printValue($recopts).printValue($rec)."<hr>\n";
	$_SERVER['_cache_']['getDBTableModel'][$originaltable]=$rec;

	return $rec;
}

//---------- begin function getDBAdminSettings
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBAdminSettings(){
	$setfield='_admin_settings_';
	$settings=getDBSettings($setfield,'-1',1);
	//Defaults
	$defaults=getDBAdminDefaultSettings();
	foreach($defaults as $key=>$val){
		if(!strlen($settings[$key])){$settings[$key]=$val;}
	}
	//mainmenu
	$settings['mainmenu_icons']=1;
	$settings['mainmenu_text']=1;
	if(is_array($settings['mainmenu_toggle'])){
		if(!in_array('TXT',$settings['mainmenu_toggle'])){$settings['mainmenu_text']=0;}
		if(!in_array('ICO',$settings['mainmenu_toggle'])){$settings['mainmenu_icons']=0;}
	}
	elseif(strlen($settings['mainmenu_toggle'])){
		if($settings['mainmenu_toggle']=='TXT'){$settings['mainmenu_icons']=0;}
		if($settings['mainmenu_toggle']=='ICO'){$settings['mainmenu_text']=0;}
	}
	elseif(is_array($ConfigSettings) && !isset($settings['mainmenu_toggle'])){
		$settings['mainmenu_text']=0;
		$settings['mainmenu_icons']=0;
	}
	//actionmenu
	$settings['actionmenu_text']=1;
	$settings['actionmenu_icons']=1;
	if(is_array($settings['actionmenu_toggle'])){
		if(!in_array('TXT',$settings['actionmenu_toggle'])){$settings['actionmenu_text']=0;}
		if(!in_array('ICO',$settings['actionmenu_toggle'])){$settings['actionmenu_icons']=0;}
	}
	elseif(strlen($settings['actionmenu_toggle'])){
		if($settings['actionmenu_toggle']=='TXT'){$settings['actionmenu_icons']=0;}
		if($settings['actionmenu_toggle']=='ICO'){$settings['actionmenu_text']=0;}
	}
	elseif(is_array($settings) && !isset($settings['actionmenu_toggle'])){
		$settings['actionmenu_text']=0;
		$settings['actionmenu_icons']=0;
	}
	return $settings;
}
//---------- begin function getDBAdminDefaultSettings
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBAdminDefaultSettings(){
	$settings=array(
		'mainmenu_fade_color_top'	=> '#d6e2f8',
		'mainmenu_fade_color_bot'	=> '#a4bfee',
		'mainmenu_border_color_bot'	=> '#7a93df',
		'mainmenu_text_color'		=> '#3465a4',
		'mainmenu_hover_background'	=> '#e4eaed',
		'mainmenu_toggle'			=> 'ICO',
		'logo'						=> '/wfiles/iconsets/16/webpage.png',
		'logo_width'				=> 16,
		'logo_height'				=> 16,
		'logo_text'					=> $_SERVER['HTTP_HOST'],
		'actionmenu_toggle'			=> 'ICO',
		'actionmenu_text_color'		=> '#3465a4',
		'actionmenu_hover_background'=>'#e4eaed',
		'table_even_background'		=> '#f0f3f4',
		'table_header_text'			=> '#ffffff',
		'table_header_background'	=> '#3465a4',
		'content_position'			=> 'full',
		);
	return $settings;
}
//---------- begin function getDBCharsets ----------
/**
* @describe returns an array of all available charsets in current database
* @return array
* @usage $charsets=getDBCharsets();
*/
function getDBCharsets(){
	global $databaseCache;
	if(isset($databaseCache['getDBCharsets'])){return $databaseCache['getDBCharsets'];}
	$recs=getDBRecords(array('-query'=>"show character set"));
	//echo printValue($recs);
	$charsets=array();
	foreach($recs as $rec){
		$set=$rec['charset'];
		$charsets[$set]=$rec['description'];
    	}
    $databaseCache['getDBCharsets']=$charsets;
    return $charsets;
	}
//---------- begin function getDBCharset--------------------
/**
* @describe returns the current default charset of the database
* @return string
* @usage $charset=getDBCharset()
*/
function getDBCharset(){
	global $databaseCache;
	if(isset($databaseCache['getDBCharset'])){return $databaseCache['getDBCharset'];}
	global $CONFIG;
	if(isMysql() || isMysqli()){
		$recs=getDBRecords(array('-query'=>"SHOW CREATE DATABASE {$CONFIG['dbname']}"));
		if(count($recs)==1 && preg_match('/DEFAULT CHARACTER SET(.+)/i',$recs[0]['create database'],$chmatch)){
			$charset=trim($chmatch[1]);
			$charset=preg_replace('/[\s\*\/]+$/','',$charset);
			$databaseCache['getDBCharset']=$charset;
			return $charset;
	    }
	}
	$databaseCache['getDBCharset']='unknown';
    return "unknown";
}
//---------- begin function  ----------
/**
* @describe returns number of records that match params criteria
* @param param array
* @return integer - number of records
* @usage $ok=getDBCount(array('-table'=>$table,'field1'=>$val1...))
*/
function getDBCount($params=array()){
	$function='getDBCount';
	$cnt=0;
	if(isset($params['-table'])){
		$params['-fields']="count(*) as cnt";
		unset($params['-order']);
		$query=getDBQuery($params);
		$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1));
		if(!isset($recs[0]['cnt'])){
			debugValue($recs);
			return 0;
		}
		$cnt=$recs[0]['cnt'];
    	}
    elseif(isset($params['-countquery'])){
		$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1));
		if(!is_array($recs)){return $recs;}
		if(isset($recs[0]['cnt'])){
			$cnt=$recs[0]['cnt'];
		}
		elseif(isset($recs[0]['count'])){
			$cnt=$recs[0]['count'];
		}
		elseif(isset($recs[0])){
			foreach($recs[0] as $key=>$val){
            	$cnt=$val;
            	break;
			}
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		// Perform Query
		$start=microtime(true);
		$query_result=@databaseQuery($query);
		if($query_result){
    		$cnt=databaseNumRows($query_result);
    		databaseFreeResult($query_result);
    		logDBQuery($query,$start,$function,$params['-table']);
			}
		}
    return $cnt;
	}
//---------- begin function getDBError
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBError(){
	return databaseError();
	}
//---------- begin function getDBFieldMeta--------------------
/**
* @describe returns the meta data from the _fielddata table for said table and fields
* @param table string - table name
* @param [fields] string - comma separated list of fields to return - defaults to blank
* @param [fieldname] string - specific field to retrieve only - defaults to blank
* @return array
* @usage $fields=getDBFieldMeta('notes');
*/
function getDBFieldMeta($table,$fields='',$fieldname=''){
	global $databaseCache;
	$dbcachekey=strtolower($table.'_'.$fields.'_'.$fieldname);
	if(isset($databaseCache['getDBFieldMeta'][$dbcachekey])){return $databaseCache['getDBFieldMeta'][$dbcachekey];}
	$getrecs=array(
		'-table'=>"_fielddata",
		'-index'=>'fieldname',
		'-where'=>"tablename='{$table}'",
		'-notimestamp'=>1
		);
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	$originaltable=$table;
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$getrecs['-table']="{$dbname}._fielddata";
		$table=implode('.',$table_parts);
		$getrecs['-where']="tablename='{$table}'";
		}
	if(strlen($fields)){
		//make sure fieldname is in the fields list so we can index by it
		$list=preg_split('/[,;]+/',$fields);
		if(!in_array('fieldname',$list)){$list[]='fieldname';}
    	$getrecs['-fields']=implode(',',$list);
	}
	if(strlen($fieldname)){
    	$getrecs['-where']="tablename='{$table}' and fieldname='{$fieldname}'";
	}
	$rtn = getDBRecords($getrecs);
	$databaseCache['getDBFieldMeta'][$dbcachekey]=$rtn;
	return $rtn;
}
//---------- begin function getDBFieldTag
/**
* @describe returns the HTML tag associated with this field. Other tag attributes can be passed in to override
* @param params array - requires either -list or -table or -query
*	-table string - table name
*	-field string - field name
*	[-formname] -  name of the parent form tag
*	other field/value pairs override defaults in the _fielddata table
* @return string - html tag to display
* @usage
*	<?=getDBFieldTag('-table'=>'notes','-field'=>'comments','width'=>'400'));?> display the comments textarea field and override the default width
*/
function getDBFieldTag($params=array()){
    if(!isset($params['-table'])){return 'getDBFieldTag Error: No table' . printValue($params);}
    if(!isDBTable($params['-table'])){return 'getDBFieldTag Error: table does not exist' . printValue($params);}
    if(!isset($params['-field'])){return 'getDBFieldTag Error: No field for '.$params['-table'] . printValue($params);}
    $field=$params['-field'];
    //get the information from the db
    //Valid _dbtype values: string, real, int, date, time, datetime, blob
    $info=array();
    $info[$field]=array();
    $info=getDBFieldInfo($params['-table'],1,$field);
    if(!is_array($info) && strlen($info)){return $info;}
    //echo printValue($info);
    //echo printValue($params);
    $styles=array();
    //overrides that are passed in
    foreach($params as $key=>$val){
		if(preg_match('/^\-/',$key)){continue;}
		$info[$field][$key]=$val;
    	}
    //set value
    if(isset($params['value'])){$info[$field]['value']=$params['value'];}
	else{
		$vfield=(isset($params['name']) && strlen($params['name']))?$params['name']:$field;
		if(isset($_REQUEST[$vfield])){$info[$field]['value']=$_REQUEST[$vfield];}
		elseif(isset($_SESSION[$vfield])){$info[$field]['value']=$_SESSION[$vfield];}
		elseif(isset($params['defaultval'])){$info[$field]['value']=$params['defaultval'];}
    	}
    //view only?
    if(isset($params['-view']) && isNum($params['-view']) && $params['-view']==1){
		//view only - return the value instead of the tag
		if(in_array($info[$field]['inputtype'],array('select','checkbox'))){
			$selections=getDBFieldSelections($info[$field]);
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
                    //selected?
                    if(isset($_REQUEST[$field]) && ($_REQUEST[$field]==$selections['tvals'][$x] || $_REQUEST[$field]==$selections['dvals'][$x])){
						return $selections['dvals'][$x];
					}
                    elseif(isset($info[$field]['value']) && ($info[$field]['value']==$selections['tvals'][$x] || $info[$field]['value']==$selections['dvals'][$x])){
						return $selections['dvals'][$x];
					}
                }
            }
		}
		return $info[$field]['value'];
    }
    //set inputmax if not defined
    if(!isset($info[$field]['inputmax']) && isset($info[$field]['_dblength'])){$info[$field]['inputmax']=$info[$field]['_dblength'];}
    //set inputtype if not defined
    if(!isset($info[$field]['inputtype'])){
		//assign a valid inputtype based on _dbtype
		//Valid inputtypes:
		//	checkbox , combobox, date, file, formula, hidden, multi-select, password
		//	radio, select, text, textarea, time
		switch ($info[$field]['_dbtype']) {
			case 'string':
				$info[$field]['inputtype']=$info[$field]['_dblength']<256?'text':'textarea';
				if(!isset($info[$field]['width'])){$info[$field]['width']=200;}
				break;
			case 'blob':
				$info[$field]['inputtype']='textarea';
				if(!isset($info[$field]['width'])){$info[$field]['width']=400;}
				if(!isset($info[$field]['height'])){$info[$field]['height']=100;}
				break;
			case 'date':
				$info[$field]['inputtype']='date';
				break;
			case 'time':
				$info[$field]['inputtype']='time';
				break;
			case 'datetime':
				$info[$field]['inputtype']='datetime';
				break;
			default:
				$info[$field]['inputtype']='text';
				if(!isset($info[$field]['width'])){$info[$field]['width']=200;}
				break;
			}
		}
	if(!strlen($info[$field]['inputtype'])){return "Unknown inputtype for fieldname ".$field;}
	//set a few special fields
	switch ($info[$field]['inputtype']){
		//Checkbox
		case 'date':
			$styles['width']='90px';
            $styles['font-size']='9pt';
            $styles['font-family']='arial';
			$info[$field]['mask']='^[0-9]{1,2}[\-\/][0-9]{1,2}[\-\/][0-9]{2,4}$';
			$info[$field]['maskmsg']="Invalid date format (MM-DD-YYYY)";
			//if(!isset($info[$field]['value'])){$info[$field]['value']=date("m-d-Y");}
			unset($info[$field]['height']);
			break;
		case 'text':
			unset($info[$field]['height']);
			break;
		case 'password':
			unset($info[$field]['height']);
			$info[$field]['onfocus']="this.select();";
			break;
		case 'file':
			if(!isset($info[$field]['width']) || $info[$field]['width']==0){$info[$field]['width']=300;}
			unset($info[$field]['height']);
			break;
        case 'select':
			unset($info[$field]['height']);
			if(isset($info[$field]['width']) && (!isNum($info[$field]['width']) || $info[$field]['width']==0)){unset($info[$field]['width']);}
			break;
		case 'combo':
			unset($info[$field]['height']);
			if($params['-table']=='_fielddata' && $field=='behavior'){
            	$info[$field]['tvals']=wasqlGetBehaviors();
            	$info[$field]['dvals']=wasqlGetBehaviors();
			}
			break;
		case 'time':
			unset($info[$field]['height']);
			unset($info[$field]['width']);
			break;
		case 'color':
			unset($info[$field]['height']);
			$info[$field]['width']=80;
			break;
		case 'slider':
			$info[$field]['min']=$info[$field]['height'];
			$info[$field]['max']=$info[$field]['inputmax'];
			$info[$field]['step']=$info[$field]['required'];
			unset($info[$field]['height']);
			unset($info[$field]['inputmax']);
			unset($info[$field]['required']);
			break;
		}
	//set tag name
	if(!isset($info[$field]['name'])){$info[$field]['name']=$field;}
	//set displayname
	if(!isset($info[$field]['displayname']) || !strlen($info[$field]['displayname'])){$info[$field]['displayname']=ucfirst($field);}
    //set the width and height in the style attribute
    // style="width:100px;height:100px;font-size:

	if(isset($params['style'])){
    	$setstyles=preg_split('/\;/',$params['style']);
    	foreach($setstyles as $setstyle){
			$parts=preg_split('/\:/',$setstyle);
			$key=trim($parts[0]);
			$styles[$key]=trim($parts[1]);
        	}
        unset($setstyles);
 		}
 	$stylekeys=array('width','height');
 	foreach($stylekeys as $stylekey){
		if(!isset($styles[$stylekey]) && isset($info[$field][$stylekey]) && $info[$field][$stylekey] != 0){
            $styles[$stylekey]=$info[$field][$stylekey] . 'px';
        	}
    	}
    $info[$field]['style']=setStyleAttribues($styles);
	//Build the HTML tag
	//change the required attribute to _required since it messes up HTML5
	if(isNum($info[$field]['required']) && $info[$field]['required']==1){
		unset($info[$field]['required']);
		$info[$field]['_required']=1;
    	}
	$tag='';
	switch ($info[$field]['inputtype']){
		//Checkbox - NOTE: use arrayColumns function to order vertically rather than horizontally.
		case 'checkbox':
			$selections=getDBFieldSelections($info[$field]);
			if(is_array($selections['tvals'])){
				$name=$info[$field]['name'];
				$tval_count=count($selections['tvals']);
				$group_id="group_{$info[$field]['name']}";
				$group_id=preg_replace('/\[\]$/','',$group_id);
				if(isset($params['checkclass']) && isset($params['checkclasschecked'])){
					$checkclass=$params['checkclass'];
					$checkclasschecked=$params['checkclasschecked'];
					}
				if(isset($params['-formname'])){$group_id .= "_{$params['-formname']}";}
				//show Select All
				if((!isset($params['-all']) || $params['-all'] != 0) && $tval_count > 1 && isNum($info[$field]['width']) && $tval_count > (integer)$info[$field]['width']){
					if(isset($params['checkclass']) && isset($params['checkclasschecked'])){
						$tag .= '<div style="margin-bottom:5px;"><input id="'.$group_id.'_all" type="checkbox" onclick="checkAllElements(\'group\',\''.$group_id.'\',this.checked);setLabelChecked(\'id\',\''.$group_id.'\',this.checked,\''.$checkclass.'\',\''.$checkclasschecked.'\');"><label for="'.$group_id.'_all"> Check/Uncheck All</label></div>'."\n";
					}
					else{
						$tag .= '<div style="margin-bottom:5px;"><input id="'.$group_id.'_all" type="checkbox" onclick="checkAllElements(\'group\',\''.$group_id.'\',this.checked);setLabelChecked(\'id\',\''.$group_id.'\',this.checked);"><label for="'.$group_id.'_all"> Check/Uncheck All</label></div>'."\n";
                    }
				}
				$width=(integer)$info[$field]['width'];
				if($width==0){$width=1;}
				$tval_cols=arrayColumns($selections['tvals'],$width);
				if(is_array($selections['dvals']) && count($selections['dvals'])==count($selections['tvals'])){
					$dval_cols=arrayColumns($selections['dvals'],$width);
					}
				else{$dval_cols=$tval_cols;}
				//put them in a table so the checkboxes line up if multiple lines
				$tag .= '<table cellspacing="0" cellpadding="2" border="0">'."\n";
				$tag .= '	<tr valign="top" align="left">'."\n";
				$onclick=isset($info[$field]['onclick'])?$info[$field]['onclick'].';':'';
				$tcount_all=count($tval_cols);
				foreach($tval_cols as $colindex=>$tvals){
					$tag .= '		<td>'."\n";
     				$tcount=count($tvals);
					for($x=0;$x<$tcount;$x++){
						$tval=$tval_cols[$colindex][$x];
						$dval=$dval_cols[$colindex][$x];
						$check_id="{$name}_{$colindex}-{$x}";
						$ischecked=array();
						if(isset($params['-formname'])){$check_id .= "_{$params['-formname']}";}
						$tag .= '			<div style="padding:2px 2px 2px 0;">'."\n";
						if(isset($params['checkclass']) && isset($params['checkclasschecked'])){
							$tag .= '		<input group="'.$group_id.'" id="'.$check_id.'" onclick="'.$onclick.'setLabelChecked(\'for\',\''.$check_id.'\',this.checked,\''.$checkclass.'\',\''.$checkclasschecked.'\');" type="checkbox" style="margin:0px;" name="'.$info[$field]['name'];
							}
						else{
							$tag .= '		<input group="'.$group_id.'" id="'.$check_id.'" onclick="'.$onclick.'setLabelChecked(\'for\',\''.$check_id.'\',this.checked);" type="checkbox" style="margin:0px;" name="'.$info[$field]['name'];
							}
						if($tcount_all > 1){
							$tag .='[]';
							if(isset($info[$field]['_required']) && !isset($info[$field]['requiredmsg'])){
								$info[$field]['requiredmsg']=$name." is required";
                        		}
							}
						$tag .= '" value="'.$tval.'"';
	                    //Required?
	                    $topts=array('_required','requiredmsg');
	                    foreach($topts as $t){
	                    	if(isset($info[$field][$t])){
								$val=$info[$field][$t];
								$tag .= ' '.$t.'="'.$val.'"';
								}
	                    	}
	                    //selected?
	                    if(isset($params['value'])){
							if($params['value']==$tval || $params['value']==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								}
							else{
								$parts=preg_split('/\:+/',$params['value']);
								foreach($parts as $part){
									if($part==$tval || $part==$dval){
										$ischecked[$check_id]=1;
										$tag .= ' checked';
										break;
										}
	                            	}
	                        	}
							}
						elseif(isset($_REQUEST[$name])){
							if($_REQUEST[$name]==$tval || $_REQUEST[$name]==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								}
							else{
								if(is_array($_REQUEST[$name])){$parts=$_REQUEST[$name];}
								else{$parts=preg_split('/\:+/',$_REQUEST[$name]);}
								foreach($parts as $part){
									if($part==$tval || $part==$dval){
										$ischecked[$check_id]=1;
										$tag .= ' checked';
										break;
										}
	                            	}
	                        	}
							}
	                    elseif(isset($info[$field]['value']) && is_array($info[$field]['value'])){
							foreach($info[$field]['value'] as $rval){
								if($rval==$tval || $rval==$dval){
									$tag .= ' checked';
									$ischecked[$check_id]=1;
									break;
	                                }
	                            }
	                        }
						elseif(isset($info[$field]['value'])){
							if($info[$field]['value']==$tval || $info[$field]['value']==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								}
							else{
								if(is_array($info[$field]['value'])){$parts=$info[$field]['value'];}
								else{$parts=preg_split('/\:+/',$info[$field]['value']);}
								foreach($parts as $part){
									if($part==$tval || $part==$dval){
										$tag .= ' checked';
										$ischecked[$check_id]=1;
										break;
										}
	                            	}
	                        	}
							}
	                    elseif(is_array($params[$name])){
							foreach($params[$name] as $rval){
								if($rval==$tval || $rval==$dval){
									$tag .= ' checked';
									$ischecked[$check_id]=1;
									break;
	                                }
	                            }
	                        }
	                    elseif(isset($params[$name])){
							if($params[$name]==$tval || $params[$name]==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								}
							else{
								if(is_array($params[$name])){$parts=$params[$name];}
								else{$parts=preg_split('/\:+/',$params[$name]);}
								foreach($parts as $part){
									if($part==$tval || $part==$dval){
										$tag .= ' checked';
										$ischecked[$check_id]=1;
										break;
										}
	                            	}
	                        	}
							}
						elseif(is_array($_REQUEST[$name])){
							foreach($_REQUEST[$name] as $rval){
								if($rval==$tval || $rval==$dval){
									$tag .= ' checked';
									$ischecked[$check_id]=1;
									break;
	                                }
	                            }
	                        }
						$tag .= '>'."\n";
						if(sha1($dval) != sha1('1') || $tval_count > 1){
							if($ischecked[$check_id]==1){
								$class=isset($params['checkclasschecked'])?$params['checkclasschecked']:'w_checklist_checked';
								}
							else{
								$class=isset($params['checkclass'])?$params['checkclass']:'w_checklist';
								}
							$tag .= '<label style="margin:0px;" class="'.$class.'" id="'.$group_id.'" for="'.$check_id.'">';
							$tag .= $dval;
							$tag .= '</label>';
							}
						$tag .= '</div>'."\n";
	                	}
	                $tag .= '		</td>'."\n";
	                }
                $tag .= '	</tr>'."\n";
                $tag .= '</table>'."\n";
                }
			break;
		case 'color':
			$tag .= '<table cellspacing="0" cellpadding="0" border="0">';
			$tag .= '	<tr valign="middle" align="left">';
			if(strlen($info[$field]['name'])){
				$colorbox=$info[$field]['name'] . '_boxdiv';
				$colordiv=$info[$field]['name'] . '_imgdiv';
				$colorid=$info[$field]['name'] . '_inpdiv';
            	}
            else{
				$colorbox=$field . '_boxdiv';
				$colordiv=$field . '_imgdiv';
				$colorid=$field . '_inpdiv';
				}

            $tag .= '		<td><input id="'.$colorid.'" type="text"';
			$tag .= setTagAttributes($info[$field]);
			if(isset($info[$field]['value'])){
				$tag .= ' value="'.$info[$field]['value'].'"';
				}
			$tag .= '></td>'."\n";
			$tag .= '		<td valign="top"><div id="'.$colordiv.'"';
			if(isset($info[$field]['value'])){
				$tag .= ' style="background-color:'.$info[$field]['value'].';"';
				}
			$tag .= '><img alt="Show Color Control" title="Show Color Control" src="/wfiles/colors.gif" width="20" height="20" border="0" onClick="selectColor(\''.$colorbox.'\',\''.$colorid.'\',\''.$colordiv.'\');return false;" style="cursor:pointer;">';
			$tag .= '<div id="'.$colorbox.'" style="position:absolute;"></div></td>'."\n";
            $tag .= '	</tr>'."\n";
            $tag .= '</table>'."\n";
			break;
		case 'combo':
			//editable selection list
			if(!isset($info[$field]['width'])){$info[$field]['width']=140;}
			if(strlen($info[$field]['name'])){
				$inputid="combo_" . $info[$field]['name'];
				$comboid="comboselect_" . $info[$field]['name'];
            	}
            else{
				$inputid="combo_" . $field;
				$comboid="comboselect_" . $field;
				}
			$tag .= '<table cellspacing="0" cellpadding="0" border="0">'."\n";
			$tag .= '	<tr valign="middle" align="left">'."\n";
			$tag .= '		<td nowrap><div style="position:relative">'."\n";
			$tag .= '			<input id="'.$inputid.'" type="text"';
			$info[$field]['onkeypress']='return comboComplete(this, event, \''.$comboid.'\')';
			$tag .= setTagAttributes($info[$field]);
			$tag .= ' last_index="0"';
			if(isset($info[$field]['value'])){
				$tag .= ' value="'.encodeHtml($info[$field]['value']).'"';
				}
			$tag .= '><img class="w_noprint3" alt="Show Selections" title="Show Selections" src="/wfiles/dropdown.gif" width="16" height="16" border="0" onClick="return showDropDown(\''.$comboid.'\');" style="cursor:pointer;"><br>'."\n";
			$selections=getDBFieldSelections($info[$field]);
			$width=strlen($info[$field]['width'])?$info[$field]['width']:140;
			$tag .= '	<div id="'.$comboid.'" class="w_drop" style="z-index:945;width:'.$width.'px;">'."\n";
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tag .= '		<div><a class="w_link" href="#" onClick="setText(\''.$inputid.'\',this.getAttribute(\'tval\'));hideId(\''.$comboid.'\');return false;" tval="'.$selections['tvals'][$x].'">'.$selections['dvals'][$x].'</a></div>'."\n";
    			}
			}
            $tag .= '	</div>'."\n";
			$tag .= '		</div></td>'."\n";
			$tag .= '	</tr>'."\n";
            $tag .= '</table>'."\n";
			break;
		case 'date':
			$name=$info[$field]['name'];
			$tagopts=$info[$field];
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			$tag .= buildFormCalendar($name,$tagopts);
			break;
		case 'datetime':
			$name=$info[$field]['name'];
			//date part
			$tagopts=array();
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];} 
			//set prefix to formname
			if(isset($params['-formname'])){$tagopts['-prefix']=$params['-formname'];}
			$tag .= buildFormCalendar("{$name}[]",$tagopts);
			//time part
			$tagopts=array();
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			//set prefix to formname
			if(isset($params['-formname'])){$tagopts['-prefix']=$params['-formname'];}
			//check for required
			if(isset($info[$field]['_required']) && $info[$field]['_required'] ==1){
				$tagopts['-required']=1;
				if(!isset($tagopts['-value'])){
                	$tagopts['-value']=date('h:i:s');
				}
			}
			$tag .= buildFormTime($info[$field]['name'],$tagopts);
			break;
		//File
		case 'file':
			//set path of where to store this file in
			$name=$info[$field]['name'];
			$path=isset($info[$field]['path'])?$info[$field]['path']:$info[$field]['defaultval'];
			if(preg_match('/^\<\?(.+?)\?\>$/is',$path)){$path = trim(evalPHP($path));}
			if(!strlen($path)){$path='/files';}
			if(isset($_REQUEST["{$name}_path"]) && strlen($_REQUEST["{$name}_path"])){$path=$_REQUEST["{$name}_path"];}
            $tag .= '<input type="hidden" name="'.$name.'_path" value="'.$path.'">'."\n";
            //remove style attribute since it is not supported
            unset($info[$field]['style']);
            $info[$field]['size']=intval((string)$info[$field]['width']/8);
            //autonumber on upload - appends unix timestamp to filename
            if(isset($info[$field]['autonumber']) || $info[$field]['tvals'] == 'autonumber' || $info[$field]['behavior'] == 'autonumber'){
				$tag .= '<input type="hidden" name="'.$name.'_autonumber" value="1">'."\n";
                }
            //if a value exists then display it as a link with a remove checkbox
			if($params['-editmode'] && strlen($info[$field]['value']) && $info[$field]['value'] != $info[$field]['defaultval']){
				$val=encodeHtml($info[$field]['value']);
				$tag .= '<div class="w_smallest w_lblue">'."\n";
				$tag .= '<a class="w_link w_lblue" href="'.$val.'">'.$val.'</a>'."\n";
				$tag .= '<input type="checkbox" value="1" name="'.$name.'_remove"> Remove'."\n";
				$tag .= '<input type="hidden" name="'.$name.'_prev" value="'.$val.'">'."\n";
				$tag .= '</div>'."\n";
            	}
            //exit;
            //file tag
			$tag .= '<input type="file"';
			$tag .= setTagAttributes($info[$field]);
			$tag .= '>'."\n";
			break;
		case 'formula':
			break;
		case 'hidden':
			$tag .= '<input type="hidden"';
			$tag .= setTagAttributes($info[$field]);
			if(isset($info[$field]['value'])){
				$tag .= ' value="'.encodeHtml($info[$field]['value']).'"';
				}
			$tag .= '>'."\n";
			break;
		case 'multiselect':
			//a multiselect is just a special checkbox field group
			if(!isset($info[$field]['width'])){$info[$field]['width']=200;}
			$mid=$field.'_options';
			if(isset($params['-formname'])){$mid .= "_{$params['-formname']}";}
			$tag .= '<div style="border:1px solid #CCC;padding:2px 2px 2px 0;width:'.$info[$field]['width'].'px;" data-behavior="menu" display="'.$mid.'"> -- choose --'."\n";
			$tag .= '<div style="display:none;position:absolute;background:#FFF;border:1px solid #CCC;padding:3px 3px 10px 5px;z-index:9999" class="w_roundsmall_botleft w_roundsmall_botright" id ="'.$mid.'">'."\n";
			$info[$field]['width']=1;
			$info[$field]['inputtype'] = "checkbox";
			$info[$field]['-table']=$params['-table'];
			$info[$field]['-field']=$params['-field'];
			$info[$field]['name']="{$field}[]";
			$tag .=  getDBFieldTag($info[$field]);
			$tag .= '</div>'."\n";
			$tag .= '</div>'."\n";
			break;
		//Password
		case 'password':
			$tag .= '<input type="password"';
			$tag .= setTagAttributes($info[$field]);
			if(isset($info[$field]['value'])){
				$tag .= ' value="'.encodeHtml($info[$field]['value']).'"';
				}
			$tag .= '>'."\n";
			break;
		//Radio
		case 'radio':
			$selections=getDBFieldSelections($info[$field]);
			if(is_array($selections['tvals'])){
				//put them in a table so the checkboxes line up if multiple lines
				$tag .= '<table cellspacing="0" cellpadding="0" border="0">';
				$tag .= '	<tr valign="middle" align="left">';
				$name=$info[$field]['name'];
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$radio_id="{$name}_{$x}";
					$tval=$selections['tvals'][$x];
					$dval=$selections['dvals'][$x];
					if(isset($params['-formname'])){$radio_id .= "_{$params['-formname']}";}
					$tag .= '		<td><input type="radio" id="'.$radio_id.'" name="'.$name.'"';
                    $tag .= ' value="'.$tval.'"';
                    //set onclick manually since we add setRadioLabel also...
                    $onclicks=array("setRadioLabel(this.name)");
                    if(isset($info[$field]['onclick'])){
                    	$onclicks[]=$info[$field]['onclick'];
					}
					$onclick=implode(';',$onclicks);
					$tag .= ' onclick="'.$onclick.'"';
					//set other attrubutes (like required)
					$tag .= setTagAttributes($info[$field],array('id','name','value','onclick','maxlength'));
                    //selected?
                    if(isset($params['value'])){
						if($params['value']==$tval || $params['value']==$dval){
							$tag .= ' checked';
							$ischecked[$check_id]=1;
							}
						else{
							$parts=preg_split('/\:+/',$params['value']);
							foreach($parts as $part){
								if($part==$tval || $part==$dval){
									$ischecked[$check_id]=1;
									$tag .= ' checked';
									break;
									}
                            	}
                        	}
						}
					elseif(isset($_REQUEST[$name])){
						if($_REQUEST[$name]==$tval || $_REQUEST[$name]==$dval){
							$tag .= ' checked';
							$ischecked[$check_id]=1;
							}
						else{
							$parts=preg_split('/\:+/',$_REQUEST[$name]);
							foreach($parts as $part){
								if($part==$tval || $part==$dval){
									$ischecked[$check_id]=1;
									$tag .= ' checked';
									break;
									}
                            	}
                        	}
						}
                    elseif(isset($info[$field]['value']) && is_array($info[$field]['value'])){
						foreach($info[$field]['value'] as $rval){
							if($rval==$tval || $rval==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								break;
                                }
                            }
                        }
					elseif(isset($info[$field]['value'])){
						if($info[$field]['value']==$tval || $info[$field]['value']==$dval){
							$tag .= ' checked';
							$ischecked[$check_id]=1;
							}
						else{
							$parts=preg_split('/\:+/',$info[$field]['value']);
							foreach($parts as $part){
								if($part==$tval || $part==$dval){
									$tag .= ' checked';
									$ischecked[$check_id]=1;
									break;
									}
                            	}
                        	}
						}
                    elseif(is_array($params[$name])){
						foreach($params[$name] as $rval){
							if($rval==$tval || $rval==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								break;
                                }
                            }
                        }
                    elseif(isset($params[$name])){
						if($params[$name]==$tval || $params[$name]==$dval){
							$tag .= ' checked';
							$ischecked[$check_id]=1;
							}
						else{
							$parts=preg_split('/\:+/',$params[$name]);
							foreach($parts as $part){
								if($part==$tval || $part==$dval){
									$tag .= ' checked';
									$ischecked[$check_id]=1;
									break;
									}
                            	}
                        	}
						}
					elseif(is_array($_REQUEST[$name])){
						foreach($_REQUEST[$name] as $rval){
							if($rval==$tval || $rval==$dval){
								$tag .= ' checked';
								$ischecked[$check_id]=1;
								break;
                                }
                            }
                        }
					$tag .= '><label for="'.$radio_id.'" class="'.$class.'">'.$selections['dvals'][$x];
					$tag .= '</label></td>'."\n";
					if(isset($info[$field]['width']) && isFactor(intval($x+1),(int)$info[$field]['width'])){
						$tag .= '	</tr>'."\n".'	<tr valign="middle" align="left">';
                        	}
                    	}
                    $tag .= '	</tr>'."\n";
                    $tag .= '</table>'."\n";
                    $tag .= buildOnLoad("setRadioLabel('{$info[$field]['name']}');");
                	}
			break;
		//Select
		case 'select':
			$selections=getDBFieldSelections($info[$field]);
			if($field=='state' && (!is_array($selections['tvals']) || !count($selections['tvals']) || (count($selections['tvals'])==1 && !strlen($selections['tvals'][0])))){
				$params['inputtype']="text";
				if(!isset($params['width'])){
                	$params['width']=150;
				}
				$tag .= getDBFieldTag($params);
				break;
			}
			//if($field=='state'){echo "selections" . printValue(array($selections,$info[$field]));}
			if(is_array($selections['tvals']) && count($selections['tvals'])){
                $tag .= '<select';
				$tag .= setTagAttributes($info[$field]);
				$tag .= '>'."\n";
				$selected=0;
				$name=$info[$field]['name'];
				$message=isset($info[$field]['message'])?$info[$field]['message']:' -- choose --';
				//if(!isset($info[$field]['_required']) || $info[$field]['_required'] !=1 || isset($info[$field]['message'])){
					$tag .= '	<option value="">'.$message.'</option>'."\n";
                //}
                $cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					//-filter:
					if(isset($info['-tval_filter']) && !stristr($info['-tval_filter'],$selections['tvals'][$x])){continue;}
                    if(isset($info['-dval_filter']) && !stristr($info['-dval_filter'],$selections['dvals'][$x])){continue;}
                    if($selections['tvals'][$x]=='--' && $selections['dvals'][$x]=='--'){
                		$selections['tvals'][$x]='';
                		$selections['dvals'][$x]='----------';
					}
					$tag .= '	<option value="'.$selections['tvals'][$x].'"';
                    //selected?
                    if(isset($params['value'])){
						if($params['value']==$selections['tvals'][$x] || $params['value']==$selections['dvals'][$x]){
							$tag .= ' selected';
							$selected=1;
						}
					}
					elseif(isset($_REQUEST[$name])){
						if($_REQUEST[$name]==$selections['tvals'][$x] || $_REQUEST[$name]==$selections['dvals'][$x]){
							$tag .= ' selected';
							$selected=1;
						}
					}
					elseif(isset($_REQUEST[$field])){
						if($_REQUEST[$field]==$selections['tvals'][$x] || $_REQUEST[$field]==$selections['dvals'][$x]){
							$tag .= ' selected';
							$selected=1;
						}
					}
                    elseif(isset($info[$field]['value'])){
						if($info[$field]['value']==$selections['tvals'][$x] || $info[$field]['value']==$selections['dvals'][$x]){
							$tag .= ' selected';
							$selected=1;
						}
					}
					$tag .= '>'.$selections['dvals'][$x].'</option>'."\n";
                }
                $tag .= '</select>'."\n";
                if($selected==1 && isset($params['-formname']) && isset($info[$field]['onchange'])){
					$fname=strlen($info[$field]['name'])?$info[$field]['name']:$field;
					$fieldstr="document.{$params['-formname']}.{$fname}";
					$onchange=preg_replace('/this/',$fieldstr,$info[$field]['onchange']);
					$tag .= buildOnLoad($onchange);
                }
            }
            else{
            	$tag .= '<select';
				$tag .= setTagAttributes($info[$field]);
				$tag .= '>'."\n";
				$selected=0;
				$name=$info[$field]['name'];
				$message=isset($info[$field]['message'])?$info[$field]['message']:' -- --';
				$tag .= '	<option value="">'.$message.'</option>'."\n";
				$tag .= '</select>'."\n";
			}
			break;
		case 'signature':
			//$tag .= printValue($tagopts).printValue($info[$field]);break;
			$name=$info[$field]['name'];
			//date part
			$tagopts=array();
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];} 
			//check for displayname
			if(isset($params['-displayname'])){$tagopts['displayname']=$params['-displayname'];}
			elseif(isset($info[$field]['displayname'])){$tagopts['displayname']=$info[$field]['displayname'];}
			if(isset($params['width'])){$tagopts['width']=$params['width'];}
			elseif(isset($info[$field]['width'])){$tagopts['width']=$info[$field]['width'];}
			if(isset($params['height'])){$tagopts['height']=$params['height'];}
			elseif(isset($info[$field]['height'])){$tagopts['height']=$info[$field]['height'];}
			//set prefix to formname
			if(isset($params['-formname'])){$tagopts['-prefix']=$params['-formname'];}
			$tag .= buildFormSignature($name,$tagopts);
			break;
		case 'slider':
		case 'range':
			//load html5slider.js to enable slider support in FF, etc. Still will not work in IE.
			$tag .= buildFormSlider($info[$field]['name'],$info[$field]);
			//echo "HERE".printValue($tag).printValue($info[$field]);exit;
			break;
		case 'textarea':
			$end_div=0;
			//add a behavior for css, js
			if(!strlen($info[$field]['behavior']) && strlen($info[$field]['value'])){
				//echo $info[$field]['value'];exit;
				if(stringContains($info[$field]['value'],'/*filetype:css*/')){
	            	$info[$field]['behavior']='csseditor';
				}
				elseif(stringContains($info[$field]['value'],'/*filetype:js*/')){
	            	$info[$field]['behavior']='jseditor';
				}
				elseif(stringContains($info[$field]['value'],'/*filetype:php*/')){
	            	$info[$field]['behavior']='phpeditor';
				}
				elseif(stringContains($info[$field]['value'],'/*filetype:pl*/')){
	            	$info[$field]['behavior']='perleditor';
				}
				elseif(stringContains($info[$field]['value'],'/*filetype:rb*/')){
	            	$info[$field]['behavior']='rubyeditor';
				}
				elseif(stringContains($info[$field]['value'],'/*filetype:xml*/')){
	            	$info[$field]['behavior']='xmleditor';
				}
			}
			if(strlen($info[$field]['behavior']) && $info[$field]['behavior']=='nowrap'){
            	$info[$field]['behavior']='';
				$info[$field]['wrap']="off";
			}
			if(strlen($info[$field]['behavior'])){
				//set the flag to load additional behaior js if needed
				$tagwrap=0;
				if(stringContains($info[$field]['behavior'],'editor')){
					loadExtrasCss(array('codemirror','nicedit'));
					$tagwrap=1;
				}
				elseif(stringContains($info[$field]['behavior'],'tinymce')){
					loadExtrasCss(array('nicedit'));
					$tagwrap=1;
				}
				elseif(stringContains($info[$field]['behavior'],'wysiwyg')){
					loadExtrasCss(array('nicedit'));
					$tagwrap=1;
				}
				elseif(stringContains($info[$field]['behavior'],'nicedit')){
					loadExtrasCss(array('nicedit'));
					$tagwrap=1;
				}
				if($tagwrap==1){
					$tag .= '<div style="background-color:#FFFFFF;">'."\n";
					$end_div=1;
					//$info[$field]['value']=fixMicrosoft($info[$field]['value']);
                	}
			}
			//pass behavior as a hidden field
			if(strlen($info[$field]['behavior'])){
            	$tag .= '<input type="hidden" name="'.$field.'_behavior" value="'.$info[$field]['behavior'].'">'."\n";
			}
			if(!isset($info[$field]['id'])){$info[$field]['id']="txtfld_{$field}";}
			$tag .= '<textarea ';
			//wrap?
			if(isset($params['wrap'])){$info[$field]['wrap']=$params['wrap'];}
			elseif($params['-table']=='_pages'){$info[$field]['wrap']="off";}
			//show preview for _pages and _templates
			if(isset($params['-preview'])){
				$tag .= ' preview="'.$params['-preview'].'"';
			}
			$tag .= setTagAttributes($info[$field]);
			$tag .= '>';
			if(isset($info[$field]['value'])){
				//Aug 7 2012: fix for UTF-8 characters to show properly in textarea
				$info[$field]['value']=fixMicrosoft($info[$field]['value']);
				$tag .= encodeHtml($info[$field]['value']);
				}
            $tag .= '</textarea>'."\n";
            if($end_div==1){$tag .= '</div>'."\n";}
            if(isset($info[$field]['wrap'])){
                $tag .= '<input type="hidden" name="'.$field.'_wrap" value="'.$info[$field]['wrap'].'">'."\n";
             	}
            break;
		case 'time':
			$tagopts=array();
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			//set prefix to formname
			if(isset($params['-formname'])){$tagopts['-prefix']=$params['-formname'];}
			//check for required
			if(isset($info[$field]['_required']) && $info[$field]['_required'] ==1){
				$tagopts['-required']=1;
				if(!isset($tagopts['-value'])){
                	$tagopts['-value']=date('h:i:s');
				}
			}
			$tag .= buildFormTime($info[$field]['name'],$tagopts);
			break;
		//Text
		default:
			//default to text
			//remove height since it does not apply to text fields
			$tag .= '<input type="text"';
			$tag .= setTagAttributes($info[$field]);
			if(isset($info[$field]['value'])){
				$tag .= ' value="'.encodeHtml($info[$field]['value']).'"';
				}
			$tag .= '>';

			break;
    	}
    //not done here yet...
    return $tag;
	}
//---------- begin function getDBFieldSelections
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBFieldSelections($info=array()){
	$selections=array();
	if(strtolower(trim($info['tvals']))=='&getdbtables'){
			$selections['tvals']=getDBTables();
			$selections['dvals']=$selections['tvals'];
			return $selections;
        	}
	if(!isset($info['dvals']) || !strlen($info['dvals'])){$info['dvals']=$info['tvals'];}
    if(isset($info['tvals'])){
		if(is_array($info['tvals'])){$tvals=$info['tvals'];}
		else{
			$tvals=trim($info['tvals']);
			if(preg_match('/\<\?(.+?)\?\>/is',$tvals)){$tvals = evalPHP($tvals);}
		}
		if(is_array($info['tvals'])){$dvals=$info['dvals'];}
		else{
			$dvals=trim($info['dvals']);
			if(preg_match('/\<\?(.+?)\?\>/is',$dvals)){$dvals = evalPHP($dvals);}
		}
		if(is_array($tvals) && is_array($dvals)){
			$selections['tvals']=$tvals;
			$selections['dvals']=$dvals;
			return $selections;
		}
		if(preg_match('/^select\ /i',$tvals)){
			$tvalresults=getDBRecords(array('-query'=>$tvals));
			if(is_array($tvalresults)){
                $dvalresults=getDBRecords(array('-query'=>$dvals));
				if(is_array($dvalresults)){
					//if($info['fieldname']=='user_id'){echo $tvals . printValue($tvalresults) . $dvals . printValue($dvalresults);}
					//parse through the results and build the tval/dval array.
	                $tvalues=array();
	                foreach($tvalresults as $tvalresult){
						$vals=array();
                        foreach($tvalresult as $rkey=>$rval){
							array_push($vals,$rval);
                        	}
						$val=implode(' ',$vals);
						unset($vals);
						array_push($tvalues,$val);
                    	}
                    $dvalues=array();
	                foreach($dvalresults as $dvalresult){
						$vals=array();
                        foreach($dvalresult as $rkey=>$rval){
							array_push($vals,$rval);
                        	}
						$val=implode(' ',$vals);
						unset($vals);
						array_push($dvalues,$val);
                    	}
					$selections['tvals']=$tvalues;
					$selections['dvals']=$dvalues;
	            	}
            	}

        	}
        elseif(preg_match('/^([0-9]+?)\.\.([0-9]+)$/',$tvals,$tvmatch)){
			$selections['tvals']=array();
			$start=(integer)$tvmatch[1];
			$end=(integer)$tvmatch[2];
			for($x=$start;$x <= $end;$x++){
                array_push($selections['tvals'],$x);
            	}
            $selections['dvals']=$selections['tvals'];
        	}
		else{
			//Parse values in tvals and dvals
			$selections['tvals']=preg_split('/[\r\n\,]+/',$tvals);
			$selections['dvals']=preg_split('/[\r\n\,]+/',$dvals);
			//abort(printValue($selections));
        	}
        return $selections;
    	}
	return;
	}
//---------- begin function getDBList--------------------
/**
* @describe returns an array of databases that the dbuser has rights to see
* @return array
* @usage $dbs=getDBList();
*/
function getDBList(){
	return databaseListDbs();
}
//---------- begin function getDBProcesses--------------------
/**
* @describe returns an array of current database processes/threads
* @return array
* @usage $procs=getDBProcesses();
*/
function getDBProcesses(){
	$db_list = databaseListProcesses();
	$procs=array();
	while ($row = databaseFetchObject($db_list)) {
		$proc=array();
		foreach($row as $key=>$val){$proc[$key]=$val;}
		$procs[]=$proc;
		}
	return $procs;
	}
//---------- begin function getDBPaging--------------------
/**
* @describe returns an array of paging information needed for buildDBPaging
* @param recs_count integer - total record count
* @param [page_count] - numbers of records to display - defaults to 20
* @param [limit_start] - record number to start paging at - defaults to 0
* @return array
* @usage $procs=getDBPaging($cnt);
*/
function getDBPaging($recs_count,$page_count=20,$limit_start=0){
	if(!isNum($page_count)){$page_count=20;}
	if($recs_count <= $page_count){return null;}
	$paging=array();
	if(isset($_REQUEST['_start']) && isNum($_REQUEST['_start'])){
		$limit_start=(integer)$_REQUEST['_start'];
		}
	$limit_cnt=$page_count+$limit_start;
	if($limit_cnt > $recs_count){$limit_cnt = $recs_count;}
	$paging['-start']=$limit_start;
	$paging['-offset']=$page_count;
	$paging['-limit']="{$limit_start},{$page_count}";
	//previous
	if($limit_start > 0){
		$prev=$limit_start-$page_count;
		if($prev < 0){$prev=0;}
		$paging['-prev']=$prev;
		if($prev > 0){
			$paging['-first']=0;
			}
		}
	//next
	if($limit_cnt < $recs_count){
		$next=$limit_start+$page_count;
		$paging['-next']=$next;
		$last=$recs_count-$page_count;
		if($last > 0 && $last > $next){
			$paging['-last']=$last;
			}
		}
	//text
	$paging['-text']=round(($limit_start+1),0) . " - {$limit_cnt} of {$recs_count}";
	return $paging;
	}
//---------- begin function loadDBFunctions---------------------------------------
/**
* @describe loads functions in pages. Returns the load times for each loaded page.
* @param $names string|array
*    page name or lists of page names.
* @param $field string
*  	page field to be loaded
* @return string
* 	returns an html comment showing the load times for each loaded page.
* @usage 
*	loadDBFunctions('sampleFunctionsPage'); //This would load and process the body segment of 'sampleFunctionsPage'.
* 	loadFunctions('locations','functions'); //This would load and process the functions segment of the 'locations' page.
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function loadDBFunctions($names,$field='body'){
	if(is_array($names)){}
	elseif(strlen($names)){$names=array($names);}
	else{$names=array('functions');}
	$errors=array();
	$rtn='<!-- loadDBFunctions'."\n";
	foreach($names as $name){
		$start=microtime(1);
		$table="_pages";
		$tname=$name;
		//does the name include a database name?
    	if(preg_match('/^(.+?)\.(.+)$/',$name,$m)){
			$table="{$m[1]}._pages";
			$name=$m[2];
        	}
		$opts=array('-table'=>$table,'-field'=>$field);
		if(isNum($name)){$opts['-where']="_id={$name}";}
		else{$opts['-where']="name = '{$name}'";}
		$ok=includeDBOnce($opts);
		$stop=microtime(1);
		$loadtime=$stop-$start;
		if(!isNum($ok)){
			$rtn .= "	{$tname} ERRORS: {$ok}\n";
			debugValue($ok);
			}
		else{$rtn .= "	{$tname} took {$loadtime} seconds\n";}
    	}
    $rtn .= ' -->'."\n";
	return $rtn;
	}
//---------- begin function logDBQuery
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function logDBQuery($query,$start,$function,$tablename='',$fields='',$rowcount=0){
	global $SETTINGS;
	global $USER;
	global $PAGE;
	if(isset($_SERVER['WaSQL_AdminUserID']) && isNum($_SERVER['WaSQL_AdminUserID'])){return;}
	if(!isset($SETTINGS['wasql_queries']) || $SETTINGS['wasql_queries']!=1){return;}
	if(preg_match('/\_(queries|fielddata)/i',$query)){return;}
	if(preg_match('/^desc /i',$query)){return;}
	//only run if user?
	if(isNum($SETTINGS['wasql_queries_user']) && (!isset($USER['_id']) || $SETTINGS['wasql_queries_user'] != $USER['_id'])){return;}
	$stop=microtime(true);
	$run_length=round(($stop-$start),3);
	if(isNum($SETTINGS['wasql_queries_time']) && $run_length < $SETTINGS['wasql_queries_time']){return;}
	$addopts=array('-table'=>"_queries",
		'function'		=> $function,
		'query'			=> $query,
		'row_count'		=> $rowcount,
		'run_length'	=> $run_length,
		);
	if(strlen($tablename)){$addopts['tablename']=$tablename;}
	if(!is_array($fields)){$fields=preg_split('/[\,\;\ ]+/',$fields);}
	$addopts['fields']=implode(',',$fields);
	$addopts['field_count']=count($fields);
	if(isNum($USER['_id'])){$addopts['user_id']=$USER['_id'];}
	if(isNum($PAGE['_id'])){$addopts['page_id']=$PAGE['_id'];}
	$ok=addDBRecord($addopts);
	//remove records older than $SETTINGS['wasql_queries_age'] days. Default to 10 days
	$days=setValue(array($SETTINGS['wasql_queries_days'],10));
	if(!isset($_SERVER['logDBQuery'])){
		$query="delete from _queries where _cdate < DATE_ADD(NOW(), INTERVAL -{$days} DAY)";
		$x=executeSQL($query);
		$_SERVER['logDBQuery']=1;
		}
	return $ok;
	}
//---------- begin function includeDBOnce
/**
* @describe function to load database records as php you can include dynamic functions
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function includeDBOnce($params=array()){
	global $CONFIG;
	// need table, field, where
	if(!isset($params['-table'])){return 'includeDBOnce Error: No table' . printValue($params);}
	if(!isset($params['-field'])){return 'includeDBOnce Error: No field' . printValue($params);}
	if(!isset($params['-where'])){return 'includeDBOnce Error: No where' . printValue($params);}
	$field=$params['-field'];
	$params['-where']=str_replace(' like ',' = ',$params['-where']);
	$opts=array('-table'=>$params['-table'],'-notimestamp'=>1,'-where'=>$params['-where'],'-fields'=>array($field));
	if(isset($params['-dbname'])){$opts['-dbname']=$params['-dbname'];}
	$rec=getDBRecord($opts);
	if(!is_array($rec)){
		return 'includeDBOnce Error: No record. ' .$rec. printValue($params);
	}
	$content=trim($rec[$field]);
	if(!stringBeginsWith($content,'<?')){
    	$content="<?php
    	{$content}
    	?>";
	}
	$where=preg_replace('/[^a-z0-9]+/i','_',$params['-where']);
	$where=preg_replace('/\_+$/','',$where);
	/* Since URL file-access is disabled on some servers for security reasons, bring the rss feed locally first*/
	$phpfilename=$CONFIG['dbname'] .'_' . $params['-table'] .'_' . $params['-field'] . '_' . $where . '.php';
	$phpfilename=preg_replace('/\_+/','_',$phpfilename);
	$progpath=dirname(__FILE__);
	buildDir("{$progpath}/temp");
	$phpfile="{$progpath}/temp/{$phpfilename}";
	//If the DB record has changed since the file has changed, then force a reload
	$content_md5=md5($content);
	if(file_exists($phpfile) && md5_file($phpfile) != $content_md5){
		unlink($phpfile);
		if(is_file($phpfile)){
        	return "includeDBOnce Error: permission errors in {$progpath}/temp - unable to unlink {$phpfile}";
		}
	}
	//write the php file if needed
	if(!file_exists($phpfile)){
		//echo "Writing {$phpfile}<br>\n";
		$fp = fopen($phpfile, "w");
		fwrite($fp, $content);
		fclose($fp);
		}
	//include this php file
	if(file_exists($phpfile)){
		//echo $phpfile;exit;
		@trigger_error("");
		//$evalstring='error_reporting(E_ERROR | E_PARSE);'."\n";
		$evalstring='showErrors();'."\n";
		$evalstring .= 'try{'."\n";
		$evalstring .= '	include_once(\''.$phpfile.'\');'."\n";
		$evalstring .= '	}'."\n";
		$evalstring .= 'catch(Exception $e){'."\n";
		$evalstring .= '	}'."\n";
		//return $evalstring;

		@eval($evalstring);
		$e=error_get_last();
		if($e['message']!=='' && !preg_match('/Undefined/i',$e['message'])){
    		// An error occurred
    		//return evalErrorWrapper($e,"includeDBOnce Error".printValue($params));
    		debugValue($params);
    		return 0;
			}
		return 1;
    	}
    else{
    	return "includeDBOnce Error: permission errors in {$progpath}/temp - unable to write {$phpfile}";
	}
	return 0;
	}
//---------- begin function mapDBDvalsToTvals--------------------
/**
* @describe returns a key/value array map so if you know a tval you can derive the dval
* @param table string - table name
* @param field string - field name in table
* @param params array - filters to apply 
* @param [min] - skip tval if less than min
* @param [max] - skip tval if more than max
* @param [contains] - skip if tval does not contain
* @param [equals] - skip if tval does not equal
* @param [in] array - skip if tval is not in this array of values
* @return array with tval as the index
* @usage $map=mapDBDvalsToTvals('states','code');
*/
function mapDBDvalsToTvals($table,$field,$params=array()){
	global $databaseCache;
	$cachekey=$table.'_'.$field;
	if(count($params)){$cachekey .= '_'.sha1(printValue($params));}
	if(isset($databaseCache['mapDBDvalsToTvals'][$cachekey])){
		return $databaseCache['mapDBDvalsToTvals'][$cachekey];
		}
	$info=getDBFieldMeta($table,"tvals,dvals",$field);
	$selections=getDBFieldSelections($info[$field]);
	if(is_array($selections['tvals'])){
		$tdmap=array();
		$tcount=count($selections['tvals']);
		for($x=0;$x<$tcount;$x++){
			$tval=$selections['tvals'][$x];
			if(isset($selections['dvals'][$x]) && strlen($selections['dvals'][$x])){$dval=$selections['dvals'][$x];}
			else{$dval=$tval;}
			//check params - min, max, contains, equals
			if(isset($params['min']) && isNum($params['min']) && isNum($tval) && $tval < $params['min']){continue;}
			if(isset($params['max']) && isNum($params['max']) && isNum($tval) && $tval > $params['max']){continue;}
			if(isset($params['contains']) && strlen($params['contains']) && strlen($tval) && !stringContains($tval,$params['contains'])){continue;}
			if(isset($params['equals']) && strlen($params['equals']) && strlen($tval) && $tval != $params['equals']){continue;}
			if(isset($params['in']) && is_array($params['in']) && strlen($tval) && !in_array($tval,$params['in'])){continue;}
			$tdmap[$tval]=$dval;
        	}
        $databaseCache['mapDBDvalsToTvals'][$cachekey]=$tdmap;
        return $tdmap;
    	}
    return null;
    }
//---------- begin function mapDBTvalsToDvals--------------------
/**
* @describe returns a key/value array map so if you know a dval you can derive the tval
* @param table string - table name
* @param field string - field name in table
* @param params array - filters to apply 
* @param [min] - skip dval if less than min
* @param [max] - skip dval if more than max
* @param [contains] - skip if dval does not contain
* @param [equals] - skip if dval does not equal
* @param [in] array - skip if dval is not in this array of values
* @return array with dval as the index
* @usage $map=mapDBTvalsToDvals('states','code');
*/
function mapDBTvalsToDvals($table,$field){
	global $databaseCache;
	$cachekey=$table.$field;
	if(isset($databaseCache['mapDBTvalsToDvals'][$cachekey])){
		return $databaseCache['mapDBTvalsToDvals'][$cachekey];
		}
	$info=getDBFieldMeta($table,"tvals,dvals",$field);
	$selections=getDBFieldSelections($info[$field]);
	if(is_array($selections['tvals'])){
		$tdmap=array();
		$tcount=count($selections['tvals']);
		for($x=0;$x<$tcount;$x++){
			$tval=$selections['tvals'][$x];
			if(isset($selections['dvals'][$x]) && strlen($selections['dvals'][$x])){$dval=$selections['dvals'][$x];}
			else{$dval=$tval;}
			//check params - min, max, contains, equals
			if(isset($params['min']) && isNum($params['min']) && isNum($dval) && $dval < $params['min']){continue;}
			if(isset($params['max']) && isNum($params['max']) && isNum($dval) && $dval > $params['max']){continue;}
			if(isset($params['contains']) && strlen($params['contains']) && strlen($dval) && !stringContains($dval,$params['contains'])){continue;}
			if(isset($params['equals']) && strlen($params['equals']) && strlen($dval) && $dval != $params['equals']){continue;}
			if(isset($params['in']) && is_array($params['in']) && strlen($dval) && !in_array($dval,$params['in'])){continue;}
			$tdmap[$dval]=$tval;
        	}
        $databaseCache['mapDBDvalsToTvals'][$cachekey]=$tdmap;
        return $tdmap;
    	}
    return null;
    }
//---------- begin function syncDBAccess
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function syncDBAccess($sync_url,$update=1){
	//exclude: true
	global $PAGE;
	//status: 1=sync, 0=nosync
	//make sure you are not syncing the url you are on
	$hosts=array($_SERVER['HTTP_HOST'],$_SERVER['UNIQUE_HOST']);
	$parts=preg_split('/[\/]+/',$sync_url);
	foreach($hosts as $host){
		if(strtolower($parts[0]) == strtolower($host)){return "syncDBAccess Error - {$sync_url} same host as {$host}";}
		if(strtolower($parts[1]) == strtolower($host)){return "syncDBAccess Error - {$sync_url} same host as {$host}";}
    	}
	$count=0;
	//remove access records from this page
	$ok=delDBRecord(array('-table'=>"_access",'-where'=>"page = '{$PAGE['name']}'"));
	$recs=getDBRecords(array('-table'=>"_access",'-where'=>"status=1 and not(page = '{$PAGE['name']}')",'-order'=>'_cdate'));
	//return printValue($recs);
	foreach($recs as $rec){
		$xmldata=xmldata2Array($rec['xml']);
		if(isset($xmldata['counts'])){
			$urlopts=$xmldata['data'];
			if(isset($xmldata['user']['apikey']) && isset($xmldata['user']['username'])){
				$urlopts['username']=$xmldata['user']['username'];
				$urlopts['apikey']=$xmldata['user']['apikey'];
				$urlopts['_noguid']=1;
            	}
            $urlopts['_noguid']=1;
            $rtn=postURL($sync_url,$urlopts);
            //return $sync_url . printValue($urlopts).printValue($rtn);
            if(isset($rtn['body'])){$count++;}
			}
		if($update==1){
			$ok=editDBRecord(array('-table'=>"_access",'-where'=>"_id={$rec['_id']}",'status'=>0));
			}
    	}
    return $count;
	}
//---------- begin function getDBFields--------------------
/**
* @describe returns table fields. If $allfields is true returns internal fields also
* @param table string - table name
* @param allfields boolean - if true returns internal fields also - defaults to false
* @return array
* @usage $fields=getDBFields('notes');
*/
function getDBFields($table='',$allfields=0){
	global $databaseCache;
	$dbcachekey=strtolower($table);
	if($allfields){$dbcachekey.='_true';}
	if(isset($databaseCache['getDBFields'][$dbcachekey])){
		return $databaseCache['getDBFields'][$dbcachekey];
	}
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$tablename=implode('.',$table_parts);
		if(strlen($dbname)){$dbname .= '.';}
	}
	else{
		$dbname='';
		$tablename=$table;
    }
    $fieldnames=array();
	if(isMssql()){$tablename="[{$tablename}]";}
	$query="SELECT * FROM {$dbname}{$tablename} where 1=0";
	$query_result=@databaseQuery($query);
  	if(!$query_result){
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  	}
	//mysqli does not have a mysqli_field_name function
	if(isMysqli()){
		while ($finfo = mysqli_fetch_field($query_result)) {
	        $name = (string)$finfo->name;
	        if(!$allfields && preg_match('/^\_/',$name)){continue;}
	        if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
	    }
	}
	elseif(isPostgreSQL()){
    	$i = pg_num_fields($query_result);
  		for ($j = 0; $j < $i; $j++) {
      		$name = pg_field_name($query_result, $j);
      		if(!$allfields && preg_match('/^\_/',$name)){continue;}
	        if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
      		//$clen = pg_field_prtlen($result, $name); //char length
      		//$blen = pg_field_size($result, $j); //byte length
      		//$type = pg_field_type($result, $j); // type
  		}
	}
	else{
		$cnt = databaseNumFields($query_result);
		for ($i=0; $i < $cnt; $i++) {
			$name  = (string) databaseFieldName($query_result, $i);
			if(!$allfields && preg_match('/^\_/',$name)){continue;}
			if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
		}
	}
	databaseFreeResult($query_result);
	sort($fieldnames);
	$databaseCache['getDBFields'][$dbcachekey]=$fieldnames;
	return $fieldnames;
}
//---------- begin function getDBFieldInfo--------------------
/**
* @describe returns an array containing type,length, and flags for each field in said table
* @param table string - table name
* @param [getmeta] boolean - if true returns info in _fielddata table for these fields - defaults to false
* @param [field] string - if this has a value return only this field - defaults to blank
* @param [getmeta] boolean - if true forces a refresh - defaults to false
* @return array
* @usage $fields=getDBFieldInfo('notes');
*/
function getDBFieldInfo($table='',$getmeta=0,$field='',$force=0){
	global $databaseCache;
	$dbcachekey=strtolower($table.'_'.$getmeta.'_'.$field);
	if($force==0 && isset($databaseCache['getDBFieldInfo'][$dbcachekey])){
		return $databaseCache['getDBFieldInfo'][$dbcachekey];
		}
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$tablename=array_pop($table_parts);
		$db_prefix=implode('.',$table_parts).'.';
		}
	else{
		$db_prefix='';
		$tablename=$table;
    	}
	$info=array();
	//get a list of the database info
	$schema = getDBSchema(array($table));
	if(!is_array($schema)){return $schema;}
    foreach($schema as $fld){
		$name=$fld['field'];
		foreach($fld as $key=>$val){
			if(preg_match('/^_/',$key) || !strlen($val)){continue;}

			$info[$name]['_db'.$key]=$val;
        	}
    	}
	if(isMssql()){$table="[{$table}]";}
	$query="SELECT * FROM $table where 1=0";
	$query_result=@databaseQuery($query);
  	if(!$query_result){
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	$cnt = databaseNumFields($query_result);
	//mysqli does not have a mysqli_field_name function
	if(isMysqli()){
		$dbtypemap = array(
		    1=>'tinyint',
		    2=>'smallint',
		    3=>'int',
		    4=>'float',
		    5=>'double',
		    7=>'timestamp',
		    8=>'bigint',
		    9=>'mediumint',
		    10=>'date',
		    11=>'time',
		    12=>'datetime',
		    13=>'year',
		    16=>'bit',
		    252=>'blob',
		    253=>'varchar',
		    254=>'char',
		    246=>'decimal'
		);
		/*
		       NOT_NULL_FLAG = 1
		       PRI_KEY_FLAG = 2                                                                              
		       UNIQUE_KEY_FLAG = 4                                                                           
		       BLOB_FLAG = 16                                                                                
		       UNSIGNED_FLAG = 32                                                                            
		       ZEROFILL_FLAG = 64                                                                            
		       BINARY_FLAG = 128                                                                             
		       ENUM_FLAG = 256                                                                               
		       AUTO_INCREMENT_FLAG = 512                                                                     
		       TIMESTAMP_FLAG = 1024                                                                         
		       SET_FLAG = 2048                                                                               
		       NUM_FLAG = 32768                                                                              
		       PART_KEY_FLAG = 16384                                                                         
		       GROUP_FLAG = 32768
		       UNIQUE_FLAG = 65536
		*/
		while ($finfo = mysqli_fetch_field($query_result)) {
	        $name = (string)$finfo->name;
	        if(strlen($field) && $name != $field){continue;}
	        $flags=array();
	        if($finfo->flags & 1){$flags[]='not_null';}
	        if($finfo->flags & 2){$flags[]='primary_key';}
	        if($finfo->flags & 4){$flags[]='unique_key';}
	        if($finfo->flags & 32){$flags[]='unsigned';}
	        if($finfo->flags & 512){$flags[]='auto_increment';}
	        if($finfo->flags & 65536){$flags[]='unique';}
	        $dbtypeid=(string)$finfo->type;
	        //echo $name.printValue($finfo);
	        $info[$name]['_dbtable'] = $finfo->table;
	        $info[$name]['_dblength'] = (integer)$finfo->length;
	        if($info[$name]['_dblength']==0){
	        	$info[$name]['_dblength'] = (integer)$finfo->max_length;
			}
	        $info[$name]['_dbflags'] = implode(' ',$flags);
	        $info[$name]['_dbtype'] = isset($dbtypemap[$dbtypeid])?$dbtypemap[$dbtypeid]:$dbtypeid;
    	}
	}
	elseif(isPostgreSQL()){
		$info=postgresTableInfo($table);
		//echo $table.printValue($info);
	}
	else{
		for ($i=0; $i < $cnt; $i++) {
			$name  = (string) databaseFieldName($query_result, $i);
			if(strlen($field) && $name != $field){continue;}
			$info[$name]['_dbtable'] = $table;
			$info[$name]['_dbtype'] = databaseFieldType($query_result, $i);
	        $info[$name]['_dblength']  = databaseFieldLength($query_result, $i);
		    $info[$name]['_dbflags']  = databaseFieldFlags($query_result, $i);
		}
	}
	databaseFreeResult($query_result);
	if($getmeta){
	    //Get a list of the metadata for this table
	    $metaopts=array('-table'=>"{$db_prefix}_fielddata",'-notimestamp'=>1,'tablename'=>$tablename);
	    if(strlen($field)){$metaopts['fieldname']=$field;}
	    $meta_recs=getDBRecords($metaopts);
	    if(is_array($meta_recs)){
			foreach($meta_recs as $meta_rec){
				$name=$meta_rec['fieldname'];
				if(!isset($info[$name]['_dbtype'])){continue;}
				foreach($meta_rec as $key=>$val){
					if(preg_match('/^\_/',$key)){continue;}
					$info[$name][$key]=$val;
					}
            	}
        	}
		}
	if(count($info)){$databaseCache['getDBFieldInfo'][$dbcachekey]=$info;}
	return $info;
	}
//---------- begin function postgresTableInfo
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postgresTableInfo($table){
	$dbtypemap = array(
	    'bool'		=> 'tinyint',
	    'int2'		=> 'smallint',
	    'int4'		=> 'int',
	    'int8'		=> 'bigint',
	    'float4'	=> 'float',
	    'float8'	=> 'double',
	    'bpchar'	=> 'char',
	    'timestamp'	=> 'datetime'
	);
    $query="SELECT
		a.attname AS name, 
		t.typname AS type,
		a.attlen AS size,
		a.atttypmod AS len,
		a.attnotnull as notnull
    FROM 
		pg_attribute a , pg_class c, pg_type t
    WHERE
		c.relname = '{$table}'
		AND a.attstattarget = -1
    	AND a.attrelid = c.oid AND a.atttypid = t.oid
	";
    $recs=getDBRecords(array('-query'=>$query,'-index'=>'name'));
    $info=array();
    foreach($recs as $field=>$rec){
    	$info[$field]=array(
			'_dbtable' => $table,
			'_dbtype'	=> isset($dbtypemap[$rec['type']])?$dbtypemap[$rec['type']]:$rec['type'],
			'_dbsize'	=> $rec['size']
		);
		if($rec['notnull']=='t'){$info[$field]['_dbflags']='not_null';}
		if($rec['len']<0 && $rec['i'] != "x"){
        	// in case of digits if needed ... (+1 for negative values)
            $info[$field]['_dblength']=(strlen(pow(2,($q["size"]*8)))+1);
        }
        else{
        	$info[$field]['_dblength']=$rec['len'];
        }
	}
	return $info;
}
//---------- begin function getDBFieldNames---------------------------------------
/**
* @deprecated use getDBFields instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function getDBFieldNames($table='',$allfields=0){
	return getDBFields($table,$allfields);
}
//---------- begin function getDBFormRecord
/**
* @describe single record wrapper for getDBFormRecords - returns first record from query
* @param params array - requires either -list or -table or -query
*	_formname string - form name to return from the _forms table
*	other field/value pairs filter the query results. Any field or XML key can be used
* @return array - field/value recordset
* @usage $recs=getDBFormRecord(array('_formname'=>"contact",'gender'=>'F')); return records where _formname=contacts and gender is F
*/
function getDBFormRecord($params=array()){
	$recs=getDBFormRecords($params);
	if(is_array($recs)){
		foreach($recs as $rec){return $rec;}
    	}
    return $recs;
	}
//---------- begin function getDBFormRecords
/**
* @describe returns records and xml data from the _forms table. Any key in the xml data can be used to limit results
* @param params array - requires either -list or -table or -query
*	_formname string - form name to return from the _forms table
*	other field/value pairs filter the query results. Any field or XML key can be used
* @return array - array of field/value recordsets
* @usage $recs=getDBFormRecords(array('_formname'=>"contact",'gender'=>'F')); return records where _formname=contacts and gender is F
*/
function getDBFormRecords($params=array()){
    if(!isset($params['_formname']) && !isset($params['_id'])){return 'getDBFormRecords Error: No formname';}
    $opts=array('-table'=>"_forms",'-notimestamp'=>1);
    $fields=getDBFields("_forms",1);
    $filtered=array();
    foreach($fields as $field){
		if(isset($params[$field])){
			$opts[$field]=$params[$field];
			$filtered[$field]++;
			}
    	}
    //add -order and -where to query opts
    foreach($params as $key=>$val){
		if(!preg_match('/^\-/',$key)){continue;}
		$opts[$key]=$val;
		$filtered[$key]++;
		}
    $forms=getDBRecords($opts);
	if(!is_array($forms)){return "No records";}
	$recs=array();
	//$allkeys=array();
	//$errorcnt=0;
	foreach($forms as $form){
		$rec=array();
		$xml_array=xml2Array($form['_xmldata']);
		if(isset($xml_array['request']['server'])){
			foreach($xml_array['request']['server'] as $key=>$val){
				$rec[$key]=trim($val);
			}
		}
		if(isset($xml_array['request']['data'])){
			foreach($xml_array['request']['data'] as $key=>$val){
				if(preg_match('/^\_(action|honeypot|botcheck|xmldata)$/',$key)){continue;}
				if(preg_match('/^u\_\_/',$key)){continue;}
				if(is_array($val)){$val=implode(':',$val);}
				else{$val=trim(removeHtml($val));}
				if(strlen($val)==0){continue;}
				$rec[$key]=$val;
			}
		}
		//load table column values
		foreach($form as $key=>$val){
			if(preg_match('/^\_(action|honeypot|botcheck|xmldata)$/',$key)){continue;}
			if(strlen($val)){$rec[$key]=$val;}
		}
		//check params for additional filters
		$skip=0;
		foreach($params as $key=>$val){
			if(isset($filtered[$key])){continue;}
			if(!isset($rec[$key])){$skip++;continue;}
			if(preg_match('/^\%(.+?)\%$/',$val,$smatch)){
				if(!stristr($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(preg_match('/(.+?)\%$/',$val,$smatch)){
				if(!stringBeginsWith($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(preg_match('/^%(.+?)/',$val,$smatch)){
				if(!stringEndsWith($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(strtolower($rec[$key]) != strtolower($val)){$skip++;continue;}
        }
        if($skip==0){array_push($recs,$rec);}
	}
	unset($filtered);
	unset($fields);
	return $recs;
}
//---------- begin function getDBIndexes
/**
* @describe returns indexes for specified (or all if none specified) tables
* @param [tables] array - array of table names
* @param [dbname] string - name of database - defaults to current database
* @return array
* @usage $indexes=getDBIndexes(array('note'));
*/
function getDBIndexes($tables=array(),$dbname=''){
	$indexes=array();
	if(count($tables)==0){$tables=getDBTables($dbname);}
	foreach($tables as $table){
		$recs=databaseIndexes($table);
		if(is_array($recs)){
			foreach($recs as $rec){
				$rec['tablename']=$table;
				array_push($indexes,$rec);
	        	}
			}
    	}
    return $indexes;
	}
//---------- begin function getDBRelatedRecords
/**
* @describe returns all records in $table with $values as _id - used in getting related records
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBRelatedRecords($table,$values){
	global $databaseCache;
	$cachekey=strtolower($table).sha1(printValue($values));
	if(isset($databaseCache['getDBRelatedRecords'][$cachekey])){
		return $databaseCache['getDBRelatedRecords'][$cachekey];
		}
	if(is_array($values)){
		sort($values);
		$values=implode(',',$values);
		}
	$getopts=array('-table'=>$table,'-notimestamp'=>1,'-where'=>"_id in ({$values})",'-index'=>'_id');
	//echo $table . printValue($getopts);
	$recs=getDBRecords($getopts);
	$databaseCache['getDBRelatedRecords'][$cachekey]=$recs;
	return $recs;
	}
//---------- begin function getDBRelatedTable
/**
* @describe returns table that matches related field
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBRelatedTable($rfield,$dbname=''){
	$rfield=strtolower($rfield);
	if(strlen($dbname)){$dbname.='.';}
	$tablenames=array(
		$rfield,
		"{$rfield}s",
		preg_replace('/y$/i','ies',$rfield),
		preg_replace('/\_id$/i','',$rfield),
		preg_replace('/\_id$/i','s',$rfield),
		"_{$rfield}s"
		);
	foreach($tablenames as $tablename){
		if(isDBTable($tablename)){return $dbname.$tablename;}
		if(isDBTable("_{$tablename}")){return "{$dbname}_{$tablename}";}
		}
	switch($rfield){
		case '_cuser':
		case '_euser':
		case '_auser':
		case 'user_id':
		case 'owner_id':
		case 'manager_id':
			return $dbname.'_users';
			break;
    	}
	return '';
	}
//---------- begin function getDBSiteStats
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBSiteStats(){
	$rtn='';
	$currentDay=date("Y-m-d");
	$currentWeek=date("W"); 
	$currentMonth=date("m");
	$quarters=array(
		'1'=>1,'2'=>1,'3'=>1,
		'4'=>2,'5'=>2,'6'=>2,
		'7'=>3,'8'=>3,'9'=>3,
		'10'=>4,'11'=>4,'12'=>4
		);
	$currentQuarter=$quarters[(integer)$currentMonth];
	$currentYear=date("Y");
	if(!isDBTable('_access')){
		return '<div class="w_bold w_big">No access table found</div>'."\n";
		}
	$recs=getDBRecords(array('-table'=>"_access",'-where'=>"YEAR(_cdate)=YEAR(NOW())"));
	if(!is_array($recs) || !count($recs)){
		return '<div class="w_bold w_big">No access records yet</div>'."\n";
		}
	$fields=getDBFields("_access");
	$stats=array();
	$totals=array();
	foreach($recs as $rec){
		$page=getDBRecord(array('-table'=>'_pages','name'=>$rec['page']));
		//day
		$cdate=date("Y-m-d",$rec['_cdate_utime']);
		//week
		$cweek=date("W",$rec['_cdate_utime']);
		//month
		$cmonth=date("m",$rec['_cdate_utime']);
		//quarter
		$cquarter=$quarters[(integer)$cmonth];
		//year
		$cyear=date("Y",$rec['_cdate_utime']);
		//http_host
		$field='host';
		$val=$rec['http_host'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//page name
		$field='page';
		$val=$page['name'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_browser
		$field='browser';
		$val=ucwords($rec['remote_browser']).' '.$rec['remote_browser_version'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_os
		$field='operating system';
		$val=$rec['remote_os'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_lang
		$field='language';
		$val=$rec['remote_lang'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		}
	$rowdates=array();
	$days=array(6,5,4,3,2,1,0);
	$rtn .= '<table cellspacing="0" cellpadding="2" border="1" class="w_table">'."\n";
	//Table Header Row
	$rtn .= '	<tr valign="top">'."\n";
	$rtn .= '		<th colspan="2">Stats</th>'."\n";
	//show the last 7 days
	foreach($days as $num){
		$ctime=strtotime("{$num} days ago");
		$cdate=date('D\<\b\r\>d',$ctime);
		$rtn .= '		<th>'.$cdate.'</th>'."\n";
		array_push($rowdates,date("Y-m-d",$ctime));
		}
	//Week
	$rtn .= '		<th>Week<br>'.$currentWeek.'</th>'."\n";
	//show last three months
	$months=array(2,1,0);
	foreach($months as $month){
		$ctime=strtotime("{$month} months ago");
		$cdate=date('M',$ctime);
		$rtn .= '		<th>'.$cdate.'</th>'."\n";
		}
	//show quarters
	$quarters=array(1,2,3,4);
	foreach($quarters as $quarter){
		$rtn .= '		<th>QTD<br>Q'.$quarter.'</th>'."\n";
		}
	//Year
	$rtn .= '		<th>YTD<br>'.$currentYear.'</th>'."\n";
	$rtn .= '	</tr>'."\n";
	//Rows
	$types=array_keys($stats);
	sort($types);
	$row=0;
	$ctype='';
	foreach($types as $type){
		$titles=array_keys($stats[$type]);
		sort($titles);
		foreach($titles as $title){
			$row++;
			$rtn .= '	<tr align="right"';
			if(isFactor($row,2)){$rtn .= ' bgcolor="#e8e8e8"';}
			$rtn .= '>'."\n";
			$xtype=ucwords($type);
			if($ctype==$type){$xtype='';}
			$ctype=$type;
			$rtn .= '		<td align="left"><b>'.$xtype.'</b></td>'."\n";
			$rtn .= '		<td align="left">'.$title.'</td>'."\n";
			//days
			foreach($rowdates as $rdate){
				$rtn .= '		<td';
				if($rdate==$currentDay){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['day'][$rdate])){
					$rtn .= numberFormat($stats[$type][$title]['day'][$rdate]);
					}
				$rtn .= '</td>'."\n";
				}
			//week
			$rtn .= '<td>';
			if(isset($stats[$type][$title]['week'][$currentWeek])){
				$rtn .= numberFormat($stats[$type][$title]['week'][$currentWeek]);
				}
			$rtn .= '</td>';
			//month
			foreach($months as $month){
				$ctime=strtotime("{$month} months ago");
				$cmonth=date('m',$ctime);
				$rtn .= '		<td';
				if($cmonth==$currentMonth){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['month'][$cmonth])){
					$rtn .= numberFormat($stats[$type][$title]['month'][$cmonth]);
					}
				$rtn .= '</td>'."\n";
				}
			//quarters
			foreach($quarters as $quarter){
				$rtn .= '		<td';
				if($quarter==$currentQuarter){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['quarter'][$quarter])){
					$rtn .= numberFormat($stats[$type][$title]['quarter'][$quarter]);
					}
				$rtn .= '</td>'."\n";
				}
			//Year
			$rtn .= '		<td>';
			if(isset($stats[$type][$title]['year'][$currentYear])){
				$rtn .= numberFormat($stats[$type][$title]['year'][$currentYear]);
				}
			$rtn .= '</td>'."\n";
			$rtn .= '	</tr>'."\n";
			}
		//total row for this type
		$rtn .= '	<tr align="right>"';
		$xtype=ucwords($type);
		if($ctype==$type){$xtype='';}
		$ctype=$type;
		$rtn .= '		<th align="right" colspan="2" align="right">'.ucwords($type).' Totals</th>'."\n";
		//days
		foreach($rowdates as $rdate){
			$rtn .= '		<th align="right"';
			if($rdate==$currentDay){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['day'][$rdate])){
				$rtn .= numberFormat($totals[$type]['day'][$rdate]);
				}
			$rtn .= '</th>'."\n";
			}
		//week
		$rtn .= '<th align="right">';
		if(isset($totals[$type]['week'][$currentWeek])){
			$rtn .= numberFormat($totals[$type]['week'][$currentWeek]);
			}
		$rtn .= '</th>';
		//month
		foreach($months as $month){
			$ctime=strtotime("{$month} months ago");
			$cmonth=date('m',$ctime);
			$rtn .= '		<th align="right"';
			if($cmonth==$currentMonth){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['month'][$cmonth])){
				$rtn .= numberFormat($totals[$type]['month'][$cmonth]);
				}
			$rtn .= '</th>'."\n";
			}
		//quarters
		foreach($quarters as $quarter){
			$rtn .= '		<th align="right"';
			if($quarter==$currentQuarter){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['quarter'][$quarter])){
				$rtn .= numberFormat($totals[$type]['quarter'][$quarter]);
				}
			$rtn .= '</th>'."\n";
			}
		//Year
		$rtn .= '		<th align="right">'.numberFormat($totals[$type]['year'][$currentYear]).'</th>'."\n";
		$rtn .= '	</tr>'."\n";
		}
	$rtn .= '</table>'."\n";
	return $rtn;
	}
//---------- begin function getDBTableInfo
/**
* @describe returns meta data associated with table
* @param params array
*	-table string -  table name
*	[-fieldinfo] boolean - return field meta data also - defaults to false
* @return array
* @usage $info=getDBTableInfo(array('-table'=>'note'));
*/
function getDBTableInfo($params=array()){

    if(!isset($params['-table'])){return 'getDBTableInfo Error: No table' . printValue($params);}
    global $USER;
    $table_parts=preg_split('/\./', $params['-table']);
	$infotable="_tabledata";
	$infotablename=$params['-table'];
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$infotable="{$dbname}.{$infotable}";
		$infotablename=implode('.',$table_parts);
		}
    $info=getDBRecord(array('-table'=>$infotable,'tablename'=>$infotablename));
    $info['fields'] = getDBFields($params['-table']);
    $info['table'] = $params['-table'];
	if(isset($params['-fieldinfo']) && $params['-fieldinfo']==true){
		$info['fieldinfo']=getDBFieldInfo($params['-table'],1);
    	}
    //Is the user administrator or not?
    if(isAdmin()){$info['isadmin']=true;}

    else{$info['isadmin']=false;}
	//turn table field data into arrays
	$flds=array('listfields','listfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$info[$fld]=preg_split('/[\r\n\t\s\,\:\ ]+/',$info[$fld]);
			}
		else{$info["default_{$fld}"]=$info['fields'];}
    	}
    $flds=array('sortfields','sortfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$info[$fld]=preg_split('/[\r\n\t\,\:]+/',$info[$fld]);
			}
		else{$info["default_{$fld}"]=$info['fields'][0];}
    	}
    $flds=array('formfields','formfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$rows=preg_split('/[\r\n\,]+/',$info[$fld]);
			$info[$fld]=array();
			foreach($rows as $row){
				//check for html and php
				$row=trim($row);
				if(isXML($row)){array_push($info[$fld],$row);}
				else{
					$line=preg_split('/[\t\s\:]+/',$row);
					array_push($info[$fld],$line);
					}
	            }
			}
		else{$info["default_{$fld}"]=$info['fields'];}
    	}
    //echo printValue($info);
    return $info;
	}
//---------- begin function getDBTableStatus
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBTableStatus(){
	global $PAGE;
	$rtn='';
	$recs=getDBRecords(array('-query'=>"show table status"));
	//add some extra data to the result
	$cnt=count($recs);
	for($i=0;$i<$cnt;$i++){
		$indexes=getDBIndexes(array($recs[$i]['name']));
		$recs[$i]['indexes']=count($indexes);
		$fields=getDBFields($recs[$i]['name']);
		$recs[$i]['fields']=count($fields);
		$recs[$i]['records']=getDBCount(array('-table'=>$recs[$i]['name']));
    }
	$rtn .= buildTableBegin(2,1);
	$sort=$_REQUEST['_sort'];
	unset($_REQUEST['_sort']);
	$tlink=buildUrl($_REQUEST);
	$rtn .= buildTableTH(array(
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=name">Table</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=records">Records</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=data_length">Size</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=fields">Fields</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=indexes">Indexes</a>',
		'Created','Updated','Next ID','Format','Char Set'
		));
	$totals=array();
	if(isset($sort)){$recs=sortArrayByKeys($recs,array($sort=>SORT_ASC));}
	foreach($recs as $rec){
		$totals['records']+=$rec['records'];
		$totals['size']+=$rec['data_length'];
		$totals['indexes']+=$rec['indexes'];
		$totals['fields']+=$rec['fields'];
		$rtn .= '	<tr align="right">'."\n";
		$rtn .= '		<td align="left">'.$rec['name'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['records'].'</td>'."\n";
		$rtn .= '		<td>'.verboseSize($rec['data_length']).'</td>'."\n";
		$rtn .= '		<td>'.$rec['fields'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['indexes'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['create'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['update_time'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['auto_increment'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['row_format'].'</td>'."\n";
		$rtn .= '		<td>'.$rec['collation'].'</td>'."\n";
		$rtn .= '	</tr>'."\n";
    }
    $rtn .= buildTableTH(array('Totals:',$totals['records'],verboseSize($totals['size']),$totals['fields'],$totals['indexes'],'','','','',''),array('align'=>"right"));
	$rtn .= buildTableEnd();
	return $rtn;
}
//---------- begin function getDBQuery--------------------
/**
* @describe builds a database query based on params
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query string
* @usage $query=getDBQuery(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBQuery($params=array()){
	if(!isset($params['-table'])){return 'getDBQuery Error: No table' . printValue($params);}
	//get field info for this table
	//echo "getting info for {$params['-table']}<br>\n";
	$info=getDBFieldInfo($params['-table']);
	if(!is_array($info)){return $info;}
	$loopfields=array();
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$loopfields=$params['-fields'];}
		else{$loopfields=preg_split('/\,+/',trim($params['-fields']));}
    	}
    else{$loopfields=array_keys($info);}
    //now add the _utime to any date fields
    $fields=array();
    foreach ($loopfields as $field){
		array_push($fields,$field);
		//add timestamp unless $params['-notimestamp'] is set
		if(!isset($params['-notimestamp']) && isset($info[$field]['_dbtype'])){
			if(preg_match('/^(datetime|date|timestamp)$/i',$info[$field]['_dbtype'])){
				if(isMysqli() || isMysql()){
					array_push($fields,'UNIX_TIMESTAMP('.$field.') as '.$field.'_utime');
				}
				elseif(isPostgreSQL()){
					array_push($fields,$field.'::abstime::int4 as '.$field.'_utime');
					}
				elseif(isMssql()){
					//MS SQL Unix_timestamp equivilent: select datediff(s, '19700101', <fieldname>)
					array_push($fields,'datediff(s, \'19700101\', '.$field.') as '.$field.'_utime');
				}
			}
			elseif(preg_match('/^time$/i',$info[$field]['_dbtype'])){
				if(isMysqli() || isMysql()){
					array_push($fields,'TIME_TO_SEC('.$field.') as '.$field.'_seconds');
				}
				elseif(isMssql()){
					//MS SQL Unix_timestamp equivilent: select datediff(s, '19700101', <fieldname>)
					array_push($fields,'datediff(s, \'19700101\', '.$field.') as '.$field.'_seconds');
				}
			}
		}
	}
	$query='select ';
	if(isMssql() && isset($params['-limit'])){
		$query .= "top({$params['-limit']}) ";
		}
	$query .= implode(',',$fields).' from ' . $params['-table'];
	if(isset($params['-where'])){
        $query .= ' where '.$params['-where'];
        if(isset($params['-filter'])){$query .= " and ({$params['-filter']})";}
        if(isset($params['-search'])){
			if(preg_match('/^where (.+)/i',$params['-search'],$smatch)){
				$query .= ' and ('.$smatch[1].')';
            	}
            elseif(isset($params['-searchfield'])){
				$field=$params['-searchfield'];
				if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
					if(isNum($params['-search'])){
                		$query .= " and {$field}={$params['-search']}";
					}
					else{
						$query .= " and {$field}='{$params['-search']}'";
					}
				}
				else{
					if(stringContains($params['-search'],'%')){
						$query .= " and {$field} like '{$params['-search']}'";
					}
					else{
						$query .= " and {$field} = '{$params['-search']}'";
					}
                }
			}
            else{
				$ors=array();
				foreach(array_keys($info) as $field){
					if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
						if(isNum($params['-search'])){
							array_push($ors,"{$field} = {$params['-search']}");
                        	}
	                	}
	                else{array_push($ors,"{$field} like '%{$params['-search']}%'");}
					}
				if(count($ors)){
					$query .= ' and ('.implode(' or ',$ors).')';
	            	}
				}
        	}
    	}
	else{
		if(isset($params['-filter'])){$query .= " where ({$params['-filter']})";}
		else{$query .= ' where 1=1';}
		/* Filter the query based on params */
		foreach($params as $key=>$val){
			//skip keys that begin with a dash
			if(preg_match('/^\-/',$key)){continue;}
			if(!isset($info[$key]['_dbtype'])){continue;}
			if($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='real'){
				$query .= " and {$key}={$val}";
				}
			else{
				$val=databaseEscapeString($val);
				$query .= " and {$key}='{$val}'";
	        	}
	    	}
	    if(isset($params['-search'])){
			if(!stringBeginsWith($params['-search'],'where')){
				$params['-search']=databaseEscapeString($params['-search']);
			}
			if(preg_match('/^where (.+)/i',$params['-search'],$smatch)){
				$query .= ' and ('.$smatch[1].')';
            	}
            elseif(isset($params['-searchfield'])){
				$field=$params['-searchfield'];
				if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
					if(isNum($params['-search'])){
                		$query .= " and {$field}={$params['-search']}";
					}
					else{
						$query .= " and {$field}='{$params['-search']}'";
					}
				}
				else{
					if(stringContains($params['-search'],'%')){
						$query .= " and {$field} like '{$params['-search']}'";
					}
					else{
						$query .= " and {$field} = '{$params['-search']}'";
					}
                }
			}
			else{
				$ors=array();
				foreach(array_keys($info) as $field){
					if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
						if(isNum($params['-search'])){
							array_push($ors,"{$field} = {$params['-search']}");
                        	}
	                	}
	                else{array_push($ors,"{$field} like '%{$params['-search']}%'");}
					}
				if(count($ors)){
					$query .= ' and ('.implode(' or ',$ors).')';
	            	}
	            }
        	}
	    }
	//Set order by if defined
    if(isset($params['-group'])){$query .= ' group by '.$params['-group'];}
	//Set order by if defined
    if(isset($params['-order'])){$query .= ' order by '.$params['-order'];}
    //Set limit if defined
    if((isMysql() || isMysqli()) && isset($params['-limit'])){$query .= ' limit '.$params['-limit'];}
	//if($params['-debug']){debugValue($query);}
    return $query;
    }
//---------- begin function getDBRecord-------------------
/**
* @describe returns a single multi-dimensional record based on params
* @param params array - returns a key/value array for each recordset found
*	[-table] string - table to query
*	[-query] string - exact query to use instead of passing in params
*	[-where] string - where clause to filter results by
*	[-index] string - field to use as index.  i.e  '-index'=>'_id'
*	[-json] array - fields to decode as json.  returns decoded json values into field_json key
*	[-random] integer  - number of random records to return from the results
*	[-model] boolean - process results through the GetRecord model function
*	[-relate] mixed - field/table pairs of fields to get related records from other tables
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return array
* @usage $rec=getDBRecord(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBRecord($params=array()){
	if(!isset($params['-table']) && !isset($params['-query'])){return "getDBRecord Error: no table or query defined" . printValue($params);}
	if(isset($params['-random'])){
		$params['-random']=1;
		unset($params['-limit']);
		}
     else{$params['-limit']=1;}
	$list=getDBRecords($params);
	if(!is_array($list)){return $list;}
	if(!count($list)){return null;}
	if(!isset($list[0])){return null;}
	$rec=array();
	//get the first record and return it. the index may not be zero if they indexed it differently.
	foreach($list as $index=>$crec){
		foreach($crec as $key=>$val){
			$rec[$key]=$val;
		}
    	break;
	}
	if(count($rec)){
		return $rec;
		}
	return null;
	}
//---------- begin function addDBRecord--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=getDBRecordById('comments',7);
*/
function getDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "getDBRecordById Error: No Table";}
	if($id == 0){return "getDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=getDBRecord($recopts);
	return $rec;
	}
//---------- begin function getDBRecords-------------------
/**
* @describe returns a multi-dimensional array of records found
* @param params array - returns a key/value array for each recordset found
*	[-table] string - table to query
*	[-query] string - exact query to use instead of passing in params
*	[-where] string - where clause to filter results by
*	[-index] string - field to use as index.  i.e  '-index'=>'_id'
*	[-json] array - fields to decode as json.  returns decoded json values into field_json key
*	[-random] integer  - number of random records to return from the results
*	[-model] boolean - process results through the GetRecord model function
*	[-relate] mixed - field/table pairs of fields to get related records from other tables
*	[-notimestamp] boolean - if true disables adding extra _utime data to date and datetime fields. Defaults to false
*	[-model] boolean - set to false to disable model functionality
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return array
* @usage $recs=getDBRecords(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBRecords($params=array()){
	$function='getDBRecords';
	global $CONFIG;
	global $databaseCache;
	//change database if requested
	if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
		if(!databaseSelectDb($params['-dbname'])){
			setWasqlError(debug_backtrace(),getDBError());
			return getDBError();
			}
		}
	if(isset($params['-query'])){$query=$params['-query'];}
	elseif(isset($params['-table'])){
		$query=getDBQuery($params);
		}
	else{
		if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
			if(!databaseSelectDb($CONFIG['dbname'])){
				setWasqlError(debug_backtrace(),getDBError());
				return getDBError();
				}
			}
		setWasqlError(debug_backtrace(),"No table");
		return "No table";
	}
	//do we already have a query for this stored?
	$query_sha=sha1($CONFIG['dbname'].$query);
	if(!isset($params['-nocache']) && isset($databaseCache['getDBRecords'][$query_sha]) && is_array($databaseCache['getDBRecords'][$query_sha])){
		return $databaseCache['getDBRecords'][$query_sha];
    }
    $start=microtime(true);
    //-json
    if(isset($params['-json'])){
		$jsonfields=array();
		if(!is_array($params['-json'])){
        	$params['-json']=preg_split('/\,/',trim($params['-json']));
		}
		foreach($params['-json'] as $jsonfield){
        	$jsonfields[$jsonfield]=1;
		}
	}
	// Perform Query
	$query_result=@databaseQuery($query);
  	if(!$query_result){
		$e=getDBError();
		echo printValue($e);exit;
		if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
			if(!databaseSelectDb($CONFIG['dbname'])){
				return setWasqlError(debug_backtrace(),getDBError(),$query);
				}
			}
		return setWasqlError(debug_backtrace(),$e,$query);
  		}
	$rows   = databaseNumRows($query_result);
	if(!$rows){
		if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
			if(!databaseSelectDb($CONFIG['dbname'])){return setWasqlError(debug_backtrace(),getDBError(),$query);}
			}
		return null;
		}
	$list=array();
	$x=0;
	$randompick=0;
	$random=array();
	if(isset($params['-random']) && isNum($params['-random'])){
		$cnt=databaseNumRows($query_result);
		$max=$cnt-1;
		while((count($random) < $params['-random']*10) && (count($random) < $max)){
			$r=rand(0,$max);
			if(isNum($r) && !in_array($r,$random)){$random[]=$r;}
        	}
        $randompick=1;
		}
	$rx=0;
	while ($row = databaseFetchAssoc($query_result)) {
		if($randompick==1){
			$rx+=1;
			if(count($list) >= count($random)){
				break;
			}
			if(!in_array($rx,$random)){continue;}
			//get out of the loop once we have filled our random count
        }
		foreach($row as $key=>$val){
			if(!isset($params['-lowercase']) || $params['-lowercase'] != false){$key=strtolower($key);}
			if(isset($params['-index'])){
				if(is_array($params['-index']) && count($params['-index'])){
					$indexes=array();
					foreach($params['-index'] as $fld){
						$indexes[] = $row[$fld];
						}
					$index=implode(',',$indexes);
					$list[$index][$key]=$val;
                	}
				elseif(strlen($params['-index']) && !isNum($params['-index']) && isset($row[$params['-index']])){
					$index=$row[$params['-index']];
					$list[$index][$key]=$val;
                	}
                //-json?
                if(isset($jsonfields[$key])){
					$list[$index]["{$key}_json"]=json_decode($val,true);
				}
            }
			else{
				$list[$x][$key]=$val;
				//-json?
                if(isset($jsonfields[$key])){
					$list[$x]["{$key}_json"]=json_decode($val,true);
				}
			}
		}
		if($randompick==1 && count($list) >= $params['-random']){break;}
		//if(isset($list[$x]) && is_array($list[$x])){ksort($list[$x]);}
		$x++;
		}
	//Free the resources associated with the result set
	databaseFreeResult($query_result);
	//determine fields returned
	foreach($list as $i=>$r){
		$fields=array_keys($r);
		break;
		}
	if(!isset($params['-nolog']) || $params['-nolog'] != 1){
		$fieldstr=implode(',',$fields);
		$row_count=count($list);
		if(isset($params['-table'])){
			logDBQuery($query,$start,$function,$params['-table'],$fieldstr,$row_count);
			}
		}
	if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
		if(!databaseSelectDb($CONFIG['dbname'])){
			return setWasqlError(debug_backtrace(),getDBError());
			}
		}
	//get related
	$related=array();
	if(isset($params['-table']) && isset($params['-relate'])){
		$table_parts=preg_split('/\./', $params['-table']);
		$dbname='';
		if(count($table_parts) > 1){
			$dbname=array_shift($table_parts);
			}
		if(isNum($params['-relate']) && $params['-relate']==1){
			$xinfo=getDBFieldMeta($params['-table'],"tvals,dvals,inputtype");
			//check for -norelate fields to skip
			$skipfields=array();
			if(isset($params['-norelate'])){
            	if(is_array($params['-norelate'])){$skipfields=$params['-norelate'];}
            	else{
                	$skipfields=preg_split('/[,:;]+/',$params['-norelate']);
				}
			}
			foreach($fields as $field){
				//skip field if it is not a valid field or if it is a -norelate field
				if(!isset($xinfo[$field]) || !is_array($xinfo[$field]) || in_array($field,$skipfields)){continue;}
				$tvals=trim($xinfo[$field]['tvals']);
				$dvals=trim($xinfo[$field]['dvals']);
				
				if(preg_match('/(select|checkbox)/i',$xinfo[$field]['inputtype']) && strlen($tvals) && strlen($dvals) && !preg_match('/^select/i',$tvals)){
                	//simple select list - not a query
                	$tmap=array();
                	$tmap=mapDBDvalsToTvals($params['-table'],$field);
                	reset($list);
					foreach($list as $i=>$r){
						$rval=$r[$field];
						if(isset($tmap[$rval])){$related[$field][$rval]=$tmap[$rval];}
						elseif(strlen($rval) && preg_match('/\:/',$rval)){
                        	$rvals=preg_split('/\:/',$rval);
                        	$dvals=array();
                        	foreach($rvals as $rval){
								 if(isset($tmap[$rval])){$dvals[$rval]=$tmap[$rval];}
							}
							if(count($dvals)){$related[$field][$r[$field]]=$dvals;}
						}
					}
				}
				else{
					$rtable=getDBRelatedTable($field,$dbname);
					if(strlen($rtable)){
						$ids=array();
						reset($list);
						foreach($list as $i=>$r){
							if(isNum($r[$field]) && $r[$field] > 0 && !in_array($r[$field],$ids)){$ids[]=$r[$field];}
							elseif(strlen($r[$field]) && preg_match('/\:/',$r[$field])){
	                        	$rvals=preg_split('/\:/',$r[$field]);
	                        	$dvals=array();
	                        	foreach($rvals as $rval){
									if(isNum($rval) && $rval > 0 && !in_array($rval,$ids)){$ids[]=$rval;}
								}
							}
						}
						if(count($ids)){
							$related[$field]=getDBRelatedRecords($rtable,$ids);
						}
	                }
	            }
            }
        }
        elseif(is_array($params['-relate'])){
			foreach($params['-relate'] as $field=>$rtable){
				if(isDBTable($rtable)){
					$ids=array();
					reset($list);
					foreach($list as $i=>$r){
						if(isNum($r[$field]) && !in_array($r[$field],$ids)){$ids[]=$r[$field];}
						}
					if(count($ids)){
						$related[$field]=getDBRelatedRecords($rtable,$ids);
						}
                	}
            	}
        	}
    	}
    if(count($related)){
		foreach($related as $rfield=>$recs){
			reset($list);
			foreach($list as $i=>&$r){
				if(!strlen(trim($r[$rfield]))){continue;}
				$rval=$r[$rfield];
				if(strlen($rval) && preg_match('/\:/',$rval)){
					$xrvals=preg_split('/\:/',$rval);
					foreach($xrvals as $xrval){
						if(isset($recs[$rval][$xrval])){$r["{$rfield}_ex"][$xrval]=$recs[$rval][$xrval];}
						else{$r["{$rfield}_ex"][$xrval]=$recs[$xrval];}
                    }
				}
				else{
					$r["{$rfield}_ex"]=$recs[$rval];
				}
				ksort($r);
            }
        }
    }
    //cache internal table select queries
    if(!isset($params['-nocache']) && !isset($_SERVER['WaSQL_AdminUserID']) && isset($params['-table']) && preg_match('/^select/i',$query) && preg_match('/^\_/',$params['-table']) && !preg_match('/^\_(pages|templates)/i',$params['-table'])){
    	$databaseCache['getDBRecords'][$query_sha]=$list;
		}
	if(is_array($list) && count($list)){
		//check for -indexes
		if(isset($params['-indexes'])){
			$indexes=$params['-indexes'];
			$newlist=array();
			foreach($list as $rec){
				if(is_array($indexes)){
					//$grouplist[$key1][$key2][$key3...]=$rec
                	$keys=array();
                	foreach($indexes as $key){
                    	$keys[]=$rec[$key];
					}
					switch(count($keys)){
                    	case 1:$newlist[$keys[0]][]=$rec;break;
                    	case 2:$newlist[$keys[0]][$keys[1]][]=$rec;break;
                    	case 3:$newlist[$keys[0]][$keys[1]][$keys[2]][]=$rec;break;
                    	case 4:$newlist[$keys[0]][$keys[1]][$keys[2]][$keys[3]][]=$rec;break;
                    	case 5:$newlist[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]][]=$rec;break;
					}
					continue;
				}
				$key=$rec[$indexes];
				$newlist[$key][]=$rec;
            }
            return $newlist;
		}
		//process GetRecord model functions as long as -model is not false
		if(isset($params['-table']) && (!isset($params['-model']) || ($params['-model']))){
			$model=getDBTableModel($params['-table']);
			$model_table=$params['-table'];
			//check to see if they passed a databasename with table
			$table_parts=preg_split('/\./', $model_table);
			if(count($table_parts) > 1){
				$dbname=array_shift($table_parts);
				$model_table=implode('.',$table_parts);
				}
			if(isset($model['functions']) && strlen(trim($model['functions']))){
		    	$ok=includePHPOnce($model['functions'],"{$model_table}-model_functions");
		    	//look for Before trigger
		    	if(function_exists("{$model_table}GetRecord")){
					foreach($list as $i=>$rec){
		        		$rec=call_user_func("{$model_table}GetRecord",$rec);
		        		$list[$i]=$rec;
					}
				}
			}
		}
		return $list;
		}
	return null;
	}
//---------- begin function getDBSchema--------------------
/**
* @describe returns schema array for specified (or all if none specified) tables
* @param tables array - if not specifed then all tables are returned
* @param force boolean - force cache to be cleared
* @return array
* @usage 
*	$schema=getDBSchema('comments');
*	$schema=getDBSchema(array('comments','notes'));
*/
function getDBSchema($tables=array(),$force=0){
	if(!is_array($tables)){$tables=array($tables);}
	global $databaseCache;
	$schema=array();
	if(count($tables)==0){$tables=getDBTables();}
	$cachekey=implode(',',$tables);
	if($force==0 && isset($databaseCache['getDBSchema'][$cachekey])){
		return $databaseCache['getDBSchema'][$cachekey];
		}
	foreach($tables as $table){
		$recs=databaseDescribeTable($table);
		if(isset($recs['error'])){return $recs['error'];}
		elseif(!is_array($recs)){return "{$table} does not exist";}
		$i=0;
		foreach($recs as $rec){
			$i++;
			$rec['_id']=$i;
			$rec['tablename']=$table;
			if(strlen($rec['default']) && !isNum($rec['default'])){
				$rec['default']="'{$rec['default']}'";
			}
			$schema[]=$rec;
        }
    }
    $sortfield=isset($_REQUEST['_sort'])?$_REQUEST['_sort']:'field';
    $direction='SORT_ASC';
    if(preg_match('/^(.+?)\ (asc|desc)/i',$sortfield,$smatch)){
		$sortfield=$smatch[1];
		$direction='SORT_'.strtoupper($smatch[2]);
    	}
    $schema=sortArrayByKeys($schema, array($sortfield=>$direction));
    if($force==0){$databaseCache['getDBSchema'][$cachekey]=$schema;}
    return $schema;
	}
//---------- begin function getDBSchemaText--------------------
/**
* @describe returns schema text for specified table
* @param tables string - tablename
* @param force boolean - force cache to be cleared
* @return text
* @usage $schema=getDBSchemaText('comments');
*/
function getDBSchemaText($table,$force=0){
	if(!is_array($table)){$table=array($table);}
	$list=getDBSchema($table,$force);
	$txt='';
	foreach($list as $field){
		if(preg_match('/^\_/',$field['field'])){continue;}
		$type=$field['type'];
		if($field['null']=='NO'){$type .= ' NOT NULL';}
		else{$type .= ' NULL';}
		if($field['key']=='PRI'){$type .= ' Primary Key';}
		elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
		if(strlen($field['default'])){
			$type .= ' Default '.$field['default'];
			}
		if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
		$txt .= trim("{$field['field']} {$type}")."\r\n";
        }
    return $txt;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function updateDBSchema($table,$schema,$new=0){
	$lines=preg_split('/[\r\n]+/',trim($schema));
	$cfields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "int NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "int NULL",
		);
	$table_parts=preg_split('/\./', $table);
	$updatetable=$table;
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$table=implode('.',$table_parts);
	}
	switch(strtolower($table)){
		case '_forms':
			$cfields['_formname']="varchar(100) NOT NULL";
			$cfields['_xmldata']="text NOT NULL";
			break;
		case '_users':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_env']="text NULL";
			$cfields['_sid']="varchar(150) NULL";
			if(isPostgreSQL()){$cfields['_adate']=str_replace('datetime','timestamp',$cfields['_adate']);}
			break;
		case '_pages':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_amem']=databaseDataType('bigint')." NULL";
			$cfields['_auser']="integer NOT NULL Default 0";
			$cfields['_env']="text NULL";
			$cfields['_template']="integer NOT NULL Default 1";
			break;
		case '_templates':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_auser']="integer NOT NULL Default 0";
			break;
    }
	$fields=array();
	foreach($lines as $line){
		if(!strlen(trim($line))){continue;}
		list($name,$type)=preg_split('/[\s\t]+/',$line,2);
		if(!strlen($type)){continue;}
		if(!strlen($name)){continue;}
		if(preg_match('/^\_/',$name)){continue;}
		$fields[$name]=$type;
        }
    if(count($fields)){
		//add common fields
		foreach($cfields as $key=>$val){$fields[$key]=$val;}
		if($new==1){
        	$ok = createDBTable($updatetable,$fields);
			}
		else{
        	$ok = alterDBTable($updatetable,$fields);
			}
        //echo $ok . "alterDBTable({$updatetable})" . printValue($fields);
        return 1;
        }
    return 0;
	}

//---------- begin function getDBVersion--------------------
/**
* @describe returns database version
* @return string
* @usage $version=getDBVersion();
*/
function getDBVersion(){
	return databaseVersion();
}
//---------- begin getDBUser
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBUser($params=array()){
	if(!count($params)){return null;}
	$params['-table']="_users";
	return getDBRecord($params);
}
//---------- begin function getDBUserById
/**
* @describe returns user record with recordID specified
* @param id integer - record ID to return
* @param [fields] array - array of fields to return. return values are separated by a space
* @return mixed
* @usage 
*	$fullname=getDBUserById(10,array('firstname','lastname')); - returns "jon doe";
*	$user_rec=getDBUserById(10); - returns the entire record array
*/
function getDBUserById($id=0,$fields=array()){
	if($id == 0){return null;}
	$cuser=getDBRecord(array('-table'=>'_users','_id'=>$id));
	if(!is_array($cuser)){return null;}
	if(count($params)){
		//only return certain fields
		$vals=array();
		foreach($params as $fld){array_push($vals,$cuser[$fld]);}
		return implode(' ',$vals);
    	}
	return $cuser;
	}
//---------- begin function listDBRecords
/**
* @describe returns an html table of records
* @param params array - requires either -list or -table
*	[-list] array - getDBRecords array to use
*	[-table] string - table name
*	[-hidesearch] -  hide the search form
*	[-limit] mixed - query record limit
*	other field/value pairs filter the query results
* @param [customcode] string - html code to append to end - defaults to blank
* @return string - html table to display
* @usage 
*	<?=listDBRecords(array('-table'=>'notes'));?>
*	<?=listDBRecords(array('-list'=>$recs));?>
*/
function listDBRecords($params=array(),$customcode=''){
	global $PAGE;
	global $USER;
	global $CONFIG;
	$skips=array('align','style','color','class','bgcolor','displayname','nowrap','inputtype',
		'tvals','dvals','relate','ex','checkmark','checkbox','check','image','dateformat','dateage',
		'onmouseover','onmouseout','onhover','href','onclick','eval','title',
		'spellcheck','max','min','pattern','placeholder','readonly','step'
		);
	$idfield=isset($params['-id'])?$params['-id']:'_id';
	$rtn='';
	if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '<div id="cronlist">'."\n";}
	elseif(isset($params['-ajax']) && (integer)$params['-ajax']==1){
		$params['-ajaxid']='list_'.sha1(printValue($params));
		$rtn .= '<div id="'.$params['-ajaxid'].'">'."\n";
	}
	elseif(isset($params['-ajaxid'])){
		$rtn .= '<div id="'.$params['-ajaxid'].'">'."\n";
	}
    //determine sort
    $possibleSortVals=array($params['-order'],$params['-orderby'],$_REQUEST['_sort'],'none');
    $sort=setValue($possibleSortVals);
    if($sort=='none'){
		$sort='';
    	if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(isAdmin()){
				if(is_array($tinfo['sortfields'])){$sort=implode(',',$tinfo['sortfields']);}
				}
			else{
				if(is_array($tinfo['sortfields_mod'])){$sort=implode(',',$tinfo['sortfields_mod']);}
            	}
			}
		}
	if(strlen($sort)){$params['-order']=$sort;}
	if(isset($_REQUEST['_sort']) && !strlen(trim($_REQUEST['_sort']))){unset($_REQUEST['_sort']);}
	if(isset($_REQUEST['_sort'])){$params['-order']=$_REQUEST['_sort'];}
	if(isset($params['-list']) && is_array($params['-list'])){$list=$params['-list'];}
	else{
		if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
			$params['-search']=$_REQUEST['_search'];
        	}
        if(isset($_REQUEST['_searchfield']) && strlen($_REQUEST['_searchfield'])){
			$params['-searchfield']=$_REQUEST['_searchfield'];
        	}
        if(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1){
			$filterfield="_cdate";
			$wheres=array();
			if(isset($_REQUEST['date_from'])){
				$sdate=date2Mysql($_REQUEST['date_from']);
				$wheres[] = "DATE({$filterfield}) >= '{$sdate}'";
				}
			if(isset($_REQUEST['date_to'])){
				$sdate=date2Mysql($_REQUEST['date_to']);
				$wheres[] = "DATE({$filterfield}) <= '{$sdate}'";
				}
			$opts=array('_formname'=>"ticket");
			if(count($wheres)){
				$params['-where']=implode(' and ',$wheres);
				}
        	}
        if(!isset($_REQUEST['_sort']) && !isset($_REQUEST['-order']) && !isset($params['-order'])){
			$params['-order']="{$idfield} desc";
        	}
		$rec_count=getDBCount($params);
		if(isset($params['-limit']) && isNum($params['-limit']) && $params['-limit'] > 0){
			$paging=getDBPaging($rec_count,$params['-limit']);
        	}
        elseif(isset($USER['paging']) && isNum($USER['paging'])){
			$paging=getDBPaging($rec_count,$USER['paging']);
        	}
        elseif(isset($CONFIG['paging']) && isNum($CONFIG['paging'])){
			$paging=getDBPaging($rec_count,$CONFIG['paging']);
        	}
		else{$paging=getDBPaging($rec_count);}
		//echo $rec_count . printValue($paging);
		if(isset($paging['-limit'])){
			$params['-limit']=$paging['-limit'];
			}
		if(!isset($params['-hidesearch'])){$paging['-search']=true;}
		//add any filters - excluding keys that end in _{skip}
		foreach($params as $pkey=>$pval){
			$skipkey=0;
			foreach($skips as $skip){
            	if(stringEndsWith($pkey,"_{$skip}")){$skipkey=1;break;}
			}
			if($skipkey==1){continue;}
			if(preg_match('/^\-/',$pkey) && !preg_match('/^\-(ajaxid|search|where|order|pagingformname|formname)/i',$pkey)){continue;}
			if(preg_match('/\_(align|style|color|c)$/',$pkey)){continue;}
			$paging[$pkey]=$pval;
			}
		if(!isset($params['-order']) && isset($_REQUEST['_sort'])){
        	$paging['-order']=$_REQUEST['_sort'];
		}
		if(!isset($params['-search']) && isset($_REQUEST['_search'])){
        	$paging['-search']=$_REQUEST['_search'];
		}
		foreach($_REQUEST as $pkey=>$pval){
        	if(stringBeginsWith($pkey,'_')){$paging[$pkey]=$_REQUEST[$pkey];}
		}
		if(isset($params['-table'])){
        	$paging['-table']=$params['-table'];
		}
		//$rtn .= printValue($paging).printValue($params);
		$rtn .= buildDBPaging($paging);
		if(!isset($params['-fields']) && isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(is_array($tinfo)){
				$xfields=array();
				if(isset($tinfo['listfields']) && is_array($tinfo['listfields'])){
					$xfields=$tinfo['listfields'];
					}
				elseif(isset($tinfo['default_listfields']) && is_array($tinfo['default_listfields'])){
					$xfields=$tinfo['default_listfields'];
					}
				if(count($xfields)){
					array_unshift($xfields,$idfield);
					$params['-fields']=implode(',',$xfields);
					}
	            }
	        if(isset($params['-fields'])){
				$params['-fields']=preg_replace('/\,+$/','',$params['-fields']);
				$params['-fields']=preg_replace('/^\,+/','',$params['-fields']);
	        	$params['-fields']=preg_replace('/\,+/',',',$params['-fields']);
				}
	        }
		//secondary sort
		if(isset($params['-order']) && isset($params['-order2'])){
			$params['-orderX']=$params['-order'];
	    	$params['-order'] .= ", {$params['-order2']}";
		}
		$list=getDBRecords($params);
		if(isset($params['-orderX'])){
			$params['-order']=$params['-orderX'];
		}
		//echo printValue($list) . printValue($params);
		}
	if(isset($list['error'])){return $list['error'];}
	if(!is_array($list)){
		$no_records=isset($params['-norecords'])?$params['-norecords']:'No records found';
		$rtn .= "<div>{$no_records}</div>";
		if(strlen($list)){
			$rtn .= $list;
			//$rtn .= printValue($params);
			}
		if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '</div>'."\n";}
		return $rtn;
		}
	$list_cnt=count($list);
	$listform=0;
	$fields=array($idfield);
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$fields=$params['-fields'];}
		else{$fields=explode(',',$params['-fields']);}
    	}
    elseif(isset($params['-query'])){
		$fields=array();
		foreach($list as $rec){
			foreach($rec as $key=>$val){
				$fields[]=$key;
				}
			break;
        	}
        //echo $params['-query'] . printValue($fields);
    	}
    else{
		//get fields in the _tabledata
		$tdata=0;
		if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(is_array($tinfo)){
				//echo printValue($tinfo);
				if(is_array($tinfo['listfields'])){
					$fields=$tinfo['listfields'];
					//echo "listfields";
					}
				elseif(is_array($tinfo['default_listfields'])){
					//echo "default";
					$fields=$tinfo['default_listfields'];
					}
				array_unshift($fields,$idfield);
				$tdata=1;
            	}
            }
        if($tdata==0){
			//no fields defined so get all user defined fields, except for blob data
			//echo "table:{$table}" . printValue($list);
			foreach ($list[0] as $field=>$val){
				if(preg_match('/^\_/',$field)){continue;}
				$fields[]=$field;
		    	}
			}
    	}
    //remove fields that are not valid
	if(isset($params['-table'])){
		$info=getDBFieldMeta($params['-table'],"displayname,editlist");
		$parts=array();
	    foreach($_REQUEST as $key=>$val){
			if(preg_match('/^(edit|add)\_(result|id|table)$/i',$key)){continue;}
			if($key=='_action' && $val=='multi_update'){continue;}
			if(preg_match('/^(GUID|PHPSESSID)$/i',$key)){continue;}
			if(preg_match('/\_[0-9]+$/i',$key)){continue;}
			if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
			if(is_array($val) || strlen($val) > 255){continue;}
			$parts[$key]=$val;
	    	}
	    $parts['_action']="multi_update";
	    $parts['_table']=$params['-table'];
	    $parts['_fields']=implode(':',$fields);
	    //$rtn .= printValue($parts);
		$rtn .= buildFormBegin('',$parts);
		$listform=1;
    	}
    elseif(isset($params['-form']) && is_array($params['-form'])){
		$rtn .= buildFormBegin('',$params['-form']);
    	}
    //set table class
	$tableclass='w_table';
	//add the sortable class if there is only one page of records or is sorting is turned off
	if(!isset($paging['-next']) || isset($params['-nosort'])){
		$tableclass .= ' sortable';
		$params['-nosort']=1;
		}
	//check for tableclass override
	if(isset($params['-tableclass'])){
		$tableclass=$params['-tableclass'];
	}
	$tablestyle='';
	if(isset($params['-tablestyle'])){
		$tablestyle=' style="'.$params['-tablestyle'].'"';
	}
	$tableid='';
	if(isset($params['-tableid'])){
		$tablestyle=' id="'.$params['-tableid'].'"';
	}
	$rtn .= '<table class="'.$tableclass.'"'.$tablestyle.$tableid.' cellspacing="0" cellpadding="2" border="1">'."\n";

    //build header row
    $rtn .= "	<thead><tr>\n";
    if(isset($params['-table']) && $params['-table']=='_users' && $params['-icons']){
		$rtn .= '		<td><img src="/wfiles/icons/users/users.gif" border="0"></td>'."\n";
    	}
    //allow user to pass in what fields to display as -listfields
    if(isset($params['-listfields'])){
    	if(is_array($params['-listfields'])){$listfields=$params['-listfields'];}
    	else{$listfields=preg_split('/\,/',$params['-listfields']);}
	}
	else{$listfields=$fields;}
	foreach($listfields as $fld){
		if(isset($info[$fld]['displayname']) && strlen($info[$fld]['displayname'])){$col=$info[$fld]['displayname'];}
		elseif(isset($params[$fld."_displayname"])){$col=$params[$fld."_displayname"];}
		else{
			$col=preg_replace('/\_+/',' ',$fld);
			$col=ucwords($col);
			}
		$arr=$_REQUEST;
		if(isset($_REQUEST['add_result']) || isset($_REQUEST['edit_result'])){$arr=array();}
		foreach($arr as $key=>$val){
			if(is_array($val) || strlen($val)>255 || isXML($val)){unset($arr[$key]);}
			}
		foreach($params as $key=>$val){
        	if(preg_match('/^\-/',$key)){continue;}
        	$arr[$key]=$val;
		}
		$arr['_sort']=$fld;
		foreach($fields as $ufld){
			unset($arr[$ufld]);
			foreach($skips as $skip){
            	$sfield=$ufld.'_'.$skip;
            	unset($arr[$sfield]);
			}
		}
		unset($arr['x']);
		unset($arr['y']);
		$arrow='';
		if(isset($_REQUEST['_sort'])){
			if($_REQUEST['_sort']==$fld){
				$arr['_sort'] .= ' desc';
				$arrow=' <img src="/wfiles/up.gif" border="0" alt="ascending">';
				}
			elseif($_REQUEST['_sort']== "{$fld} desc"){
				$arrow=' <img src="/wfiles/down.gif" border="0" alt="descending">';
				}
			}
		elseif(isset($params['-order'])){
            if($params['-order']==$fld){
				$arr['order'] .= ' desc';
				$arrow=' <img src="/wfiles/up.gif" border="0" alt="ascending">';
				}
			elseif($params['-order']== "{$fld} desc"){
				$arrow=' <img src="/wfiles/down.gif" border="0" alt="descending">';
				}
        	}
        $title=isset($params[$fld."_title"])?' title="'.$params[$fld."_title"].'"':'';
        if(isset($params[$fld."_checkbox"]) && $params[$fld."_checkbox"]==1){
        	$rtn .= '		<th'.$title.' nowrap><label for="'.$fld.'_checkbox"> '.$col.'</label><input type="checkbox" onclick="checkAllElements(\'data-group\',\''.$fld.'_checkbox\', this.checked);"></th>'."\n";
		}
        elseif(isset($params['-nosort']) || isset($params[$fld."_nolink"])){
			$rtn .= '		<th'.$title.' nowrap>' . "{$col}</th>\n";
        	}
        elseif(isset($params['-sortlink'])){
			$href=$params['-sortlink'];
			$replace='%col%';
            $href=str_replace($replace,$col,$href);
			$rtn .= '		<th'.$title.' nowrap><a class="w_link w_white w_block" href="/'.$href.'">' . $col. "</a></th>\n";
        	}
        elseif(isset($params['-sortclick'])){
			$onclick=$params['-sortclick'];
			$replace='%col%';
            $onclick=str_replace($replace,$col,$onclick);
			$rtn .= '		<th'.$title.' nowrap><a class="w_link w_white w_block" href="#'.$col.'" onclick="/'.$onclick.'">' . $col. "</a></th>\n";
        	}
        else{
	        if(preg_match('/\.(php|htm|phtm)$/i',$PAGE['name'])){$href=$PAGE['name'].'?'.buildURL($arr);}
	        else{$href=$PAGE['name'].'/?'.buildURL($arr);}
			$rtn .= '		<th'.$title.' nowrap><a class="w_link w_white w_block" href="/'.$href.'">' . $col. "{$arrow}</a></th>\n";
			}
		}
	if(isset($params['-row_actions'])){
    	$rtn .= '		<th nowrap>Actions</th>'."\n";
	}
	$rtn .= "\t</tr></thead><tbody>\n";
	$row=0;
	$editlist=0;
	if(isset($params['-sumfields']) && !is_array($params['-sumfields'])){
		$params['-sumfields']=preg_split('/[\,\;]+/',$params['-sumfields']);
	}
	if(isset($params['-sumfields']) && is_array($params['-sumfields'])){
		$sums=array();
		foreach($params['-sumfields'] as $sumfield){$sums[$sumfield]=0;}
	}
	foreach($list as $rec){
		$row++;
		$cronalert=0;
		if(isset($params['-table']) && $params['-table']=='_cron'){
			if($rec['active']==1 && $rec['running']==0 && isNum($rec['frequency'])){
				$frequency=$rec['frequency']*60;
				if($age > $frequency){
					$cronalert=1;
            	}
			}
			elseif($rec['active']==0){
            	$cronalert=2;
			}
			elseif($rec['running']==1){
            	$cronalert=3;
			}
        }
        if($cronalert==1){
			//overdue to run
			$bgcolor=isFactor($row,2)?'#ffd2d2':'#ffa6a6';
			$rtn .= '	<tr valign="top" bgcolor="'.$bgcolor.'">'."\n";
        }
        elseif($cronalert==2){
			//Not Active - grey out
			$bgcolor=isFactor($row,2)?'#d2d2d2':'#e9e9e9';
			$rtn .= '	<tr valign="top" bgcolor="'.$bgcolor.'">'."\n";
        }
        elseif($cronalert==3){
			//Currently running
			$bgcolor=isFactor($row,2)?'#ffffc1':'#ffff9f';
			$rtn .= '	<tr valign="top" bgcolor="'.$bgcolor.'">'."\n";
        }
		elseif(isset($params['-altcolor']) && isFactor($row,2)){
			$rtn .= '	<tr valign="top" bgcolor="'.$params['-altcolor'].'">'."\n";
		}
		else{
			//check for row params
			$rowid='';
			if(isset($params['-rowid'])){
				$rowid=$params['-rowid'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowid=str_replace($replace,$rec[$xfld],$rowid);
                }
                $rowid=evalPHP($rowid);
                $rowid=' id="'.$rowid.'"';
			}
			$rowclass='';
			if(isset($params['-rowclass'])){
				$rowclass=$params['-rowclass'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowclass=str_replace($replace,$rec[$xfld],$rowclass);
                }
                $rowclass=evalPHP($rowclass);
                $rowclass=' class="'.$rowclass.'"';
			}
			if(isset($params['-sync']) && $params['-sync']==1 && $rec['user_stage']==$USER['username']){
				$bgcolor=isFactor($row,2)?'#fefdc5':'#fefc9c';
        		$params['-rowstyle']="background-color:{$bgcolor};";
			}
			$rowstyle='';
			if(isset($params['-rowstyle'])){
				$rowstyle=$params['-rowstyle'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowstyle=str_replace($replace,$rec[$xfld],$rowstyle);
                }
                $rowstyle=evalPHP($rowstyle);
            	$rowstyle=' style="'.$rowstyle.'"';
			}
			$rtn .= '	<tr valign="top"'.$rowid.$rowclass.$rowstyle.'>'."\n";
		}
		if(isset($params['-table']) && $params['-table']=='_users' && $params['-icons']){
			//echo "rec:".printValue($rec);
			$uinfo=getUserInfo($rec);
			$rtn .= '		<td><img src="'.$uinfo['icon'].'" title="user:'.$uinfo['username'].', status:'.$uinfo['status'].'" border="0"></td>'."\n";
    		}
    	if(isset($params['-sumfields']) && is_array($params['-sumfields'])){
			foreach($params['-sumfields'] as $sumfield){
				$amt=(float)str_replace(',','',$rec[$sumfield]);
				$sums[$sumfield]+=$amt;
			}
		}
    	$tabindex=0;
		foreach($listfields as $fld){
			//Show editlist?
			if($listform==1 && isset($info[$fld]['editlist']) && $info[$fld]['editlist']==1){
				$rtn .= '<td>'."\n";
				$fldopts=array('-table'=>$params['-table'],'-field'=>$fld,'name'=>"{$fld}_{$rec[$idfield]}",'value'=>$rec[$fld]);
				foreach($params as $pkey=>$pval){
					if(preg_match('/^'.$fld.'_(.+)$/',$pkey,$m)){
						if($m[1]=='tabindex'){$pval += $tabindex;$tabindex++;}
						$fldopts[$m[1]]=$pval;
					}
                }
				$rtn .= '<div style="display:none"><textarea name="'."{$fld}_{$rec[$idfield]}_prev".'">'.$rec[$fld].'</textarea></div>'."\n";
				$rtn .= getDBFieldTag($fldopts);
				$rtn .= '</td>'."\n";
				$editlist++;
				continue;
            }
            $val=isset($rec[$fld])?$rec[$fld]:null;
			if($fld=='password'){
				$val=preg_replace('/./','*',$val);
            }
            //relate?
			if(isset($params[$fld."_relate"])){
				$relflds=preg_split('/[\,\ \;\:]+/',$params[$fld."_relate"]);
				$rvals=array();
				foreach($relflds as $relfld){
					if(isset($rec["{$fld}_ex"][$relfld])){$rvals[]=$rec["{$fld}_ex"][$relfld];}
					}
                $val=count($rvals)?implode(' ',$rvals):$val;
				}
			//eval?
			if(isset($params[$fld."_eval"])){
				$evalstr=$params[$fld."_eval"];
				foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $evalstr=str_replace($replace,$rec[$xfld],$evalstr);
                	}
                $val=evalPHP('<?' . $evalstr .'?>');
				}
			//checkmark?
			if(isset($params[$fld."_checkmark"])){
                $img=$val==1?'checkmark.png':'x.png';
				$val='<img src="/wfiles/iconsets/16/'.$img.'" border="0" class="w_bottom">';
				}
			//link, check, or image?
			$target=isset($params[$fld."_target"])?' target="'.$params[$fld."_target"].'"':'';
			if(isset($params[$fld."_link"]) && $params[$fld."_link"]==1){
				$val='<a class="w_link w_block" href="'.$val.'"'.$target.'>'.$val.'</a>';
				}
			elseif(isset($params['-href'])){
                $href=$params['-href'];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link w_block" href="'.$href.'">'.$val.'</a>';
				}
			elseif(isset($params[$fld."_href"])){
                $href=$params[$fld."_href"];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link w_block" href="'.$href.'"'.$target.'>'.$val.'</a>';
				}
			elseif(isset($params[$fld."_onclick"])){
                $href=$params[$fld."_onclick"];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
					//echo "replacing {$replace} with {$rec[$xfld]}<br>\n";
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link w_block" title="CLICK" href="#" onClick="'.$href.'">'.$val.'</a>';
				}
			elseif(isset($params[$fld."_checkbox"]) && $params[$fld."_checkbox"]==1){
				$cval=$val;
				$val='<input type="checkbox" data-group="'.$fld.'_checkbox" id="'.$fld.'_checkbox_'.$row.'" name="'.$fld.'[]" value="'.$val.'">';
				if(!isNum($cval)){$val .= '<label for="'.$fld.'_checkbox_'.$row.'">'.$cval.'</label>';}
            	}
			elseif(isset($params[$fld."_check"]) && $params[$fld."_check"]==1){
				if($val==0){$val='';}
				elseif($val==1){$val='<center><img src="/wfiles/check.gif" border="0"></center>';}
            	}
            elseif(isset($params[$fld."_image"]) && $params[$fld."_image"]==1){
				$val='<center><img title="'.$val.'" src="'.$val.'" border="0"></center>';
            	}
            elseif(isset($params[$fld."_email"]) && $params[$fld."_email"]==1){
				$val='<a class="w_link" href="mailto:'.$val.'">'.$val.'</a>';
            	}
            elseif(isset($params[$fld."_dateformat"]) && strlen(trim($val)) && strlen($params[$fld."_dateformat"])){
				$val=date($params[$fld."_dateformat"],strtotime($val));
            	}
            elseif(isset($params[$fld."_dateage"]) && $params[$fld."_dateage"]==1){
				if(isNum($rec["{$fld}_utime"])){
					$age=time()-$rec["{$fld}_utime"];
					$val=verboseTime($age);
					}
				else{$val='';}
            	}
            if(isNum($val)){$params[$fld."_align"]="right";}
			//write the cell with any custom attributes
			$rtn .= "\t\t<td";

			if(!isset($params[$fld."_align"])){
				if(preg_match('/^('.$idfield.'|recid)$/i',$fld)){$params[$fld."_align"]="right";}
				elseif(preg_match('/^(tablename|code|fieldname)$/i',$fld)){$params[$fld."_align"]="center";}
				}
			if(isset($params[$fld."_align"])){$rtn .= ' align="'.$params[$fld."_align"].'"';}
			if(isset($params[$fld."_valign"])){$rtn .= ' valign="'.$params[$fld."_valign"].'"';}
			if(isset($params[$fld."_bgcolor"])){$rtn .= ' bgcolor="'.$params[$fld."_bgcolor"].'"';}
			if(isset($params[$fld."_class"])){$rtn .= ' class="'.$params[$fld."_class"].'"';}
			elseif(isset($params[$fld."_style"])){$rtn .= ' style="'.$params[$fld."_style"].'"';}
			if(isset($params[$fld."_nowrap"]) && $params[$fld."_nowrap"]==1){$rtn .= ' nowrap';}
			$rtn .= ">" . $val . "</td>\n";
			}
		if(isset($params['-row_actions']) && is_array($params['-row_actions']) && count($params['-row_actions'])){
			$rtn .= '	<td align="right">'."\n";
			foreach($params['-row_actions'] as $action){
            	if(!is_array($action)){$action=array($action);}
            	$action_value=array_shift($action);
            	$show=1;
            	//criteria?
                while(count($action)){
					$key=array_shift($action);
					$val=array_shift($action);
					if(isset($rec[$key])){
                    	if($rec[$key]!=$val){$show=0;}
					}
				}
				if($show==1){
					foreach($list[0] as $xfld=>$xval){
						if(is_array($xfld) || is_array($xval)){continue;}
						$replace='%'.$xfld.'%';
                    	$action_value=str_replace($replace,$rec[$xfld],$action_value);
                	}
                	$rtn .='		'.$action_value." \n";
				}
			}
			$rtn .= '	</td>'."\n";
		}
		$rtn .= "\t</tr>\n";
    	}
    $rtn .= '</tbody>'."\n";
    if(isset($params['-sumfields']) && is_array($params['-sumfields'])){
		$rtn .= '	<tfoot><tr>'."\n";
		foreach($fields as $fld){
        	if(isset($sums[$fld])){$val=$sums[$fld];}
        	else{$val='';}
        	$rtn .= '		<th align="right">'.$val.'</th>'."\n";
		}
		$rtn .= '	</tr></tfoot>'."\n";
	}
    $rtn .= "</table>\n";
    if($listform==1){
		if($editlist > 0){$rtn .= buildFormSubmit("Update");}
		$rtn .= $customcode;
    	$rtn .= buildFormEnd();
		}
	elseif(isset($params['-form']) && is_array($params['-form'])){
    	$rtn .= $customcode;
    	$rtn .= buildFormEnd();
	}
	else{$rtn .= $customcode;}
	if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '</div>'."\n";}
	elseif(isset($params['-ajaxid'])){
		$rtn .= '</div>'."\n";
	}
	return $rtn;
	}

//---------- begin function listDBResults
/**
* @describe wrapper function for listDBRecords. the results of listDBRecords based on query passed in
* @param query string - SQL query
* @param params array - requires either -list or -table or -query
*	[-list] array - getDBRecords array to use
*	[-table] string - table name
*	[-query] string- SQL query string
*	[-hidesearch] -  hide the search form
*	[-limit] mixed - query record limit
*	other field/value pairs filter the query results
* @param [customcode] string - html code to append to end - defaults to blank
* @return string - html table to display
* @usage 
*	<?=listDBResults('select title,note_date from notes');?>
*/
function listDBResults($query='',$params=array()){
	$params['-query']=$query;
	return listDBRecords($params);
	}
//---------- begin function getDBTables --------------------
/**
* @describe returns table names.
* @param [dbname] string - database name - defaults to current database
* @param [force] boolean - if true forces the cache to be cleared - defaults to false
* @return array
* @usage $tables=getDBTables();
*/
function getDBTables($dbname='',$force=0){
	return databaseTables($dbname,$force);
	}
//---------- begin function isDBReservedWord ----------
/**
* @describe returns true if word is a reserved database word.. ie - dont use it
* @param word string
* @return boolean
*	returns true if word is a reserved database word.. ie - dont use it
* @usage if(isDBReservedWord('select')){...}
*/
function isDBReservedWord($word=''){
	$word=trim($word);
	//return 1 if word starts with a number since those fields are not allowed in xml
	if(preg_match('/^[0-9]/',$word)){return true;}
	$reserved=array(
		'action','add','all','allfields','alter','and','as','asc','auto_increment','between','bigint','bit','binary','blob','both','by',
		'cascade','char','character','change','check','column','columns','create',
		'data','database','databases','date','datetime','day','day_hour','day_minute','day_second','dayofweek','dec','decimal','default','delete','desc','describe','distinct','double','drop','escaped','enclosed',
		'enum','explain','fields','float','float4','float8','foreign','from','for','full',
		'grant','group','having','hour','hour_minute','hour_second',
		'ignore','in','index','infile','insert','int','integer','interval','int1','int2','int3','int4','int8','into','is','inshift','in1',
		'join','key','keys','leading','left','like','lines','limit','lock','load','long','longblob','longtext',
		'match','mediumblob','mediumtext','mediumint','middleint','minute','minute_second','mod','month',
		'natural','numeric','no','not','null','on','option','optionally','or','order','outer','outfile',
		'partial','precision','primary','procedure','privileges',
		'read','real','references','rename','regexp','repeat','replace','restrict','rlike',
		'select','set','show','smallint','sql_big_tables','sql_big_selects','sql_select_limit','sql_log_off','straight_join','starting',
		'table','tables','terminated','text','time','timestamp','tinyblob','tinytext','tinyint','trailing','to',
		'use','using','unique','unlock','unsigned','update','usage',
		'values','varchar','varying','varbinary','with','write','where',
		'year','year_month','zerofill'
		);
	if(in_array($word,$reserved)){return true;}
	return false;
	}
//---------- begin function isDBStage ----------
/**
* @describe returns true if you are in the staging database
* @return boolean
*	returns true if you are in the staging database
* @usage if(isDBStage()){...}
*/
function isDBStage(){
	global $databaseCache;
	if(isset($databaseCache['isDBStage'])){return $databaseCache['isDBStage'];}
	global $CONFIG;
	if(isset($CONFIG['stage'])){
    	$rtn=$CONFIG['stage'];
	}
	else{
		$xset=settingsValues(0);
		$rtn=0;
		if(isset($xset['wasql_synchronize_master']) && $xset['wasql_synchronize_master']==$CONFIG['dbname']){$rtn = 1;}
	}
	$databaseCache['isDBStage']=$rtn;
	return $rtn;
}
//---------- begin function isDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
*	returns true if table already exists
* @usage if(isDBTable('_users')){...}
*/
function isDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if(isset($databaseCache['isDBTable'][$table])){return $databaseCache['isDBTable'][$table];}
	$databaseCache['isDBTable']=array();
	$table_parts=preg_split('/\./', $table);
	$dbname='';
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		//$table=implode('.',$table_parts);
		}
	$tables=getDBTables($dbname,$force);
	$databaseCache['isDBTable'][$table]=false;
	foreach($tables as $ctable){
		$ctable=strtolower($ctable);
		if(strlen($dbname)){$ctable="{$dbname}.{$ctable}";}
		$databaseCache['isDBTable'][$ctable]=true;
		}
	return $databaseCache['isDBTable'][$table];
	}
//---------- begin function truncateDBTable ----------
/**
* @describe removes all records in specified table and resets the auto-incriment field to zero
* @param table string
* @return mixed - return true on success or errmsg on failure
* @usage $ok=truncateDBTable('comments');
*/
function truncateDBTable($table){
	if(is_array($table)){$tables=$table;}
	else{$tables=array($table);}
	foreach($tables as $table){
		if(!isDBTable($table)){return "No such table: {$table}.";}
		$result=executeSQL("truncate {$table}");
		if(isset($result['error'])){
			return $result['error'];
	        }
	    }
    return 1;
	}

/*  ############################################################################
	Database Independant function calls 
	- currently supports MySQL, PostgreSQL, and MS SQL
	############################################################################
*/
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseAffectedRows($resource=''){
	global $dbh;
	//Open a connection to a dabase Server - supports multiple database types
	if(isMysqli()){return mysqli_affected_rows($dbh);}
	elseif(isPostgreSQL()){return pg_affected_rows($resource);}
	elseif(isMysql()){return mysql_affected_rows();}
	elseif(isPostgreSQL()){return mysql_affected_rows($resource);}
	elseif(isMssql()){
		$val = null;
		$res = databaseQuery('SELECT @@rowcount as rows');
		if ($row = mssql_fetch_row($res)) {
			$val = trim($row[0]);
			}
		mssql_free_result($res);
		return $val;
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseConnect($host,$user,$pass,$dbname=''){
	//Open a connection to a dabase Server - supports multiple database types
	if(isMysqli()){return mysqli_connect($host, $user, $pass, $dbname);}
	elseif(isMysql()){return mysql_connect($host, $user, $pass);}
	elseif(isMssql()){return mssql_connect($host, $user, $pass);}
	elseif(isPostgreSQL()){
		//Open a persistent PostgreSQL connection
		$conn_string="host={$host} dbname={$dbname} user={$user} password={$pass}";
		return pg_pconnect($conn_string);
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDataType($str){
	//integer, real(8,2), int(8)
	//PostgreSQL does not have a the same data types as mysql
	//http://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
	$parts=preg_split('/[,()]/',$str);
	$name=strtolower($parts[0]);
	if(isPostgreSQL()){
		switch(strtolower($name)){
			case 'tinyint':return 'int2';break;
			case 'smallint':return 'int4';break;
        	case 'bigint':return 'int8';break;
        	case 'real':return 'float4';break;
        	case 'datetime':return 'timestamp';break;
        	case 'numeric':
        		if(count($parts)==3){return "decimal({$parts[1]},{$parts[2]})";}
        		elseif(count($parts)==2){return "decimal({$parts[1]})";}
        		else{return 'decimal';}
        		break;
        	case 'tinytext':
        	case 'mediumtext':
        	case 'longtext':
				return 'text';
				break;
		}
	}
	return $str;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDateTimeNow(){
	$version=databaseVersion();
	if(isMysqli() || isMysql()){
		if(stringBeginsWith($version,'5.6')){
			return " NOT NULL Default NOW()";
		}
	}
	elseif(isPostgreSQL()){
		return " NOT NULL Default CURRENT_DATE";
	}
	elseif(isMssql()){
		return " NOT NULL Default GETDATE()";
	}
	return " NOT NULL";
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDescribeTable($table){
	global $dbh;
	global $databaseCache;
	$cachekey=strtolower($table);
	if(isset($databaseCache['databaseDescribeTable'][$cachekey])){
		return $databaseCache['databaseDescribeTable'][$cachekey];
		}
	$recs=array();
	if(isMysqli() || isMysql()){
		$recs=getDBRecords(array('-query'=>"desc {$table}"));
		}
	elseif(isPostgreSQL()){
		//field,type,null,key,default,extra
    	$query="
			SELECT 
				a.attname as field, 
				format_type(a.atttypid, a.atttypmod) as type, 
				d.adsrc as extra,
		    	a.attnotnull as null
			FROM 
				pg_attribute a LEFT JOIN pg_attrdef d
			ON 
				A.attrelid = d.adrelid AND a.attnum = d.adnum
			WHERE
				a.attrelid = '{$table}'::regclass AND a.attnum > 0 AND NOT a.attisdropped 
			ORDER BY a.attnum
		";
		$recs=getDBRecords(array('-query'=>$query));
	}
	elseif(isMssql()){
		$trecs=getDBRecords(array('-query'=>"exec sp_columns [{$table}]"));
		//echo printValue($trecs);
		foreach($trecs as $trec){
			$rec=array(
				'field'		=> $trec['column_name'],
				'type'		=> preg_match('/char$/i',$trec['type_name'])?"{$trec['type_name']}({$trec['precision']})":"{$trec['type_name']}",
				'null'		=> $trec['is_nullable']
				);
			$recs[]=$rec;
        	}
    	}
    $databaseCache['databaseDescribeTable'][$cachekey]=$recs;
	return $recs;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseError(){
	//Returns the text of the error message from previous MySQL operation - supports multiple database types
	global $dbh;
	if(isMysqli()){
		if(!$dbh){return 'connection failure';}
		return mysqli_error($dbh);
		}
	elseif(isMysql()){return mysql_error();}
	elseif(isPostgreSQL()){return @pg_last_error();}
	elseif(isMssql()){
		$err=mysql_error();
		$info=@mysql_info();
		if(strlen($info)){
 			$err .= "<br>\n". mysql_info();
			}
		return $err;
    	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseEscapeString($str){
	global $dbh;
	if(isMysqli()){
		$str = function_exists('mysqli_real_escape_string')?mysqli_real_escape_string($dbh,$str):mysqli_escape_string($dbh,$str);
	}
	elseif(isMysql()){
		$str = function_exists('mysql_real_escape_string')?mysql_real_escape_string($str):mysql_escape_string($str);
	}
	elseif(isPostgreSQL()){return pg_escape_string($str);}
	else{
		//MS SQL does not have a specific escape string function
		$str = str_replace("'","''",$str);
	}
	return $str;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchAssoc($query_result){
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysqli()){return mysqli_fetch_assoc($query_result);}
	elseif(isMysql()){return mysql_fetch_assoc($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_assoc($query_result);}
	elseif(isMssql()){return mssql_fetch_assoc($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchObject($query_result){
	//Fetch row as object - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_fetch_object($dbh,$query_result);}
	elseif(isMysql()){return mysql_fetch_object($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_object($query_result);}
	elseif(isMssql()){return mssql_fetch_object($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchRow($query_result){
	//Get a result row as an enumerated array - supports multiple database types
	if(isMysqli()){return mysqli_fetch_row($query_result);}
	elseif(isMysql()){return mysql_fetch_row($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_row($query_result);}
	elseif(isMssql()){return mssql_fetch_row($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldFlags($query_result,$i=-1){
	//Returns the length of the specified field - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_flags($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_flags($query_result,$i);}
	elseif(isPostgreSQL()){}
	elseif(isMssql()){
		return null;
		return mssql_field_flags($query_result,$i);
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldLength($query_result,$i=-1){
	//Returns the length of the specified field - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_len($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_len($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_prtlen($query_result,$i);}
	elseif(isMssql()){return mssql_field_length($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldName($query_result,$i=-1){
	//Get the name of the specified field in a result - supports multiple database types
	global $dbh;
	//mysqli does not have a mysqli_field_name function
	if(isMysqli()){return abort("mysqli_field_name does not exist!");}
	elseif(isMysql()){return mysql_field_name($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_name($query_result,$i);}
	elseif(isMssql()){return mssql_field_name($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldType($query_result,$i=-1){
	//Get the type of the specified field in a result - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_type($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_type($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_type($query_result,$i);}
	elseif(isMssql()){return mssql_field_type($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFreeResult($query_result){
	//Free result memory - supports multiple database types
	global $dbh;
	if(!is_resource($query_result)){return;}
	if(isMysqli()){return mysqli_free_result($dbh,$query_result);}
	elseif(isMysql()){return mysql_free_result($query_result);}
	elseif(isPostgreSQL()){return pg_free_result($query_result);}
	elseif(isMssql()){return mssql_free_result($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseIndexes($table){
	//Get the ID generated in the last query - supports multiple database types
	if(isMysqli() || isMysql()){
		return getDBRecords(array('-query'=>"show index from {$table}"));
    	}
    elseif(isPostgreSQL()){
    	$query="
		SELECT n.nspname as schema,
 		c.relname as name,
		CASE c.relkind 
			WHEN 'r' THEN 'table' 
			WHEN 'v' THEN 'view' 
			WHEN 'i' THEN 'index' 
			WHEN 'S' THEN 'sequence' 
			WHEN 's' THEN 'special'
			END as type,
	 	u.usename as owner,
		c2.relname as table
		FROM pg_catalog.pg_class c
	    	JOIN pg_catalog.pg_index i ON i.indexrelid = c.oid
	    	JOIN pg_catalog.pg_class c2 ON i.indrelid = c2.oid
	    	LEFT JOIN pg_catalog.pg_user u ON u.usesysid = c.relowner
	    	LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
		WHERE c.relkind IN ('i','')
	     	AND n.nspname NOT IN ('pg_catalog', 'pg_toast')
	     	AND pg_catalog.pg_table_is_visible(c.oid)
	     	and c2.relname='{$table}'
		ORDER BY 1,2
		";
	return getDBRecords(array('-query'=>$query));
	}
	elseif(isSqlite()){return array();}
	elseif(isMssql()){
		$query='SELECT sys.tables.object_id, sys.tables.name as table_name, sys.columns.name as column_name, sys.indexes.name as index_name,
sys.indexes.is_unique, sys.indexes.is_primary_key
FROM sys.tables, sys.indexes, sys.index_columns, sys.columns
WHERE (sys.tables.object_id = sys.indexes.object_id AND sys.tables.object_id = sys.index_columns.object_id AND sys.tables.object_id = sys.columns.object_id
AND sys.indexes.index_id = sys.index_columns.index_id AND sys.index_columns.column_id = sys.columns.column_id)
AND sys.tables.name = '."'{$table}'";
		return getDBRecords(array('-query'=>$query));
    	}
    return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseInsertId($query_result=''){
	//Get the ID generated in the last query - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_insert_id($dbh);}
	elseif(isMysql()){return mysql_insert_id();}
	elseif(isSqlite()){return sqlite_last_insert_rowid();}
	elseif(isPostgreSQL()){return pg_last_oid($query_result);}
	elseif(isMssql()){
		//MSSQL does not have an insert_id function like mysql does
		$id = null;
		$res = databaseQuery('SELECT @@identity AS id');
		if ($row = mssql_fetch_row($res)) {
			$id = trim($row[0]);
			}
		mssql_free_result($res);
		return $id;
    	}
    return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseListDbs(){
	global $dbh;
	$dbs=array();
	if(isMysqli()){
		//mysqli does not have a mysqli_list_dbs function
		$query="show databases";
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $rec){
			if(preg_match('/^(mysql|performance_schema|information_schema)$/',$rec['database'])){continue;}
			$dbs[]=$rec['database'];
			}
	}
	elseif(isMysql()){
		$db_list=mysql_list_dbs($dbh);
		while ($row = databaseFetchObject($db_list)) {
			$name=(string)$row->Database;
			if(preg_match('/^(mysql|performance_schema|information_schema)$/',$name)){continue;}
			$dbs[]=$name;
		}
	}
	elseif(isMssql()){
		$db_list=databaseQuery("exec sp_databases");
		while ($row = databaseFetchObject($db_list)) {
			$name=(string)$row->DATABASE_NAME;
			if(preg_match('/^(master|model|msdb|tempdb)$/',$name)){continue;}
			$dbs[]=$name;
		}
	}
	elseif(isPostgreSQL()){
    	$query="SELECT datname as name FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE AND datname != 'postgres'";
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $rec){$dbs[]=$rec['name'];}
	}
	sort($dbs);
	return $dbs;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseListProcesses(){
	global $dbh;
	if(isMysqli()){return mysqli_list_processes($dbh);}
	elseif(isMysql()){return mysql_list_processes();}
	elseif(isPostgreSQL()){
		$query="select * from pg_stat_activity";
		return getDBRecords(array('-query'=>$query));
	}
	elseif(isMssql()){
		//not sure how for MS SQL
		return null;
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseNumFields($query_result){
	//Free result memory - supports multiple database types
	if(isMysqli()){return mysqli_num_fields($query_result);}
	elseif(isMysql()){return mysql_num_fields($query_result);}
	elseif(isPostgreSQL()){return pg_num_fields($query_result);}
	elseif(isMssql()){return mssql_num_fields($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseNumRows($query_result){
	//Free result memory - supports multiple database types
	if(isMysqli()){return mysqli_num_rows($query_result);}
	elseif(isMysql()){return mysql_num_rows($query_result);}
	elseif(isPostgreSQL()){return pg_num_rows($query_result);}
	elseif(isMssql()){return mssql_num_rows($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databasePrimaryKeyFieldString(){
	if(isMysqli() || isMysql()){return "integer NOT NULL Primary Key auto_increment";}
	elseif(isPostgreSQL()){return "serial PRIMARY KEY";}
	elseif(isMssql()){return "INT NOT NULL IDENTITY(1,1)";}
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseQuery($query){
	//Free result memory - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_query($dbh,$query);}
	elseif(isMysql()){return mysql_query($query);}
	elseif(isPostgreSQL()){return pg_query($dbh,$query);}
	elseif(isMssql()){return mssql_query($query);}
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseRestoreDb(){
	global $CONFIG;
	return databaseSelectDb($CONFIG['dbname']);
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseSelectDb($dbname){
	global $CONFIG;
	global $dbh;
	if(!strlen($dbname)){return false;}
	if(isset($CONFIG['_current_dbname_']) && $dbname === $CONFIG['_current_dbname_']){return true;}
	global $dbh;
	//Free result memory - supports multiple database types
	if(isMysqli()){$rtn = mysqli_select_db($dbh,$dbname);}
	elseif(isMysql()){$rtn = mysql_select_db($dbname);}
	elseif(isPostgreSQL()){$rtn=$dbh?true:false;}
	elseif(isMssql()){$rtn = mssql_select_db($dbname);}
	if($rtn){$CONFIG['_current_dbname_']=$dbname;}
	return $rtn;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseTables($dbname='',$force=0){
	global $databaseCache;
	global $CONFIG;
	$dbcachekey=strlen($dbname)?strtolower($dbname):$CONFIG['dbname'];
	if(!$force && isset($databaseCache['databaseTables'][$dbcachekey])){
		return $databaseCache['databaseTables'][$dbcachekey];
		}
	//returns array of user tables - supports multiple database types
	$tables=array();
	//set query string
	if(isMysqli() || isMysql()){
		$query = "SHOW TABLES";
		if(strlen($dbname)){$query .= " from {$dbname}";}
		}
	elseif(isPostgreSQL()){
    	$query="SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
	}
	elseif(isMssql()){
		global $CONFIG;
		$query = "select name from sysobjects where xtype = 'U';";
		}
	else{return null;}
	//run query
	$query_result=@databaseQuery($query);
  	if(!$query_result){return $query . databaseError();}
  	//build tables array
	while ($row = databaseFetchRow($query_result)) {
    	array_push($tables,$row[0]);
		}
	databaseFreeResult($query_result);
	//return sorted tables array
	sort($tables);
	$databaseCache['databaseTables'][$dbcachekey]=$tables;
	return $tables;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseVersion(){
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysqli() || isMysql() || isPostgreSQL()){
		$recs=getDBRecords(array('-query'=>"select version() as version"));
		if(isset($recs[0]['version'])){return $recs[0]['version'];}
		return printValue($recs);
    	}
	elseif(isMssql()){
		$recs=getDBRecords(array('-query'=>"select @@version as version"));
		if(isset($recs[0]['version'])){return $recs[0]['version'];}
		return printValue($recs);
    	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseType(){
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysql()){return 'Mysql';}
	elseif(isMysqli()){return 'Mysqli';}
	elseif(isPostgreSQL()){return 'PostgreSQL';}
	elseif(isMssql()){return 'MS SQL';}
	elseif(isSqlite()){return 'SQLite';}
	return 'Unknown';
	}

//---------- begin function isMysql ----------
/**
* @describe returns true if database driver is MySQL
* @return boolean
* @usage if(isMysql()){...}
*/
function isMysql(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mysql'){return true;}
	return false;
	}
//---------- begin function isMysqli ----------
/**
* @describe returns true if database driver is MySQLi
* @return boolean
* @usage if(isMysqli()){...}
*/
function isMysqli(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mysqli'){return true;}
	return false;
	}
//---------- begin function isPostgreSQL ----------
/**
* @describe returns true if database driver is PostgreSQL
* @return boolean
* @usage if(isPostgreSQL()){...}
*/
function isPostgreSQL(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='postgres'){return true;}
	elseif($dbtype=='postgresql'){return true;}
	return false;
	}
//---------- begin function isSqlite ----------
/**
* @describe returns true if database driver is Sqlite
* @return boolean
* @usage if(isSqlite()){...}
*/
function isSqlite(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='sqlite'){return true;}
	return false;
	}
//---------- begin function isMssql ----------
/**
* @describe returns true if database driver is MS SQL
* @return boolean
* @usage if(isMssql()){...}
*/
function isMssql(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mssql'){return true;}
	return false;
	}
//---------- begin function isODBC ----------
/**
* @describe returns true if database driver is ODBC
* @return boolean
* @usage if(isODBC()){...}
*/
function isODBC(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='odbc'){return true;}
	return false;
	}
/**
* setDBSettings - sets a value in the settings table
* @param key_name string
* @param key_value string
* @param user_id int - optional. defaults to current USER id or 0
* @return
* 	<p>@usage setDBSettings($key_name,$key_value,$userid);</p>
*/
function setDBSettings($name,$value,$userid){
	global $USER;
	if(!isNum($userid)){
		$userid=setValue(array($USER['_id'],0));
		}
	$rec=getDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid));
	if(is_array($rec)){
		return editDBRecord(array('-table'=>'_settings','-where'=>"_id={$rec['_id']}",'key_value'=>$value));
    }
    else{
		return addDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid,'key_value'=>$value));
	}
}
/**
* getDBSettings - retrieves a value in the settings table
* @param key_name string
* @param user_id int - optional. defaults to current USER id or 0
* @param collapse boolean - optional. defaults to false. If true, collapses XML array results
* @return  if key_value is xml it returns an array, otherwise a string
* 	<p>@usage $val=getDBSettings($key_name,$userid);</p>
*/
function getDBSettings($name,$userid,$collapse=0){
	global $USER;
	if(!isNum($userid)){
		$userid=setValue(array($USER['_id'],0));
		}
	$settings=null;
	$rec=getDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid));
	if(is_array($rec)){
		if(isXML($rec['key_value'])){
			$settings = xml2Array($rec['key_value']);
			if($collapse && is_array($settings['request'])){
				$settings=$settings['request'];
				if(is_array($settings)){
                	foreach($settings as $key=>$val){
                    	if(isset($settings[$key]['values']) && is_array($settings[$key]['values'])){
                        	$settings[$key]=$settings[$key]['values'];
						}
					}
				}
			}
		}
		else{$settings=$rec['key_value'];}
    }
    return $settings;
}
//---------- begin function showDBCronPanel ----
/**
* @describe shows the cron panel and updates automatically
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function showDBCronPanel($ajax=0,$frequency=60){
	$rtn='';
	if($ajax==0){
		$rtn .= '<div class="w_pad w_round w_smaller w_right w_border w_tip" style="width:170px;z-index:999;position:absolute;top:5px;right:10px;">'."\n";
		$rtn .= '	<div class="w_right"><img src="/wfiles/iconsets/16/close.png" border="0" style="cursor:pointer;" onclick="removeId(\'cronpanel\');"></div>'."\n";
		$rtn .= '	<div class="w_bold"><img src="/wfiles/_cron.png" width="16" height="16" border="0" style="vertical-align:middle;"> Cron Information Panel</div>'."\n";
		$rtn .= '	<div id="cronpanel">'."\n";
		}
	//show date updated
	$rtn .= '			<table cellspacing="0" cellpadding="0" border="0"><tr><td><div style="color:#CCC;font-size:10pt;" align="center">'.date("F j, Y, g:i a").'</div></td><td style="padding-left:5px;"><div style="color:#CCC;font-size:10pt;padding:1px 2px 1px 2px;border:1px solid #CCC;" id="crontimer" data-behavior="countdown">'.$frequency.'</div></td></tr></table>'."\n";
	$rtn .= '			<hr size="1">'."\n";
	$recs=getDBRecords(array('-table'=>"_cron"));
	//collect some stats
	$stats=array('cron_pid'=>array(),'active'=>0,'running'=>array());
	foreach($recs as $rec){
		if($rec['active']==1){$stats['active']+=1;}
		if($rec['running']==1){$stats['running'][]=$rec;}
		$stats['cron_pid'][$rec['cron_pid']]+=1;
		if(!isset($stats['lastrun']) || $rec['run_date_utime'] > $stats['lastrun']['run_date_utime']){$stats['lastrun']=$rec;}
		}
	//how many crons are running?
	if(!count($stats['cron_pid'])){
		$rtn .= '		<div><img src="/wfiles/iconsets/16/warning.png" border="0" style="vertical-align:middle"><b class="w_red">WARNING!</b> NO cron servers are listening. At least one cron server must be running in order for cron jobs to work.</div>'."\n";
		}
	else{
		$rtn .= '		<div><img src="/wfiles/iconsets/16/checkmark.png" width="16" height="16" border="0" style="vertical-align:bottom;"> '.count($stats['cron_pid']).' Cron servers listening</div>'."\n";
		}
	//last run
	if(is_array($stats['lastrun'])){
		$elapsed=time()-$stats['lastrun']['run_date_utime'];
		$rtn .= '		<div class="w_bold w_pad">"'.$stats['lastrun']['name'].'" ran '.verboseTime($elapsed).' ago</div>'."\n";
		}
	//running crons list
	if(count($stats['running'])){
		$rtn .= '		<div class="w_bold w_pad">'.count($recs).' Cron Jobs Running</div>'."\n";
		foreach($stats['running'] as $rec){
			$rtn .= '		<div style="margin-left:15px;">'."\n";
			$rtn .= "			{$rec['_id']}.  {$rec['name']}";
			$rtn .= '		</div>'."\n";
            }
		}
    else{$rtn .= '		<div class="w_bold w_pad">NO Cron Jobs Running</div>'."\n";}
	$frequency=$frequency*1000;
	$sort=encodeURL($_REQUEST['_sort']);
	$rtn .= '			' . buildOnLoad("initBehaviors();scheduleAjaxGet('cronpanel','php/index.php','cronpanel','_action=cronpanel&_sort={$sort}&freq={$frequency}',{$frequency},1);");
	if($ajax==0){
		$rtn .= '	</div>'."\n";
		$rtn .= '</div>'."\n";
		}
	return $rtn;
	}
?>