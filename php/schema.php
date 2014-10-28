<?php
/*
      WaSQL Default Schema

*/
//---------- begin function createWasqlTables
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function createWasqlTables($wtables=array()){
	//clear the cache
	if(!is_array($wtables)){$wtables=array($wtables);}
	clearDBCache('databaseTables');
	if(!count($wtables)){$wtables=getWasqlTables();}
	$ctables=getDBTables('',1);
	$rtn=array();
	foreach($wtables as $wtable){
		if(!in_array($wtable,$ctables)){
			clearDBCache(array('getDBFieldInfo','databaseTables','isDBTable'));
			$rtn[$wtable]=createWasqlTable($wtable);
			$cnt++;
			}
		else{
			$rtn[$wtable]="already exists";
        	}
    	}
    return $rtn;
	}
//---------- begin function createWasqlTable
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function createWasqlTable($table=''){
	global $CONFIG;
	//common fields to all wasql tables
	$fields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "INT NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "INT NULL",
		);
	switch(strtolower($table)){
		case '_access':
			$fields['http_host']="varchar(225) NULL";
			$fields['http_referer']="varchar(255) NULL";
			$fields['page']="varchar(255) NOT NULL";
			$fields['remote_addr']="varchar(20) NULL";
			$fields['remote_browser']="varchar(125) NULL";
			$fields['remote_browser_version']="varchar(125) NULL";
			$fields['remote_lang']="varchar(10) NULL";
			$fields['remote_os']="varchar(50) NULL";
			$fields['remote_device']="varchar(50) NULL";
			$fields['session_id']="varchar(50) NULL";
			$fields['guid']="char(40) NULL";
			$fields['xml']="text NULL";
			$fields['status']=databaseDataType('smallint')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=schemaAddFileData($table);
			addMetaData($table);
			return 1;
			break;
		case '_access_summary':
			$fields['accessdate']=databaseDataType('datetime')." NOT NULL";
			$fields['http_host']="varchar(225) NOT NULL";
			$fields['visits']="INT NOT NULL Default 1";
			$fields['visits_unique']="INT NOT NULL Default 1";
			$fields['http_referer_unique']="INT NOT NULL Default 1";
			$fields['page_unique']="INT NOT NULL Default 1";
			$fields['remote_addr_unique']="INT NOT NULL Default 1";
			$fields['remote_browser_unique']="INT NOT NULL Default 1";
			$fields['remote_browser_version_unique']="INT NOT NULL Default 1";
			$fields['remote_lang_unique']="INT NOT NULL Default 1";
			$fields['remote_os_unique']="INT NOT NULL Default 1";
			$fields['remote_device_unique']="INT NOT NULL Default 1";
			$fields['session_id_unique']="INT NOT NULL Default 1";
			$fields['guid_unique']="INT NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=schemaAddFileData($table);
			addMetaData($table);
			return 1;
			break;
		case '_reports':
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_auser']="integer NULL";
			$fields['name']="varchar(100) NOT NULL";
			$fields['menu']="varchar(50) NULL";
			$fields['icon']="varchar(50) NULL"; 
			$fields['query']="text NULL";
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['list_options']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"menu"));
			//echo $table.printValue($ok).printValue($fields);
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name menu active\r\nquery\r\nlist_options",
				'listfields'	=> "_cdate\r\n_cuser\r\n_edate\r\n_euser\r\n_adate\r\n_auser\r\nname\r\nmenu\r\nactive",
				'sortfields'	=> "_auser,_adate desc",
				'formfields_mod'=> "name menu active\r\nquery\r\nlist_options",
				'listfields_mod'=> "name\r\nmenu\r\nactive",
				'sortfields_mod'=> "name",
				'synchronize'	=> 1
				);
			$id=addDBRecord($addopts);
			$ok=schemaAddFileData($table);
			addMetaData($table);
			return 1;
			break;

		case '_cron':
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['begin_date']="date NULL";
			$fields['end_date']="date NULL";
			$fields['frequency']="integer NOT NULL Default 0";
			$fields['name']="varchar(150) NULL";
			$fields['cron_pid']="integer NOT NULL Default 0";
			$fields['run_cmd']="varchar(255) NOT NULL";
			$fields['run_date']=databaseDataType('datetime')." NULL";
			$fields['run_format']="varchar(255) NULL";
			$fields['run_length']="integer NULL";
			$fields['run_result']="text NULL";
			$fields['run_values']="varchar(255) NULL";
			$fields['logfile']="varchar(255) NULL";
			$fields['logfile_maxsize']="integer NULL";
			$fields['running']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['run_log']=databaseDataType('tinyint')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"run_cmd",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name active run_log begin_date end_date\r\nfrequency run_format run_values\r\nlogfile logfile_maxsize\r\nrun_cmd\r\nrunning run_date run_length\r\nrun_result",
				'listfields'	=> "name\r\ncron_pid\r\nactive\r\nrun_log\r\nrunning\r\nfrequency\r\nrun_format\r\nrun_values\r\nrun_cmd\r\nrun_date\r\nrun_length\r\nbegin_date\r\nend_date",
				'sortfields'	=> "active desc, running desc, begin_date desc",
				'formfields_mod'=> "name begin_date end_date\r\nfrequency run_format run_values\r\nrun_cmd\r\nrun_date run_length\r\nrun_result",
				'listfields_mod'=> "name\r\ncron_pid\r\nactive\r\nrunning\r\nfrequency\r\nrun_format\r\nrun_values\r\nrun_cmd\r\nrun_date\r\nrun_length\r\nbegin_date\r\nend_date",
				'sortfields_mod'=> "active desc, running desc, begin_date desc",
				'synchronize'	=> 1
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_cronlog':
			$fields['name']="varchar(150) NOT NULL";
			$fields['cron_id']="integer NOT NULL";
			$fields['cron_pid']="integer NOT NULL";
			$fields['run_cmd']="varchar(255) NOT NULL";
			$fields['run_date']=databaseDataType('datetime')." NOT NULL";
			$fields['run_result']="mediumtext NULL";
			$fields['run_length']="integer NOT NULL Default 0";
			$fields['count_crons']="integer NOT NULL Default 1";
			$fields['count_cronlogs']="integer NOT NULL Default 1";
			$fields['count_crons_active']="integer NOT NULL Default 1";
			$fields['count_crons_inactive']="integer NOT NULL Default 1";
			$fields['count_crons_running']="integer NOT NULL Default 1";
			$fields['count_crons_listening']="integer NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'listfields'	=> "name\r\ncron_pid\r\nrun_cmd\r\nrun_date\r\nrun_length\r\ncount_crons\r\ncount_crons_active\r\ncount_crons_inactive\r\ncount_crons_running\r\ncount_crons_listening",
				'sortfields'	=> "_cdate desc, name",
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_fielddata':
			$fields['behavior']="varchar(255) NULL";
			$fields['defaultval']="varchar(255) NULL";
			$fields['displayname']="varchar(255) NULL";
			$fields['description']="varchar(255) NULL";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['postedit']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['dvals']="text NULL";
			$fields['editlist']=databaseDataType('tinyint')." NULL";
			$fields['fieldname']="varchar(100) NOT NULL";
			$fields['height']="INT NULL";
			$fields['help']="varchar(255) NULL";
			$fields['inputmax']="INT NULL";
			$fields['inputtype']="varchar(25) NULL";
			$fields['mask']="varchar(255) NULL";
			$fields['onchange']="varchar(255) NULL";
			$fields['required']=databaseDataType('tinyint')." NULL";
			$fields['tablename']="varchar(100) NOT NULL";
			$fields['tvals']="text NULL";
			$fields['width']="INT NULL";
			$fields['related_table']="varchar(100) NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename,fieldname",'-unique'=>true));
			addMetaData($table);
			return 1;
			break;
		case '_files':
			$fields['file']="varchar(255) NOT NULL";
			$fields['file_size']="integer NULL";
			$fields['file_width']="integer NULL";
			$fields['file_height']="integer NULL";
			$fields['file_type']="varchar(150) NULL";
			$fields['tablename_id']="integer NOT NULL Default 0";
			$fields['tablename']="varchar(100) NULL";
			$fields['category']="varchar(100) NULL";
			$fields['description']="varchar(255) NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"file"));
			addMetaData($table);
			return 1;
			break;
		case '_forms':
			$fields['email']="varchar(255) NULL";
			$fields['_formname']="varchar(100) NOT NULL";
			$fields['http_host']="varchar(255) NULL";
			$fields['script_url']="varchar(255) NULL";
			$fields['_xmldata']="text NOT NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_formname"));
			addMetaData($table);
			return 1;
			break;
		case '_history':
			$fields['action']="char(5) NULL";
			$fields['page_id']="INT NULL";
			$fields['tablename']="varchar(255) NOT NULL";
			$fields['record_id']="INT NOT NULL";
			$fields['xmldata']="text NULL";
			$fields['md5']="char(32) NOT NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename"));
			addMetaData($table);
			return 1;
			break;
		case '_minify':
			$fields['name']="varchar(100) NOT NULL";
			$fields['version']="INT NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexs
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			//$query="alter table {$table} add index {$index_name} ({$index_tables})";
			return 1;
			break;
		case '_models':
			$fields['name']="varchar(100) NOT NULL UNIQUE";
			$fields['mtype']="INT NOT NULL Default 1";
			$fields['active']="tinyint(1) NOT NULL Default 1";
			$fields['functions']="mediumtext NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexs
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			addMetaData($table);
			//$query="alter table {$table} add index {$index_name} ({$index_tables})";
			return 1;
			break;
		case '_pages':
			/*other possible fields
				sync tinyint(1) NOT NULL Default 1 - if not checked then do not sync
				postedit tinyint(1) NOT NULL Default 1 - if not checked then do not show in postedit
				user_content text NULL - for user driven content without code
			*/
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_aip']="char(15) NULL";
			$fields['_auser']="integer NULL";
			$fields['_counter']="integer NULL";
			$fields['_amem']=databaseDataType('bigint')." NULL";
			$fields['_env']="text NULL";
			$fields['_template']="INT NOT NULL Default 1";
			$fields['_cache']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['body']=databaseDataType('mediumtext')." NULL";
			$fields['controller']="text NULL";
			$fields['menu']=databaseDataType('tinyint')." NULL";
			$fields['functions']=databaseDataType('mediumtext')." NULL";
			$fields['js']="text NULL";
			$fields['css']="text NULL";
			$fields['js_min']="text NULL";
			$fields['css_min']="text NULL";
			$fields['user_content']="text NULL";
			$fields['description']="varchar(255) NULL";
			$fields['title']="varchar(255) NULL";
			$fields['parent']="varchar(50) NULL";
			$fields['sort_order']=databaseDataType('smallint')." NOT NULL Default 0";
			$fields['postedit']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['name']="varchar(50) NOT NULL";
			$fields['permalink']="varchar(255) NULL";
			$fields['page_type']=databaseDataType('smallint')." NOT NULL Default 0";
			$fields['meta_description']="varchar(255) NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"permalink"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"page_type"));
			//insert default files for this table from the schema directory
			schemaAddFileData($table);
			addMetaData($table);
			return 1;
			break;
		case '_pagelog':
			$fields['page_name']="varchar(150) NOT NULL";
			$fields['page_id']="integer NOT NULL";
			$fields['user_id']="integer NOT NULL";
			$fields['command']="varchar(255) NOT NULL";
			$fields['description']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'listfields'	=> "_cdate,page_name\r\npage_id\r\nuser_id\r\ncommand",
				'sortfields'	=> "_cdate desc, name",
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_queries':
			$fields['run_length']=databaseDataType('real(8,3)')." NOT NULL Default 0.000";
			$fields['query']="text NULL";
			$fields['page_id']="int NULL";
			$fields['row_count']="int NULL";
			$fields['field_count']="int NULL";
			$fields['function']="varchar(25) NULL";
			$fields['fields']="varchar(255) NULL";
			$fields['tablename']="varchar(255) NULL";
			$fields['user_id']="int NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "function run_length user_id page_id row_count field_count\r\nfields\r\nquery",
				'listfields'	=> "_cdate function run_length user_id tablename page_id row_count field_count",
				'sortfields'	=> 'run_length desc',
				'formfields_mod'=> "function run_length user_id page_id row_count field_count\r\nfields\r\nquery",
				'listfields_mod'=> "_cdate function run_length user_id tablename page_id row_count field_count",
				'sortfields_mod'	=> 'run_length desc'
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_wpass':
			$fields['title']="varchar(100) NOT NULL";
			$fields['category']="varchar(50) NOT NULL";
			$fields['user']="varchar(60) NOT NULL";
			$fields['pass']="varchar(40) NOT NULL";
			$fields['url']="varchar(255) NOT NULL";
			$fields['notes']="mediumtext NULL";
			$fields['users']="varchar(1000) NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser,category,title",'-unique'=>1));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "category title\r\nuser pass\r\nurl\r\nnotes",
				'listfields'	=> "_cdate\r\n_cuser\r\ncategory\r\ntitle\r\nurl",
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_sessions':
			$fields['session_id']="char(40) NOT NULL UNIQUE";
			$fields['session_data']="mediumtext NULL";
			$fields['touchtime']="int NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"touchtime"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "touchtime session_id\r\nsession_data",
				'listfields'	=> "touchtime session_id",
				'sortfields'	=> 'touchtime desc'
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case '_tabledata':
			$fields['formfields']="text NULL";
			$fields['formfields_mod']="text NULL";
			$fields['listfields']="text NULL";
			$fields['listfields_mod']="text NULL";
			$fields['sortfields']="varchar(255) NULL";
			$fields['sortfields_mod']="varchar(255) NULL";
			$fields['tablename']="varchar(255) NOT NULL";
			$fields['tablegroup']="varchar(255) NULL";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['tabledesc']="varchar(500) NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename",'-unique'=>true));
			//insert default data
			//_access
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_access',
				'listfields'	=> '_cdate status http_host page remote_addr guid session_id remote_device remote_browser remote_os',
				'sortfields'	=> '_cdate desc'
				));
			//_access_summary
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_access_summary',
				'listfields'	=> 'accessdate http_host visits visits_unique page_unique remote_browser_unique remote_os_unique remote_device_unique session_id_unique guid_unique',
				'sortfields'	=> '_cdate desc'
				));
			//_fielddata
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_fielddata',
				'formfields'	=> "tablename fieldname displayname\r\ndescription synchronize\r\ninputtype related_table behavior postedit\r\nwidth height inputmax required editlist mask\r\nonchange\r\ntvals\r\ndvals\r\ndefaultval\r\nhelp",
				'listfields'	=> "tablename\r\nfieldname\r\ninputtype\r\nwidth\r\nheight\r\ninputmax\r\neditlist\r\ndescription",
				'sortfields'	=> 'tablename, fieldname, inputtype',
				'synchronize'	=> 1
				));
			
			//_forms
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_forms',
				'formfields'	=> "_formname email\r\nhttp_host script_url\r\n_xmldata",
				'listfields'	=> '_formname email http_host script_url _cdate',
				'sortfields'	=> "_formname, _cdate desc"
				));
			//_history
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_history',
				'formfields'	=> "_cuser action page_id record_id\r\ntablename\r\nxmldata",
				'listfields'	=> '_cuser action page_id record_id tablename',
				'sortfields'	=> '_cdate desc'
				));
			//_chat
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_chat',
				'formfields'	=> "_cdate user_id message\r\ncomputer_name computer_user computer_domain",
				'listfields'	=> '_cdate user_id message computer_name computer_user computer_domain',
				'sortfields'	=> '_cdate desc'
				));
			//_pages
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_pages',
				'formfields'	=> "name title _template sort_order\r\npermalink\r\ndescription\r\nmeta_description\r\nbody\r\ncontroller\r\nfunctions\r\ncss\r\njs",
				'listfields'	=> 'name permalink _template sort_order description',
				'formfields_mod'=> "name\r\ndescription",
				'listfields_mod'=> 'name description',
				'sortfields'	=> '_cdate desc',
				'synchronize'	=> 1
				));
			//_tabledata
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_tabledata',
				'formfields'	=> "tablename\r\nformfields\r\nlistfields\r\nsortfields\r\nformfields_mod\r\nlistfields_mod\r\nsortfields_mod",
				'listfields'	=> 'tablename sortfields sortfields_mod',
				'sortfields'	=> 'tablename, _cdate desc',
				'synchronize'	=> 1
				));
			//_templates
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_templates',
				'formfields'	=> "name\r\ndescription\r\nbody\r\nfunctions\r\ncss\r\njs",
				'listfields'	=> 'name description',
				'formfields_mod'=> "name\r\ndescription\r\nbody",
				'listfields_mod'=> 'name description',
				'sortfields'	=> 'name',
				'synchronize'	=> 1
				));
			//_users
			$id=addDBRecord(array('-table'=>$table,
				'tablename'		=> '_users',
				'formfields'	=> "utype active\r\nusername password\r\ntitle\r\nfirstname lastname\r\naddress1\r\naddress2\r\ncountry email\r\ncity state zip\r\npicture\r\nbio\r\nnote",
				'listfields'	=> 'active utype username firstname lastname email _adate _aip',
				'sortfields'	=> '_adate desc',
				'formfields_mod'=> "username password\r\ntitle\r\nfirstname lastname\r\naddress1\r\naddress2\r\ncountry email\r\ncity state zip\r\npicture\r\nbio",
				'listfields_mod'=> 'username firstname lastname email',
				'sortfields_mod'=> 'lastname, firstname, username'
				));
			addMetaData($table);
			return 1;
			break;
		case '_templates':
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_aip']="char(15) NULL";
			$fields['_auser']="integer NULL";
			$fields['body']=databaseDataType('mediumtext')." NULL";
			$fields['functions']=databaseDataType('mediumtext')." NULL";
			$fields['js']="text NULL";
			$fields['css']="text NULL";
			$fields['js_min']="text NULL";
			$fields['css_min']="text NULL";
			$fields['description']="varchar(255) NULL";
			$fields['name']="varchar(50) NOT NULL";
			$fields['postedit']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			//insert default files for this table from the schema directory
			schemaAddFileData($table);
			addMetaData($table);
			return 1;
			break;
		case '_settings':
			$fields['key_name']="varchar(25) NOT NULL";
			$fields['key_value']="varchar(5000) NULL";
			$fields['user_id']="integer NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"key_name,user_id",'-unique'=>true));
			//set queries on by default
			$id=addDBRecord(array('-table'=>'_settings',
				'key_name'		=> 'wasql_queries',
				'key_value'		=> 1,
				'user_id'		=> 0,
				));
			$id=addDBRecord(array('-table'=>'_settings',
				'key_name'		=> 'wasql_queries_time',
				'key_value'		=> 0.25,
				'user_id'		=> 0,
				));
			$id=addDBRecord(array('-table'=>'_settings',
				'key_name'		=> 'wasql_queries_days',
				'key_value'		=> 10,
				'user_id'		=> 0,
				));
			addMetaData($table);
			return 1;
			break;
		case '_synchronize':
			//sync_action,diff_table,diff_id,sync_items
			$fields['sync_action']="char(15) NOT NULL";
			$fields['user_id']="INT NOT NULL";
			$fields['note']="varchar(500) NULL";
			$fields['sync_items']="varchar(500) NULL";
			//code review fields
			$fields['review_pass']="varchar(25) NULL";
			$fields['review_user']="varchar(255) NULL";
			$fields['review_user_id']="integer NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			addMetaData($table);
			return 1;
			break;
		case '_users':
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_apage']="INT NULL";
			$fields['_aip']="char(15) NULL";
			$fields['_env']="text NULL";
			$fields['_sid']="varchar(150) NULL";
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['address1']="varchar(255) NULL";
			$fields['address2']="varchar(255) NULL";
			$fields['city']="varchar(50) NULL";
			$fields['country']="char(2) NOT NULL Default 'US'";
			$fields['email']="varchar(255) NULL";
			$fields['guid']="char(40) NULL";
			$fields['hint']="varchar(255) NULL";
			$fields['firstname']="varchar(100) NULL";
			$fields['lastname']="varchar(150) NULL";
			$fields['title']="varchar(255) NULL";
			$fields['note']="varchar(255) NULL";
			$fields['password']="varchar(25) NOT NULL";
			$fields['phone']="varchar(25) NULL";
			$fields['state']="char(5) NULL";
			$fields['username']="varchar(255) NOT NULL";
			$fields['utype']=databaseDataType('smallint')." NOT NULL Default 1";
			$fields['zip']="varchar(10) NULL";
			$fields['picture']="varchar(255) NULL";
			$fields['bio']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"username",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"guid"));
			//Add admin user as the default admin
			$addopts=array();
			foreach($fields as $field=>$type){
				if(strlen($CONFIG["default_{$field}"])){$addopts[$field]=$CONFIG["default_{$field}"];}
            	}
            if(!strlen($addopts['username'])){$addopts['username']='admin';}
			if(strlen($addopts['password'])){
				if(!userIsEncryptedPW($addopts['password'])){$addopts['password']=userEncryptPW($addopts['password']);}
				}
			else{$addopts['password']=userEncryptPW('admin');}
			if(!strlen($addopts['email'])){$addopts['email']='admin@'.strtolower($_SERVER['UNIQUE_HOST']);}
            $addopts['-table']=$table;
            $addopts['utype']=0;
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
			break;
		case 'states':
			$fields['code']="char(7) NOT NULL";
			$fields['name']="varchar(50) NOT NULL";
			$fields['country']="char(2) NOT NULL Default 'US'";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,country",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"code"));
			//populate the states with states and provinces for USA and Canada
			if(!schemaUpdateStates()){
				$progpath=dirname(__FILE__);
				$files=listFilesEx("$progpath/schema",array('name'=>"states_",'ext'=>"csv"));
				foreach($files as $file){
					$csv=getCSVFileContents($file['afile']);
					$tmp=preg_split('/\_/',getFileName($file['name'],1));
					$country=strtoupper(array_pop($tmp));
					foreach($csv['items'] as $item){
		                $item['-table']=$table;
						if(!isset($item['country'])){$item['country']=$country;}
						$item['name']=utf8_encode($item['name']);
		                $id=addDBRecord($item);
		                if(!isNum($id)){abort(printValue($id).printValue($item));}
					}
				}
	        }
            addMetaData($table);
            return 1;
			break;
		case 'contact_form':
			$fields['name']="varchar(150) NULL";
			$fields['email']="varchar(252) NOT NULL";
			$fields['subject']="varchar(150) NOT NULL";
			$fields['message']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			addMetaData($table);
			break;
		case 'countries':
			$fields['code']="char(2) NOT NULL";
			$fields['name']="varchar(100) NOT NULL";
			$fields['tlds']="varchar(255) NULL";
			$fields['currency']="varchar(150) NULL";
			$fields['calling_code']="varchar(25) NULL";
			$fields['language']="varchar(255) NULL";
			$fields['border_countries']="varchar(255) NULL";
			$fields['ccn3']="char(5) NULL";
			$fields['code3']="char(5) NULL";
			$fields['capital']="varchar(150) NULL";
			$fields['region']="varchar(150) NULL";
			$fields['subregion']="varchar(150) NULL";
			$fields['population']="integer NOT NULL Default 0";
			$fields['latitude']=databaseDataType('bigint')." NULL";
			$fields['longitude']=databaseDataType('bigint')." NULL";
			$fields['relevance']="integer NOT NULL Default 0";
			$fields['population_rank']="integer NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"code"));
			addMetaData($table);
			//populate the table if there is a countries.csv
			$progpath=dirname(__FILE__);
			if(!schemaUpdateCountries() && is_file("$progpath/schema/countries.csv")){
				$csv=getCSVFileContents("$progpath/schema/countries.csv");
				foreach($csv['items'] as $item){
                	$item['-table']=$table;
                	$id=addDBRecord($item);
                	if(!isNum($id)){abort(printValue($id).printValue($item));}
				}
            }
            addMetaData($table);
            return 1;
			break;
		default:
			return "createWasqlTable Error: {$table} is not a valid WaSQL table";
			break;
    	}
	return 0;
	}
//---------- begin function schemaUpdateCountries
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaUpdateCountries(){
	$url='https://raw.githubusercontent.com/mledoze/countries/master/countries.json';
	$post=postURL($url,array('-method'=>'GET','-ssl'=>false,'-ssl_version'=>3));
	$countries=json_decode($post['body'], true);
	if(!isset($countries[0]['name'])){return false;}
	$recs=array();
	foreach($countries as $country){
		if(is_array($country['name'])){
        	$name=$country['name']['common'];
        	if(isset($country['name']['native']['common']) && $country['name']['native']['common'] != $name){
				$name .= " ({$country['name']['native']['common']})";
			}
			$country['name']=$name;
		}
    	$rec=array('name'=>$country['name']);
    	$tlds=array();
    	foreach($country['tld'] as $tld){
        	$tld=preg_replace('/^\./','',$tld);
        	$tlds[]=$tld;
		}
		$rec['tlds']=implode(':',$tlds);
		$rec['currency']=implode(':',$country['currency']);
		$rec['calling_code']=implode(':',$country['callingCode']);
		if(is_array($country['language'])){
			$rec['language']=implode(':',$country['language']);
		}
		else{$rec['language']=$country['language'];}
		$rec['border_countries']=implode(':',$country['borders']);
		$rec['ccn3']=$country['ccn3'];
		$rec['code']=$country['cca2'];
		$rec['code3']=$country['cca3'];
		$rec['capital']=$country['capital'];
		$rec['region']=$country['region'];
		$rec['subregion']=$country['subregion'];
		$rec['population']=$country['population'];
		$rec['latitude']=$country['latlng'][0];
		$rec['longitude']=$country['latlng'][1];
		$rec['relevance']=$country['relevance'];
		switch(strtoupper($rec['code'])){
        	case 'US':$rec['tlds']='com';break;
        	case 'UK':$rec['tlds']='co.uk:uk';break;
        	case 'AU':$rec['tlds']='co.au:au';break;
		}
		//remove blanks
		foreach($rec as $key=>$val){
			if(!strlen($val)){unset($rec[$key]);}
		}
		$crec=getDBRecord(array(
			'-table'=>'countries',
			'code'	=> $rec['code'],
			'name'	=> $rec['name']
		));
		if(isset($crec['_id'])){
        	$rec['-table']='countries';
        	$rec['-where']="_id={$crec['_id']}";
        	$ok=editDBRecord($rec);
		}
		else{
			$crecs=getDBRecords(array(
				'-table'=>'countries',
				'code'	=> $rec['code']
			));
			if(is_array($crecs) && count($crecs)==1){
            	$rec['-table']='countries';
        		$rec['-where']="_id={$crecs[0]['_id']}";
        		$ok=editDBRecord($rec);
        		//echo printValue($ok).printValue($rec);
			}
			else{

				$rec['-table']='countries';
				$rec['newid']=addDBRecord($rec);
				if(!isNum($rec['newid'])){
					echo printValue($rec['newid']).printValue($rec);exit;
				}
			}
		}
		//$recs[]=$rec;
	}
	//determine population_rank
	$recs=getDBRecords(array(
		'-table'	=> 'countries',
		'-order'	=> 'population desc,relevance desc',
		'-fields'	=> '_id,name,code,population,relevance'
	));
	foreach($recs as $i=>$rec){
		$rank=$i+1;
    	$ok=executeSQL("update countries set population_rank={$rank} where _id={$rec['_id']}");
    	$recs[$i]['population_rank']=$rank;
	}
	return true;
}
//---------- begin function schemaUpdateCountries
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaUpdateStates(){
	$base_url='https://github.com/astockwell/countries-and-provinces-states-regions/tree/master/countries';
	$post=postURL($base_url,array('-method'=>'GET','-ssl'=>false,'-ssl_version'=>3));
	preg_match_all('/a href\=\"(.+?)\/([a-z\-]+?)\.json\"/',$post['body'],$m);
	if(!isset($m[0][0])){return false;}
	$cnt=count($m[0]);
	for($x=0;$x<$cnt;$x++){
    	$url="https://raw.githubusercontent.com/astockwell/countries-and-provinces-states-regions/master/countries/{$m[2][$x]}.json";
    	$post=postURL($url,array('-method'=>'GET','-json'=>1,'-ssl'=>false,'-ssl_version'=>3));
    	if(!is_array($post['json_array'])){continue;}
    	$country=getDBRecord(array(
			'-table'=>'countries',
			'name'	=> str_replace('-',' ',$m[2][$x])
		));
		if(!isset($country['code'])){continue;}
    	foreach($post['json_array'] as $rec){
			$rec['name']=str_replace(',',' ',$rec['name']);
			$crec=getDBRecord(array(
				'-table'=>'states',
				'country'=> $country['code'],
				'name'	=> $rec['name']
			));
			if(isset($crec['_id'])){continue;}
			$rec['-table']='states';
			$rec['country']=$country['code'];
			$ok=addDBRecord($rec);
		}
	}
	return true;
}
//---------- begin function addMetaData
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function addMetaData($table=''){
	//echo "addMetaData[{$table}]<br>\n";
	//delete existing metadata for this table
	$ok=delDBRecord(array('-table'=>"_fielddata",'-where'=>"tablename = '{$table}'"));
	switch(strtolower($table)){
		//_fielddata
		case '_fielddata':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'displayname',
				'inputtype'		=> 'text',
				'width'			=> 130,
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'synchronize',
				'description'	=> 'if not checked then do not synchronize with live db',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'postedit',
				'description'	=> 'if not checked then do not show in postedit',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'behavior',
				'inputtype'		=> 'multiselect',
				'tvals'			=> '<?='.'wasqlGetBehaviors();'.'?>',
				'dvals'			=> '<?='.'wasqlGetBehaviors(1);'.'?>',
				'width'			=> 100
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'mask',
				'inputtype'		=> 'select',
				'tvals'			=> '<?='.'wasqlGetMasks();'.'?>',
				'dvals'			=> '<?='.'wasqlGetMasks(1);'.'?>'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'inputtype',
				'inputtype'		=> 'select',
				'required'		=> 1,
				'onchange'		=> "fielddataChange(this)",
				'displayname'	=> "Input Type",
				'tvals'			=> '<?='.'wasqlGetInputtypes();'.'?>',
				'dvals'			=> '<?='.'wasqlGetInputtypes(1);'.'?>',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'defaultval',
				'displayname'	=> 'Default Value',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 40,
				'inputmax'		=> 255
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'help',
				'displayname'	=> 'Help (shown onmouseover field name)',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 40,
				'inputmax'		=> 255
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'dvals',
				'displayname'	=> 'Display Values',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'sqleditor',
				'width'			=> 400,
				'height'		=> 60
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'tvals',
				'displayname'	=> 'True Values',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'sqleditor',
				'width'			=> 400,
				'height'		=> 60
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'editlist',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'required',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'width',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 5,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'height',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 5,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'inputmax',
				'inputtype'		=> 'text',
				'width'			=> '50',
				'inputmax'		=> 5,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'fieldname',
				'inputtype'		=> 'text',
				'width'			=> '130',
				'inputmax'		=> 100,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'onchange',
				'displayname'	=> 'onchange Event',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> '320',
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'tablename',
				'inputtype'		=> 'select',
				'width'			=> 135,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'related_table',
				'displayname'	=> 'Related Table',
				'width'			=> 135,
				'inputtype'		=> 'select',
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				));
			break;
		//_pages
		case '_pages':
			//_pages
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'body',
				'displayname'		=> 'Body (View)',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'xmleditor',
				'width'			=> '700',
				'height'		=> '250',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'controller',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'			=> '200',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'functions',
				'displayname'		=> 'Functions',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'			=> '200',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'css',
				'displayname'	=> 'CSS / Stylesheet',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'csseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'js',
				'displayname'	=> 'Javascript',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'jseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '300',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'permalink',
				'inputtype'		=> 'text',
				'width'			=> '700',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'sort_order',
				'displayname'	=> 'SortOrder',
				'inputtype'		=> 'text',
				'width'			=> '25',
				'editlist'		=> 1,
				'mask'			=> "integer",
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> 700,
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'meta_description',
				'inputtype'		=> 'text',
				'width'			=> 700,
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'user_content','displayname'=>'User Content',
				'description'	=> 'A place for user driven content without logic',
				'inputtype'		=> 'textarea',
				'width'			=> '700',
				'height'		=> '120'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> '_template',
				'displayname'	=> 'Template',
				'inputtype'		=> 'select',
				'tvals'			=> 'select _id from _templates order by name,_id',
				'dvals'			=> 'select name from _templates order by name,_id',
				'required'		=> 1,
				'defaultval'	=> 2
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'page_type',
				'displayname'	=> 'PageType',
				'inputtype'		=> 'select',
				'tvals'			=> "1\r\n2\r\n3\r\n4",
				'dvals'			=> "WebPage\r\nBlog\r\nForum\r\nController",
				'required'		=> 1,
				'defaultval'	=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'parent',
				'inputtype'		=> 'select',
				'tvals'			=> "select name from _pages order by name",
				'dvals'			=> "select name from _pages order by name"
				));
			break;
		//_tabledata
		case '_tabledata':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'synchronize',
				'inputtype'		=> 'checkbox',
				'tvals'			=> 1,
				'required'		=> 0,
				'synchronize'	=> 0,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'tablegroup',
				'inputtype'		=> 'text',
				'width'			=> '145',
				'inputmax'		=> '50',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'tabledesc',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '40',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'listfields',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '40',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'sortfields',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '30',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'formfields',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '100',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'listfields_mod',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '40',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'sortfields_mod',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '30',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'formfields_mod',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'autogrow',
				'width'			=> '550',
				'height'		=> '100',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0
				));
		//_templates
		case '_templates':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'body',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'xmleditor',
				'width'			=> '700',
				'height'		=> '300',
				'required'		=> 1,
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'functions',
				'displayname'	=> 'Controller - functions',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'		=> '200',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'css',
				'displayname'	=> 'CSS / Stylesheet',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'csseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'js',
				'displayname'	=> 'Javascript',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'jseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '700',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> '700',
				));
			break;
		//_wpass
		case '_wpass':
			//_fielddata for _wpass
				$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'category',
				'inputtype'		=> 'text',
				'width'			=> '150',
				'height'		=> 200,
				'inputmax'		=> 80,
				'tvals'			=> '<?='.'wpassGetCategories();'.'?>',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 100,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'user',
				'displayname'	=> 'Username **',
				'inputtype'		=> 'text',
				'width'			=> '225',
				'inputmax'		=> 60,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'pass',
				'displayname'	=> 'Password **',
				'inputtype'		=> 'text',
				'width'			=> '150',
				'inputmax'		=> 40,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'url',
				'displayname'	=> 'URL **',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'notes',
				'displayname'	=> 'Notes **',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 200,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'users',
				'displayname'	=> 'Share With',
				'inputtype'		=> 'checkbox',
				'width'			=> 3,
				'tvals'			=> 'select _id from _users where _id !=<?=userValue(\'_id\');?> and active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'dvals'			=> 'select firstname,lastname from _users where _id !=<?=userValue(\'_id\');?> and active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'required'		=> 0
				));
		break;
		//_users
		case '_users':
			//_fielddata for _users
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'username',
				'inputtype'		=> 'text',
				'width'			=> '150',
				'inputmax'		=> 255,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'password',
				'inputtype'		=> 'password',
				'width'			=> '100',
				'inputmax'		=> 25,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'utype',
				'inputtype'		=> 'select',
				'required'		=> 1,
				'displayname'	=> "User Rights",
				'tvals'			=> "0\r\n1",
				'dvals'			=> "Administrator\r\nNormal",
				'defaultval'	=> 1,
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'tvals'			=> "1",
				'defaultval'	=> 1,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'address1',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'address2',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'firstname',
				'inputtype'		=> 'text',
				'width'			=> '180',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'lastname',
				'inputtype'		=> 'text',
				'width'			=> '180',
				'inputmax'		=> 255,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'zip',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 10,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'phone',
				'inputtype'		=> 'text',
				'width'			=> '100',
				'inputmax'		=> 25,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'email',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'mask'			=> 'email',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'city',
				'inputtype'		=> 'text',
				'width'			=> '170',
				'inputmax'		=> 255,
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'state',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'width'			=> 150,
				'displayname'	=> "State",
				'tvals'			=> '<?='.'wasqlGetStates();'.'?>',
				'dvals'			=> '<?='.'wasqlGetStates(1);'.'?>'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'country',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'defaultval'	=> 'US',
				'onchange'		=> "redrawField('state',this);",
				'displayname'	=> "Country",
				'tvals'			=> '<?='.'wasqlGetCountries();'.'?>',
				'dvals'			=> '<?='.'wasqlGetCountries(1);'.'?>'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'bio',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 75,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'picture',
				'inputtype'		=> 'file',
				'width'			=> 400,
				'defaultval'	=> '/files/users',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'note',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 50,
				'inputmax'		=> 255,
				'required'		=> 0
				));
			break;
		//countries
		case 'contact_form':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> 300,
				'inputmax'		=> 150,
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'email',
				'inputtype'		=> 'text',
				'width'			=> 300,
				'inputmax'		=> 255,
				'mask'			=> 'email',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'subject',
				'inputtype'		=> 'text',
				'width'			=> 300,
				'inputmax'		=> 150,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'message',
				'inputtype'		=> 'textarea',
				'width'			=> 300,
				'height'		=> 150
				));
			break;
		//countries
		case 'countries':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'countries',
				'fieldname'		=> 'code',
				'inputtype'		=> 'text',
				'width'			=> 60,
				'inputmax'		=> 2,
				'defaultval'	=> 'US',
				'required'		=> 1
				));
			break;
		//states
		case 'states':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'states',
				'fieldname'		=> 'code',
				'inputtype'		=> 'text',
				'width'			=> 60,
				'inputmax'		=> 5,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'states',
				'fieldname'		=> 'country',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'defaultval'	=> 'US',
				'displayname'	=> "Country",
				'tvals'			=> '<?='.'wasqlGetCountries();'.'?>',
				'dvals'			=> '<?='.'wasqlGetCountries(1);'.'?>'
				));
			break;
		//_forms
		case '_forms':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_forms',
				'fieldname'		=> 'email',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'mask'			=> 'email',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_forms',
				'fieldname'		=> '_formname',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_forms',
				'fieldname'		=> '_xmldata',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 300,
				'required'		=> 0
				));
			break;
		//_forms
		case '_reports':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'query',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'heiht'			=> '200',
				'behavior'		=> 'sqleditor'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 100,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'editlist'		=> 1,
				'defaultval'	=> 1,
				'tvals'			=> 1,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'menu',
				'inputtype'		=> 'text',
				'width'			=> 120,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'list_options',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'heiht'			=> '800',
				'displayname'	=> 'List Options (JSON)'
				));
			break;
		//_forms
		case '_synchronize':
			$id=addDBRecord(array('-table'=>"_tabledata",
				'tablename'		=> '_synchronize',
				'formfields'	=> "user_id\r\nnote",
				'listfields'	=> "_cdate\r\nuser_id\r\nsync_items\r\n",
				'sortfields'	=> '_cdate desc'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'note',
				'inputtype'		=> 'textarea',
				'width'			=> '300',
				'height'		=> '100',
				'inputmax'		=> 500,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'sync_items',
				'inputtype'		=> 'textarea',
				'width'			=> '300',
				'height'		=> '100',
				'inputmax'		=> 500,
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'user_id',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "User",
				'tvals'			=> "select _id from _users order by firstname,lastname,_id",
				'dvals'			=> "select firstname,lastname from _users order by firstname,lastname,_id"
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'review_user',
				'inputtype'		=> 'text',
				'width'			=> '140',
				'inputmax'		=> 255,
				'displayname'	=> "Username",
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'review_pass',
				'inputtype'		=> 'password',
				'width'			=> '140',
				'inputmax'		=> 25,
				'displayname'	=> "Password",
				));
			break;
		//_history
		case '_history';
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'record_id',
				'inputtype'		=> 'text',
				'width'			=> 40,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'action',
				'inputtype'		=> 'text',
				'width'			=> 60,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'md5',
				'inputtype'		=> 'text',
				'width'			=> 200,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'page_id',
				'inputtype'		=> 'textarea',
				'inputtype'		=> 'text',
				'width'			=> 40,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'xmldata',
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 400,
				'required'		=> 0
				));
			break;
		//_history
		case '_chat':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_chat',
				'fieldname'		=> 'message',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 100,
				'required'		=> 1,
				));
			break;
		case '_cron':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'running',
				'inputtype'		=> 'checkbox',
				'synchronize'	=> 0,
				'tvals'			=> '1',
				'editlist'		=> 1,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_log',
				'inputtype'		=> 'checkbox',
				'tvals'			=> 1,
				'editlist'		=> 1,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_length',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 100,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'cron_pid',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 60,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'active',
				'synchronize'	=> 0,
				'defaultval'	=> 1,
				'inputtype'		=> 'checkbox',
				'tvals'			=> 1,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'frequency',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "Frequency",
				'tvals'			=> "5\r\n10\r\n15\r\n30\r\n60\r\n1440\r\n720\r\n10080\r\n43829",
				'dvals'			=> "Every 5 minutes\r\nEvery 10 Minutes\r\nEvery 15 Minutes\r\nEvery 30 Minutes\r\nEvery Hour\r\nOnce Every  Day\r\nTwice Every Day\r\nOnce a Week\r\nOnce a Month"
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_date',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 250,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_result',
				'synchronize'	=> 0,
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 200,
				'required'		=> 0
				));

			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_values',
				'inputtype'		=> 'text',
				'width'			=> 240,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'logfile',
				'inputtype'		=> 'text',
				'maxlength'		=> 255,
				'width'			=> 350,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'logfile_maxsize',
				'displayname'	=> 'Logfile Maxsize (bytes)',
				'mask'			=> 'integer',
				'inputtype'		=> 'text',
				'width'			=> 130,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_format',
				'inputtype'		=> 'text',
				'width'			=> 125,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_cmd',
				'inputtype'		=> 'text',
				'width'			=> 500,
				'required'		=> 1
				));
			break;
		case '_files':
			$id=addDBRecord(array('-table'=>"_tabledata",
				'tablename'		=> '_files',
				'formfields'	=> "file\r\ntablename tablename_id\r\ncategory\r\ndescription",
				'listfields'	=> "_cdate\r\n_edate\r\nfile\r\nfile_size\r\nfile_type\r\ntablename\r\ntablename_id",
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'file',
				'inputtype'		=> 'file',
				'width'			=> 400,
				'required'		=> 0,
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'category',
				'inputtype'		=> 'text',
				'width'			=> 200,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'description',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 70,
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_files',
				'fieldname'		=> 'tablename',
				'inputtype'		=> 'select',
				'width'			=> 150,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 0
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'tablename_id',
				'mask'			=> 'integer',
				'inputtype'		=> 'text',
				'width'			=> 30,
				'required'		=> 0
				));
			break;
		case '_models':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_models',
				'fieldname'		=> 'name',
				'inputtype'		=> 'select',
				'width'			=> 150,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_models',
				'fieldname'		=> 'mtype',
				'displayname'	=> 'Type',
				'inputtype'		=> 'select',
				'tvals'			=> "0\r\n1",
				'dvals'			=> "Class\r\nFunctions",
				'onchange'		=> "changeModelType(this)",
				'required'		=> 1,
				'defaultval'	=> 1
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_models',
				'fieldname'		=> 'functions',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '275',
				//'behavior'		=> 'phpeditor'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_models',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'defaultval'	=> 1,
				'tvals'			=> 1
				));
			$id=addDBRecord(array('-table'=>'_tabledata',
				'tablename'		=> '_models',
				'formfields'	=> "name mtype active\r\nfunctions",
				'listfields'	=> 'name mtype active',
				'sortfields'	=> 'name',
				'synchronize'	=> 1
				));
			break;
		case '_sessions':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_sessions',
				'fieldname'		=> 'session_data',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '275'
				));
		break;
		case '_queries':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'run_length',
				'inputtype'		=> 'text',
				'width'			=> '60',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'function',
				'inputtype'		=> 'text',
				'width'			=> '100',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'page_id',
				'inputtype'		=> 'text',
				'width'			=> '60',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'fields',
				'inputtype'		=> 'text',
				'width'			=> '500',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'row_count',
				'inputtype'		=> 'text',
				'width'			=> '60',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'field_count',
				'inputtype'		=> 'text',
				'width'			=> '60',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'user_id',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "User",
				'tvals'			=> "select _id from _users order by firstname,lastname,_id",
				'dvals'			=> "select firstname,lastname from _users order by firstname,lastname,_id"
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_queries',
				'fieldname'		=> 'query',
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 200,
				'required'		=> 0
				));
			break;
		}
	}
//---------- begin function getWasqlTables
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getWasqlTables(){
	global $CONFIG;
	//info: returns an array of internal WaSQL table names
	$tables=array(
		'_fielddata','_tabledata','countries','states','contact_form',
		'_access','_access_summary','_history','_cron','_cronlog','_pages','_pagelog','_queries',
		'_templates','_settings','_synchronize','_users','_forms','_files','_minify',
		'_reports','_models','_sessions'
		);
	//include wpass table?
	if($CONFIG['wpass']){$tables[]='_wpass';}
	return $tables;
	}
//---------- begin function schemaAddFileData
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaAddFileData($table){
	global $CONFIG;
	$progpath=dirname(__FILE__);
	$files=listFilesEx("$progpath/schema",array('name'=>$table));
	$tables=array();
	foreach($files as $file){
        list($fname,$ftable,$field,$fid,$fext)=preg_split('/\./',$file['name']);
		if(strtolower($ftable) != strtolower($table)){continue;}
		$fbody=getFileContents($file['afile']);
		if(!isset($tables[$table][$fid])){
        	$tables[$table][$fid]=array(
				'-table'=>$table,
				'name'			=> $fname,
				$field			=> $fbody,
				'_id'			=> $fid
			);
			if($table=='_reports'){
            	$tables[$table][$fid]['menu']="_reports";
            	$tables[$table][$fid]['name']=ucwords(str_replace('_',' ',$fname));
			}
			elseif($table=='_pages'){
				$tables[$table][$fid]['description']=ucwords(str_replace('_',' ',$fname));
				$tables[$table][$fid]['_template']=1;
				$tables[$table][$fid]['page_type']=4;
				if($fext=='php' && !preg_match('/^(functions|mobile|css|js)$/i',$fname)){
                	$tables[$table][$fid]['_template']=2;
				}
				switch(strtolower($fname)){
		            case 'index':
		                $tables[$table][$fid]['title']="Home";
		                $tables[$table][$fid]['permalink']="home";
		                $tables[$table][$fid]['sort_order']=1;
		                $tables[$table][$fid]['page_type']=1;
						break;
					case 'about':
		                $tables[$table][$fid]['title']="About";
		                $tables[$table][$fid]['sort_order']=2;
		                $tables[$table][$fid]['page_type']=1;
						break;
					case 'contact':
		                $tables[$table][$fid]['title']="Contact";
		                $tables[$table][$fid]['permalink']="home";
		                $tables[$table][$fid]['sort_order']=3;
		                $tables[$table][$fid]['page_type']=1;
						break;
					case 'products':
						$tables[$table][$fid]['title']="Products";
		                $tables[$table][$fid]['sort_order']=4;
		                $tables[$table][$fid]['page_type']=1;
						break;
					case 'blog':
						$tables[$table][$fid]['title']="Blog";
		                $tables[$table][$fid]['sort_order']=6;
		                $tables[$table][$fid]['page_type']=1;
						$tables[$table][$fid]['parent']="contact";
						break;
					case 'forum':
						$tables[$table][$fid]['title']="Support";
		                $tables[$table][$fid]['sort_order']=7;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['parent']="contact";
						break;
					default:
						$tables[$table][$fid]['title']=ucwords(strtolower($fname));
						break;
				}
			}
		}
		else{
			$tables[$table][$fid][$field]=$fbody;
		}
	}
	foreach($tables as $table=>$recs){
    	foreach($recs as $id=>$rec){
        	$ok=addDBRecord($rec);
		}
	}
	return count($tables[$table]);
}

?>