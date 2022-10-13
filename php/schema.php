<?php

//---------- begin function createWasqlTables
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function createWasqlTables($wtables=array()){
	//clear the cache
	if(!is_array($wtables)){$wtables=array($wtables);}
	clearDBCache('databaseTables');
	if(!count($wtables)){$wtables=getWasqlTables();}
	//echo "wtables".printValue($wtables);
	$ctables=getDBTables('',1);
	//echo "ctables".printValue($ctables);
	$rtn=array();
	foreach($wtables as $wtable){
		if(!in_array($wtable,$ctables)){
			clearDBCache(array('getDBFieldInfo','databaseTables','isDBTable'));
			$rtn[$wtable]=createWasqlTable($wtable);
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
	switch(strtolower($table)){
		case '_triggers':
			//_triggers used to be called _models. Change to _triggers if it still exists
			if(isDBTable('_models')){
				$ok=executeSQL("rename table _models to _triggers");
				$ok=executeSQL("update _tabledata set tablename='_triggers' where tablename='_models'");
				$ok=executeSQL("update _fielddata set tablename='_triggers' where tablename='_models'");
				$ok=executeSQL("update _fielddata set onchange='changeTriggerType(this)' where tablename='_triggers' and fieldname='mtype'");
				return;
			}
		break;
	}
	global $CONFIG;
	//common fields to all wasql tables
	$fields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> databaseDataType('int')." NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> databaseDataType('int')." NULL",
		);
	switch(strtolower($table)){
		case '_access':
			$fields['http_host']=databaseDataType('varchar(255)')." NULL";
			$fields['http_referer']=databaseDataType('varchar(255)')." NULL";
			$fields['page']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['remote_addr']=databaseDataType('varchar(20)')." NULL";
			$fields['remote_browser']=databaseDataType('varchar(125)')." NULL";
			$fields['remote_browser_version']=databaseDataType('varchar(125)')." NULL";
			$fields['remote_lang']=databaseDataType('varchar(10)')." NULL";
			$fields['remote_os']=databaseDataType('varchar(50)')." NULL";
			$fields['remote_device']=databaseDataType('varchar(50)')." NULL";
			$fields['session_id']=databaseDataType('varchar(50)')." NULL";
			$fields['guid']=databaseDataType('varchar(40)')." NULL";
			$fields['xml']="text NULL";
			$fields['status']=databaseDataType('smallint')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=schemaAddFileData($table);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_access_summary':
			$fields['accessdate']=databaseDataType('datetime')." NOT NULL";
			$fields['http_host']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['visits']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['visits_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['http_referer_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['page_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_addr_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_browser_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_browser_version_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_lang_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_os_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['remote_device_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['session_id_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['guid_unique']=databaseDataType('integer')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=schemaAddFileData($table);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_reports':
			$fields['name']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['menu']=databaseDataType('varchar(50)')." NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$fields['dbname']=databaseDataType('varchar(255)')." NULL";
			$fields['rowcount']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['runtime']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['icon']=databaseDataType('varchar(50)')." NULL";
			$fields['query']="text NULL";
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['options']="text NULL";
			$fields['departments']=databaseDataType('varchar(1000)')." NULL";
			$fields['users']=databaseDataType('varchar(1000)')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,menu",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			//echo $table.printValue($ok).printValue($fields);
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name menu active\r\ndbname description\r\nquery\r\noptions\r\ndepartments\r\nusers",
				'listfields'	=> "_cdate\r\n_cuser\r\n_edate\r\n_euser\r\nname\r\nmenu\r\nactive\r\nrowcount\r\nruntime",
				'sortfields'	=> "_id desc",
				'formfields_mod'=> "name menu active\r\ndbname description\r\nquery\r\noptions\r\ndepartments\r\nusers",
				'listfields_mod'=> "name\r\nmenu\r\nactive",
				'sortfields_mod'=> "name",
				'synchronize'	=> 1
				);
			$id=addDBRecord($addopts);
			$ok=schemaAddFileData($table);	
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_changelog':
			$fields['tablename']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['method']=databaseDataType('varchar(10)')." NOT NULL Default 'web'";
			$fields['fieldname']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['record_id']=databaseDataType('integer')." NOT NULL";
			$fields['changeval']=databaseDataType('mediumtext')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){ return printValue($ok);break;}
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"record_id,tablename,fieldname"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_config':
			$fields['name']=databaseDataType('varchar(200)')." NOT NULL UNIQUE";
			$fields['category']=databaseDataType('varchar(200)')." NULL";
			$fields['current_value']=databaseDataType('varchar(500)')." NULL";
			$fields['default_value']=databaseDataType('varchar(500)')." NULL";
			$fields['description']=databaseDataType('varchar(1000)')." NULL";
			$fields['possible_values']=databaseDataType('varchar(1000)')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){ return printValue($ok);break;}
			addMetaData($table);
			//Add tabledata
			$ok=addDBRecord(array(
				'-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name current_value default_value\r\ndescription\r\npossible_values",
				'listfields'	=> "name\r\ncurrent_value\r\ndefault_value\r\ndescription\r\npossible_values",
				'sortfields'	=> "name",
				'synchronize'	=> 0,
				'-upsert'		=>'ignore'
			));
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/config.csv")){
				$csv=getCSVFileContents("{$progpath}/schema/config.csv");
				debugValue("Loading {$progpath}/schema/config.csv");
				$recs=$csv['items'];
				$cfg=$CONFIG;
				$set=settingsValues(0);
				//set current config.xml values
				foreach($recs as $i=>$rec){
					$name=strtolower(trim($rec['name']));
					if(isset($cfg[$name])){
						$recs[$i]['current_value']=$cfg[$name];
						unset($cfg[$name]);
					}
					elseif(isset($set[$name])){
						$recs[$i]['current_value']=$set[$name];	
						unset($set[$name]);
					}
				}
				//add any other config or settings to recs
				foreach($cfg as $k=>$v){
					if(preg_match('/^\_/',$k)){continue;}
					if(preg_match('/^(name|insecure|database|dbhost|dbname|dbicon|dbuser|displayname|group|dbpass|dbschema|dbtype)$/i',$k)){continue;}
					$recs[]=array(
						'name'=>$k,
						'current_value'=>$v,
						'default_value'=>'',
						'description'=>'Custom setting in config.xml'
					);
				}
				foreach($set as $k=>$v){
					$recs[]=array(
						'name'=>$k,
						'current_value'=>$v,
						'default_value'=>'',
						'description'=>'Custom setting in _settings table'
					);
				}
				$ok=dbAddRecords($CONFIG['database'],$table,array('-recs'=>$recs,'-ignore'=>1));
				//debugValue($ok);
				//echo $ok.printValue($recs);exit;
			}
			else{
				//debugValue("Missing {$progpath}/schema/config.csv");
			}
			return 1;
		break;
		case '_cron':
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['begin_date']=databaseDataType('date')." NULL";
			$fields['end_date']=databaseDataType('date')." NULL";
			$fields['frequency']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['name']=databaseDataType('varchar(150)')." NULL";
			$fields['cron_pid']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['run_now']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['stop_now']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['run_as']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['run_cmd']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['run_date']=databaseDataType('datetime')." NULL";
			$fields['run_format']=databaseDataType('varchar(255)')." NULL";
			$fields['run_length']=databaseDataType('float(10,2)')." NULL";
			$fields['run_result']=databaseDataType('mediumtext')." NULL";
			$fields['run_values']=databaseDataType('varchar(255)')." NULL";
			$fields['running']=databaseDataType('tinyint')." NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"run_cmd",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name active begin_date end_date\r\nfrequency run_format frequency_max\r\nrun_cmd\r\nrun_as running run_date run_length\r\nrun_result",
				'listfields'	=> "name\r\ncron_pid\r\nactive\r\nrunning\r\nfrequency\r\nrun_format\r\nfrequency_max\r\nrun_cmd\r\nrun_date\r\nrun_length\r\nbegin_date\r\nend_date",
				'sortfields'	=> "active desc, running desc, begin_date desc",
				'synchronize'	=> 1
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_cronlog':
			$fields['name']=databaseDataType('varchar(150)')." NOT NULL";
			$fields['cron_id']=databaseDataType('integer')." NOT NULL";
			$fields['cron_pid']=databaseDataType('integer')." NOT NULL";
			$fields['delete_me']=databaseDataType('integer')." NOT NULL";
			$fields['run_cmd']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['run_date']=databaseDataType('datetime')." NOT NULL";
			$fields['run_result']=databaseDataType('mediumtext')." NULL";
			$fields['run_length']=databaseDataType('float(10,2)')." NOT NULL Default 0";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"cron_id"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cdate"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"delete_me"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'listfields'	=> "name\r\ncron_pid\r\nrun_cmd\r\nrun_date\r\nrun_length",
				'sortfields'	=> "_cdate desc, name",
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
		break;
		case '_fielddata':
			$fields['behavior']=databaseDataType('varchar(255)')." NULL";
			$fields['defaultval']=databaseDataType('varchar(255)')." NULL";
			$fields['displayname']=databaseDataType('varchar(255)')." NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['postedit']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['dvals']="text NULL";
			$fields['editlist']=databaseDataType('tinyint')." NULL";
			$fields['fieldname']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['height']=databaseDataType('integer')." NULL";
			$fields['help']=databaseDataType('varchar(255)')." NULL";
			$fields['inputmax']=databaseDataType('integer')." NULL";
			$fields['inputtype']=databaseDataType('varchar(25)')." NULL";
			$fields['mask']=databaseDataType('varchar(255)')." NULL";
			$fields['onchange']=databaseDataType('varchar(255)')." NULL";
			$fields['required']=databaseDataType('tinyint')." NULL";
			$fields['tablename']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['tvals']="text NULL";
			$fields['width']=databaseDataType('integer')." NULL";
			$fields['related_table']=databaseDataType('varchar(100)')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename,fieldname",'-unique'=>true));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_files':
			$fields['file']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['file_size']=databaseDataType('integer')." NULL";
			$fields['file_width']=databaseDataType('integer')." NULL";
			$fields['file_height']=databaseDataType('integer')." NULL";
			$fields['file_type']=databaseDataType('varchar(150)')." NULL";
			$fields['tablename_id']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['tablename']=databaseDataType('varchar(100)')." NULL";
			$fields['category']=databaseDataType('varchar(100)')." NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"file"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_html_entities':
			$fields['entity_name']=databaseDataType('varchar(125)')." NULL";
			$fields['entity_number']=databaseDataType('varchar(15)')." NOT NULL UNIQUE";
			$fields['category']=databaseDataType('varchar(100)')." NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_posteditlog':
			$fields['postedittables']=databaseDataType('varchar(255)')." NOT NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){ return printValue($ok);break;}

			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
		break;
		case '_prompts':
			$fields['name']=databaseDataType('varchar(150)')." NOT NULL";
			$fields['body']=databaseDataType('mediumtext')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser,name",'-unique'=>1));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "name\r\nbody",
				'listfields'	=> "_cuser\r\nname",
				'sortfields'	=> "_cuser, name",
				'-upsert'		=> 'formfields,listfields,sortfields'
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			return 1;
		break;
		case '_tiny':
			$fields['url']="varchar(2100) NOT NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_markers':
			$fields['problem']=databaseDataType('varchar(500)')." NULL";
			$fields['solution']=databaseDataType('varchar(1000)')." NULL";
			$fields['mousex']=databaseDataType('integer')." NOT NULL Default 0 COMMENT 'Horizontal Position'";
			$fields['mousey']=databaseDataType('integer')." NOT NULL Default 0 COMMENT 'Vertical Position'";
			$fields['page_id']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['priority']=databaseDataType('integer')." NOT NULL Default 2 COMMENT 'High(1),Med(2),Low(3)'";
			$fields['status']=databaseDataType('integer')." NOT NULL Default 1 COMMENT 'New(1),Fixed(2),Nope(3)'";;
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"status,page_id"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_forms':
			$fields['email']=databaseDataType('varchar(255)')." NULL";
			$fields['_formname']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['http_host']=databaseDataType('varchar(255)')." NULL";
			$fields['script_url']=databaseDataType('varchar(255)')." NULL";
			$fields['_xmldata']="text NOT NULL";
			$ok=createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_formname"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_history':
			$fields['action']=databaseDataType('varchar(5)')." NULL";
			$fields['page_id']=databaseDataType('integer')." NULL";
			$fields['tablename']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['record_id']=databaseDataType('integer')." NOT NULL";
			$fields['xmldata']="text NULL";
			$fields['md5']=databaseDataType('varchar(32)')." NOT NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_minify':
			$fields['_cuser']=databaseDataType('integer')." NOT NULL Default 0";
			$fields['name']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['version']=databaseDataType('integer')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_triggers':
			$fields['name']=databaseDataType('varchar(100)')." NOT NULL UNIQUE";
			$fields['mtype']=databaseDataType('integer(1)')." NOT NULL Default 1";
			$fields['active']=databaseDataType('tinyint(1)')." NOT NULL Default 1";
			$fields['functions']=databaseDataType('mediumtext')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexs
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_pages':
			/*other possible fields
				sync tinyint(1) NOT NULL Default 1 - if not checked then do not sync
				postedit tinyint(1) NOT NULL Default 1 - if not checked then do not show in postedit
				user_content text NULL - for user driven content without code
			*/
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_aip']=databaseDataType('varchar(45)')." NULL";
			$fields['_auser']=databaseDataType('integer')." NULL";
			$fields['_counter']=databaseDataType('integer')." NULL";
			$fields['_amem']=databaseDataType('bigint')." NULL";
			$fields['_env']="text NULL";
			$fields['_template']=databaseDataType('integer')." NOT NULL Default 1";
			$fields['_cache']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['body']=databaseDataType('mediumtext')." NULL";
			$fields['controller']=databaseDataType('mediumtext')." NULL";
			$fields['css']=databaseDataType('mediumtext')." NULL";
			$fields['css_min']=databaseDataType('mediumtext')." NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$fields['functions']=databaseDataType('mediumtext')." NULL";
			$fields['js']=databaseDataType('mediumtext')." NULL";
			$fields['js_min']=databaseDataType('mediumtext')." NULL";
			$fields['menu']=databaseDataType('tinyint')." NULL";
			$fields['meta_description']=databaseDataType('varchar(255)')." NULL";
			$fields['name']=databaseDataType('varchar(50)')." NOT NULL";
			$fields['page_type']=databaseDataType('smallint')." NOT NULL Default 0";
			$fields['parent']=databaseDataType('varchar(50)')." NULL";
			$fields['permalink']=databaseDataType('varchar(255)')." NULL";
			$fields['postedit']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['settings']="text NULL";
			$fields['sort_order']=databaseDataType('smallint')." NOT NULL Default 0";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['title']=databaseDataType('varchar(255)')." NULL";
			$fields['user_content']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"permalink"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"page_type"));
			//insert default files for this table from the schema directory
			schemaAddFileData($table);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_queries':
			$fields['run_length']=databaseDataType('float(8,3)')." NOT NULL Default 0.000";
			$fields['query']="text NULL";
			$fields['page_id']=databaseDataType('integer')." NULL";
			$fields['row_count']=databaseDataType('integer')." NULL";
			$fields['field_count']=databaseDataType('integer')." NULL";
			$fields['function_name']=databaseDataType('varchar(25)')." NULL";
			$fields['fields']=databaseDataType('varchar(255)')." NULL";
			$fields['tablename']=databaseDataType('varchar(255)')." NULL";
			$fields['user_id']=databaseDataType('integer')." NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"tablename"));
			//Add tabledata
			$addopts=array('-table'=>"_tabledata",
				'tablename'		=> $table,
				'formfields'	=> "function_name run_length user_id page_id row_count field_count\r\nfields\r\nquery",
				'listfields'	=> "_cdate function_name run_length user_id tablename page_id row_count field_count",
				'sortfields'	=> 'run_length desc',
				'formfields_mod'=> "function_name run_length user_id page_id row_count field_count\r\nfields\r\nquery",
				'listfields_mod'=> "_cdate function_name run_length user_id tablename page_id row_count field_count",
				'sortfields_mod'	=> 'run_length desc'
				);
			$id=addDBRecord($addopts);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_wpass':
			$fields['title']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['category']=databaseDataType('varchar(50)')." NOT NULL";
			$fields['user']=databaseDataType('varchar(60)')." NOT NULL";
			$fields['pass']=databaseDataType('varchar(40)')." NOT NULL";
			$fields['url']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['notes']=databaseDataType('mediumtext')." NULL";
			$fields['users']=databaseDataType('varchar(1000)')." NULL";
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
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_sessions':
			$fields['session_id']=databaseDataType('varchar(40)')." NOT NULL UNIQUE";
			$fields['session_data']=databaseDataType('mediumtext')." NULL";
			$fields['touchtime']=databaseDataType('int')." NOT NULL Default 0";
			$fields['json']=databaseDataType('tinyint(1)')." NOT NULL Default 0";
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
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_tabledata':
			$fields['formfields']="text NULL";
			$fields['formfields_mod']="text NULL";
			$fields['listfields']="text NULL";
			$fields['listfields_mod']="text NULL";
			$fields['sortfields']=databaseDataType('varchar(255)')." NULL";
			$fields['sortfields_mod']=databaseDataType('varchar(255)')." NULL";
			$fields['tablename']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['tablegroup']=databaseDataType('varchar(255)')." NULL";
			$fields['synchronize']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['websockets']=databaseDataType('tinyint')." NOT NULL Default 0";
			$fields['tabledesc']=databaseDataType('varchar(500)')." NULL";
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
				'formfields'	=> "utype active\r\nusername password\r\ntitle department\r\nfirstname lastname\r\naddress1\r\naddress2\r\ncountry email\r\ncity state zip\r\npicture\r\nbio\r\nnote",
				'listfields'	=> 'active utype username firstname lastname email _adate _aip',
				'sortfields'	=> '_adate desc',
				'formfields_mod'=> "username password\r\ntitle department\r\nfirstname lastname\r\naddress1\r\naddress2\r\ncountry email\r\ncity state zip\r\npicture\r\nbio",
				'listfields_mod'=> 'username firstname lastname email',
				'sortfields_mod'=> 'lastname, firstname, username'
				));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_templates':
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_aip']=databaseDataType('varchar(45)')." NULL";
			$fields['_auser']=databaseDataType('integer')." NULL";
			$fields['body']=databaseDataType('mediumtext')." NULL";
			$fields['functions']=databaseDataType('mediumtext')." NULL";
			$fields['js']="text NULL";
			$fields['css']="text NULL";
			$fields['js_min']="text NULL";
			$fields['css_min']="text NULL";
			$fields['description']=databaseDataType('varchar(255)')." NULL";
			$fields['name']=databaseDataType('varchar(50)')." NOT NULL";
			$fields['postedit']=databaseDataType('tinyint(1)')." NOT NULL Default 1";
			$fields['synchronize']=databaseDataType('tinyint(1)')." NOT NULL Default 1";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			//insert default files for this table from the schema directory
			schemaAddFileData($table);
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_settings':
			$fields['key_name']=databaseDataType('varchar(25)')." NOT NULL";
			$fields['key_value']=databaseDataType('varchar(5000)')." NULL";
			$fields['user_id']=databaseDataType('integer')." NOT NULL Default 0";
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
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_synchronize':
			//sync_action,diff_table,diff_id,sync_items
			$fields['tablename']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['ids']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['notes']="text NULL";
			$fields['target']=databaseDataType('varchar(100)')." NULL";
			$fields['results']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
			break;
		case '_users':
			$fields['_adate']=databaseDataType('datetime')." NULL";
			$fields['_apage']=databaseDataType('integer')." NULL";
			$fields['_aip']=databaseDataType('varchar(45)')." NULL";
			$fields['_env']="text NULL";
			$fields['_sid']=databaseDataType('varchar(150)')." NULL";
			$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
			$fields['address1']=databaseDataType('varchar(255)')." NULL";
			$fields['address2']=databaseDataType('varchar(255)')." NULL";
			$fields['city']=databaseDataType('varchar(50)')." NULL";
			$fields['country']=databaseDataType('varchar(2)')." NOT NULL Default 'US'";
			$fields['email']=databaseDataType('varchar(255)')." NULL";
			//$fields['guid']=databaseDataType('varchar(40)')." NULL";
			$fields['department']=databaseDataType('varchar(60)')." NULL";
			$fields['hint']=databaseDataType('varchar(255)')." NULL";
			$fields['firstname']=databaseDataType('varchar(100)')." NULL";
			$fields['lastname']=databaseDataType('varchar(150)')." NULL";
			$fields['title']=databaseDataType('varchar(255)')." NULL";
			$fields['note']=databaseDataType('varchar(255)')." NULL";
			$fields['password']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['phone']=databaseDataType('varchar(25)')." NULL";
			$fields['state']=databaseDataType('varchar(5)')." NULL";
			$fields['username']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['utype']=databaseDataType('smallint')." NOT NULL Default 1";
			$fields['zip']=databaseDataType('varchar(10)')." NULL";
			$fields['picture']=databaseDataType('varchar(255)')." NULL";
			$fields['bio']="text NULL";
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){
				break;
			}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"username",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"active"));
			//$ok=addDBIndex(array('-table'=>$table,'-fields'=>"guid"));
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
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
			return 1;
		break;
		case '_docs':
			$fields['afile']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['afile_line']=databaseDataType('int').' NOT NULL';
			$fields['category']=databaseDataType('varchar(150)');
			$fields['name']=databaseDataType('varchar(200)');
			$fields['caller']=databaseDataType('varchar(255)');
			$fields['hash']=databaseDataType('varchar(200)').' NOT NULL';
			$fields['comments']=databaseDataType('text');
			$fields['info']=databaseDataType('json');

			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){
				echo printValue($ok);exit;
				break;
			}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"hash",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"category"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"afile"));
			addMetaData($table);
            return 1;
		break;
		case '_docs_files':
			$fields['afile']=databaseDataType('varchar(255)')." NOT NULL";
			$fields['afile_md5']=databaseDataType('varchar(200)').' NOT NULL';
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){
				echo printValue($ok);exit;
				break;
			}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"afile",'-unique'=>true));
            return 1;
		break;
		case 'cities':
			$fields['name']=databaseDataType('varchar(50)')." NOT NULL";
			$fields['country']=databaseDataType('varchar(3)');
			$fields['state']=databaseDataType('varchar(7)');
			$fields['longitude']=databaseDataType('decimal(12,8)');
			$fields['latitude']=databaseDataType('decimal(12,8)');
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,country,state",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
            return 1;
		break;
		case 'colors':
			$fields['code']=databaseDataType('varchar(50)')." NOT NULL";
			$fields['name']=databaseDataType('varchar(100)');
			$fields['hex']=databaseDataType('varchar(7)');
			$fields['red']=databaseDataType('int');
			$fields['green']=databaseDataType('int');
			$fields['blue']=databaseDataType('int');
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"code",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
            return 1;
		break;
		case 'states':
			$fields['code']=databaseDataType('varchar(7)')." NOT NULL";
			$fields['name']=databaseDataType('varchar(100)')." NOT NULL";
			$fields['country']=databaseDataType('varchar(3)')." NOT NULL Default 'US'";
			$fields['longitude']=databaseDataType('decimal(12,8)');
			$fields['latitude']=databaseDataType('decimal(12,8)');
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,country",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"code"));
            addMetaData($table);
            //populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
            return 1;
		break;
		case 'countries':	
			$fields['code']=databaseDataType('varchar(2)')." NOT NULL";
			$fields['code3']=databaseDataType('varchar(3)')." NULL";
			$fields['name']=databaseDataType('varchar(200)')." NOT NULL";
			$fields['capital']=databaseDataType('varchar(255)')." NULL";
			$fields['currency']=databaseDataType('varchar(25)')." NULL";
			$fields['currency_symbol']=databaseDataType('varchar(25)')." NULL";
			$fields['phone_code']=databaseDataType('varchar(5)')." NULL";
			$fields['region']=databaseDataType('varchar(255)')." NULL";
			$fields['subregion']=databaseDataType('varchar(255)')." NULL";
			$fields['longitude']=databaseDataType('decimal(12,8)');
			$fields['latitude']=databaseDataType('decimal(12,8)');
			$ok = createDBTable($table,$fields,'InnoDB');
			if($ok != 1){break;}
			//indexes
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"code"));
			$ok=addDBIndex(array('-table'=>$table,'-fields'=>"geonameid"));
			addMetaData($table);
			//populate the table 
			$progpath=dirname(__FILE__);
			if(is_file("{$progpath}/schema/{$table}.csv")){
				$ok=dbAddRecords($CONFIG['database'],$table,array('-csv'=>"{$progpath}/schema/{$table}.csv",'-ignore'=>1));
			}
            return 1;
			break;
		default:
			return "createWasqlTable Error: {$table} is not a valid WaSQL table";
			break;
    	}
	return 0;
	}
//---------- begin function schemaImportCSV
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaImportCSV($table,$file){
	$progpath=dirname(__FILE__);
	$csv=getCSVFileContents("{$progpath}/schema/{$file}");
	foreach($csv['items'] as $item){
        $item['-table']=$table;
        $id=addDBRecord($item);
        if(!isNum($id)){abort(printValue($id).printValue($item));}
	}
}
//---------- begin function schemaUpdateCountries
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaUpdateCountries(){
	$url='http://www.geonames.org/countryInfoJSON';
	$post=postURL($url,array('-method'=>'GET','-ssl'=>true));
	$countries=json_decode($post['body'], true);
	//echo "schemaUpdateCountries".printValue($countries);exit;
	if(!isset($countries['geonames'][0])){
		echo "schemaUpdateCountries".printValue($countries);exit;
		return false;
	}
	//echo "START LOOP".printValue($countries['geonames'][0]);
	foreach($countries['geonames'] as $country){
    	$country=array_change_key_case($country,CASE_LOWER);
    	$country['code']=$country['countrycode'];
    	$country['code3']=$country['isoalpha3'];
    	$country['name']=$country['countryname'];
    	$country['code']=$country['countrycode'];
    	$country['north']=$country['north'];
    	//if(isPostgreSQL()){$country['north'].='::decimal';}
    	$country['south']=$country['south'];
    	//if(isPostgreSQL()){$country['south'].='::decimal';}
    	$country['east']=$country['east'];
    	//if(isPostgreSQL()){$country['east'].='::decimal';}
    	$country['west']=$country['west'];
    	//if(isPostgreSQL()){$country['west'].='::decimal';}
    	//echo "IN LOOP".printValue($country);exit;
    	$crec=getDBRecord(array(
			'-table'=>'countries',
			'geonameid'	=> $country['geonameid'],
			'-fields'	=> '_id'
		));
		//echo "HERE".printValue($crec).printValue($country);exit;
		$country['-table']='countries';
		if(isset($crec['_id'])){
        	$country['-where']="_id={$crec['_id']}";
        	$ok=editDBRecord($country);
		}
		else{
			//echo "Adding";
			//echo $country['south'];exit;
        	$id=addDBRecord($country);
        	//echo "ADD".$id.printValue($country);exit;
		}
	}
	//echo "END LOOP";exit;
	//determine population_rank based on population
	$recs=getDBRecords(array(
		'-table'	=> 'countries',
		'-order'	=> 'population desc',
		'-fields'	=> '_id'
	));
	foreach($recs as $i=>$rec){
		$rank=$i+1;
    	$ok=executeSQL("update countries set population_rank={$rank} where _id={$rec['_id']}");
	}
	return true;
}
//---------- begin function schemaUpdateStates
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
	global $CONFIG;
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
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'synchronize',
				'description'	=> 'if not checked then do not synchronize with live db',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1',
				'-upsert'		=> 'tvals,inputtype,description',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'postedit',
				'description'	=> 'if not checked then do not show in postedit',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1',
				'-upsert'		=> 'tvals,inputtype,description',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'behavior',
				'inputtype'		=> 'multiselect',
				'tvals'			=> '<?='.'wasqlGetBehaviors();'.'?>',
				'dvals'			=> '<?='.'wasqlGetBehaviors(1);'.'?>',
				'width'			=> 100,
				'-upsert'		=> 'tvals,dvals,inputtype,width',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'mask',
				'inputtype'		=> 'select',
				'tvals'			=> '<?='.'wasqlGetMasks();'.'?>',
				'dvals'			=> '<?='.'wasqlGetMasks(1);'.'?>',
				'-upsert'		=> 'tvals,dvals,inputtype',
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
				'-upsert'		=> 'tvals,dvals,inputtype,required,onchange,displayname',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'defaultval',
				'displayname'	=> 'Default Value',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 40,
				'inputmax'		=> 255,
				'-upsert'		=> 'displayname,inputtype,width,height,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'help',
				'displayname'	=> 'Help (shown onmouseover field name)',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 40,
				'inputmax'		=> 255,
				'-upsert'		=> 'displayname,inputtype,width,height,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'dvals',
				'displayname'	=> 'Display Values',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'sqleditor',
				'width'			=> 400,
				'height'		=> 60,
				'-upsert'		=> 'displayname,inputtype,width,height,behavior',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'tvals',
				'displayname'	=> 'True Values',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'sqleditor',
				'width'			=> 400,
				'height'		=> 60,
				'-upsert'		=> 'displayname,inputtype,width,height,behavior',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'editlist',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1',
				'-upsert'		=> 'tvals,inputtype',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'required',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1',
				'-upsert'		=> 'tvals,inputtype',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'width',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 5,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'height',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 5,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'inputmax',
				'inputtype'		=> 'text',
				'width'			=> '50',
				'inputmax'		=> 5,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'fieldname',
				'inputtype'		=> 'text',
				'width'			=> '130',
				'inputmax'		=> 100,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'onchange',
				'displayname'	=> 'onchange Event',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'-upsert'		=> 'displayname,inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> '320',
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'tablename',
				'inputtype'		=> 'select',
				'width'			=> 135,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,tvals,dvals,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'related_table',
				'displayname'	=> 'Related Table',
				'width'			=> 135,
				'inputtype'		=> 'select',
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'-upsert'		=> 'displayname,inputtype,width,tvals,dvals',
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
				'required'		=> 1,
				'-upsert'		=> 'inputtype,displayname,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'controller',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'		=> '200',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'functions',
				'displayname'	=> 'Functions',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'		=> '200',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'css',
				'displayname'	=> 'CSS / Stylesheet',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'csseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'js',
				'displayname'	=> 'Javascript',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'jseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '300',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'permalink',
				'inputtype'		=> 'text',
				'width'			=> '700',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'sort_order',
				'displayname'	=> 'SortOrder',
				'inputtype'		=> 'text',
				'width'			=> '25',
				'editlist'		=> 1,
				'mask'			=> "integer",
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,width,editlist,mask,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> 700,
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'meta_description',
				'inputtype'		=> 'text',
				'width'			=> 700,
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,width,inputmax',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'user_content','displayname'=>'User Content',
				'description'	=> 'A place for user driven content without logic',
				'inputtype'		=> 'textarea',
				'width'			=> '700',
				'height'		=> '120',
				'-upsert'		=> 'inputtype,description,width,height',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> '_template',
				'displayname'	=> 'Template',
				'inputtype'		=> 'select',
				'tvals'			=> 'select _id from _templates order by name,_id',
				'dvals'			=> 'select name from _templates order by name,_id',
				'required'		=> 1,
				'defaultval'	=> 2,
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required,defaultval',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'page_type',
				'displayname'	=> 'PageType',
				'inputtype'		=> 'select',
				'tvals'			=> "1\r\n2\r\n3\r\n4",
				'dvals'			=> "WebPage\r\nBlog\r\nForum\r\nController",
				'required'		=> 1,
				'defaultval'	=> 1,
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required,defaultval',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_pages',
				'fieldname'		=> 'parent',
				'inputtype'		=> 'select',
				'tvals'			=> "select name from _pages order by name",
				'dvals'			=> "select name from _pages order by name",
				'-upsert'		=> 'inputtype,tvals,dvals',
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,tvals,required,synchronize,postedit',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_tabledata',
				'fieldname'		=> 'tablegroup',
				'inputtype'		=> 'text',
				'width'			=> '145',
				'inputmax'		=> '50',
				'required'		=> 0,
				'synchronize'	=> 1,
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required,synchronize,postedit',
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit',
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'postedit'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,synchronize,required,postedit'
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
				'-upsert'		=> 'inputtype,behavior,width,height,required',
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'functions',
				'displayname'	=> 'Controller - functions',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'phpeditor',
				'width'			=> '700',
				'height'		=> '200',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'css',
				'displayname'	=> 'CSS / Stylesheet',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'csseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'js',
				'displayname'	=> 'Javascript',
				'inputtype'		=> 'textarea',
				'behavior'		=> 'jseditor',
				'width'			=> '700',
				'height'		=> '120',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,behavior,width,height,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '700',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_templates',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> '700',
				'-upsert'		=> 'inputtype,width'
				));
			break;
		//_markers
		case '_markers':
			//_fielddata for _markers
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_markers',
				'fieldname'		=> 'priority',
				'inputtype'		=> 'select',
				'required'		=> 1,
				'displayname'	=> "Importance",
				'tvals'			=> "1\r\n2\r\n3",
				'dvals'			=> "High\r\nMed\r\nLow",
				'defaultval'	=> 2,
				'-upsert'		=> 'inputtype,displayname,dvals,tvals,defaultval'
			));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_markers',
				'fieldname'		=> 'status',
				'inputtype'		=> 'select',
				'required'		=> 1,
				'displayname'	=> "Status",
				'tvals'			=> "1\r\n2\r\n3",
				'dvals'			=> "New\r\nFixed\r\nNope",
				'defaultval'	=> 1,
				'-upsert'		=> 'inputtype,displayname,dvals,tvals,defaultval'
			));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_markers',
				'fieldname'		=> 'problem',
				'inputtype'		=> 'textarea',
				'displayname'	=> 'Explain what needs fixed',
				'width'			=> 300,
				'height'		=> 60,
				'inputmax'		=> 500,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,width,height,inputmax,required'
			));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_markers',
				'fieldname'		=> 'solution',
				'inputtype'		=> 'textarea',
				'displayname'	=> 'Explain How you fixed it',
				'width'			=> 300,
				'height'		=> 60,
				'inputmax'		=> 1000,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,width,height,inputmax,required'
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
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,inputmax,tvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 100,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'user',
				'displayname'	=> 'Username **',
				'inputtype'		=> 'text',
				'width'			=> '225',
				'inputmax'		=> 60,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'pass',
				'displayname'	=> 'Password **',
				'inputtype'		=> 'text',
				'width'			=> '150',
				'inputmax'		=> 40,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'url',
				'displayname'	=> 'URL **',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'notes',
				'displayname'	=> 'Notes **',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_wpass',
				'fieldname'		=> 'users',
				'displayname'	=> 'Share With',
				'inputtype'		=> 'checkbox',
				'width'			=> 3,
				'tvals'			=> 'select _id from _users where _id !=<?=userValue(\'_id\');?> and active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'dvals'			=> 'select firstname,lastname from _users where _id !=<?=userValue(\'_id\');?> and active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,width,dvals,tvals,required'
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
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'password',
				'inputtype'		=> 'password',
				'width'			=> '100',
				'inputmax'		=> 25,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'title',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'department',
				'inputtype'		=> 'text',
				'width'			=> '180',
				'inputmax'		=> 60,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
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
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,defaultval'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'tvals'			=> "1",
				'defaultval'	=> 1,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,tvals,defaultval'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'address1',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'address2',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'firstname',
				'inputtype'		=> 'text',
				'width'			=> '180',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'lastname',
				'inputtype'		=> 'text',
				'width'			=> '180',
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'zip',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'inputmax'		=> 10,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'phone',
				'inputtype'		=> 'text',
				'width'			=> '100',
				'inputmax'		=> 25,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'email',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'mask'			=> 'email',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,mask,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'city',
				'inputtype'		=> 'text',
				'width'			=> '170',
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,width,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'state',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'width'			=> 150,
				'displayname'	=> "State",
				'tvals'			=> '<?='.'wasqlGetStates();'.'?>',
				'dvals'			=> '<?='.'wasqlGetStates(1);'.'?>',
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required,width'
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
				'dvals'			=> '<?='.'wasqlGetCountries(1);'.'?>',
				'-upsert'		=> 'inputtype,displayname,defaultval,onchange,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'bio',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 75,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'picture',
				'inputtype'		=> 'file',
				'width'			=> 400,
				'defaultval'	=> '/files/users',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,defaultval,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_users',
				'fieldname'		=> 'note',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 50,
				'inputmax'		=> 255,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,inputmax,required'
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
				'-upsert'		=> 'inputtype,width,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'email',
				'inputtype'		=> 'text',
				'width'			=> 300,
				'inputmax'		=> 255,
				'mask'			=> 'email',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,mask,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'subject',
				'inputtype'		=> 'text',
				'width'			=> 300,
				'inputmax'		=> 150,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'contact_form',
				'fieldname'		=> 'message',
				'inputtype'		=> 'textarea',
				'width'			=> 300,
				'height'		=> 150,
				'-upsert'		=> 'inputtype,width,height'
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
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,defaultval,required'
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
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> 'states',
				'fieldname'		=> 'country',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'defaultval'	=> 'US',
				'displayname'	=> "Country",
				'tvals'			=> '<?='.'wasqlGetCountries();'.'?>',
				'dvals'			=> '<?='.'wasqlGetCountries(1);'.'?>',
				'-upsert'		=> 'inputtype,displayname,defaultval,tvals,dvals,required'
				));
			break;
		//_config
		case '_config':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_config',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 50,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_config',
				'fieldname'		=> 'current_value',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 500,
				'-upsert'		=> 'inputtype,width,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_config',
				'fieldname'		=> 'default_value',
				'inputtype'		=> 'text',
				'width'			=> '200',
				'inputmax'		=> 500,
				'-upsert'		=> 'inputtype,width,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_config',
				'fieldname'		=> 'description',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '100',
				'-upsert'		=> 'inputtype,width,height'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_config',
				'fieldname'		=> 'possible_values',
				'displayname'	=> 'Possible Values (0=Off,1=On)',
				'inputtype'		=> 'text',
				'width'			=> '600',
				'inputmax'		=> 1000,
				'-upsert'		=> 'inputtype,width,inputmax,displayname'
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
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,inputmax,mask,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_forms',
				'fieldname'		=> '_formname',
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_forms',
				'fieldname'		=> '_xmldata',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 300,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			break;
		//_forms
		case '_reports':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'-upsert'		=> 'inputtype,width,inputmax',
				'tablename'		=> '_reports',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'width'			=> '400',
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,width,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'-upsert'		=> 'inputtype,tvals,dvals,inputmax',
				'tablename'		=> '_reports',
				'fieldname'		=> 'dbname',
				'inputtype'		=> 'select',
				'tvals'			=> '<?='.'wasqlGetDatabases();'.'?>',
				'dvals'			=> '<?='.'wasqlGetDatabases(1);'.'?>',
				'inputmax'		=> 255,
				'-upsert'		=> 'inputtype,tvals,dvals,inputmax'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'-upsert'		=> 'displayname,inputtype,width,tvals,dvals,required',
				'tablename'		=> '_reports',
				'fieldname'		=> 'users',
				'displayname'	=> 'Limit Access To These Users',
				'inputtype'		=> 'checkbox',
				'width'			=> 5,
				'tvals'			=> 'select _id from _users where active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'dvals'			=> 'select firstname,lastname from _users where active=1 and concat(firstname,lastname) != \'\' order by firstname,lastname,_id',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required,width'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'-upsert'		=> 'displayname,inputtype,width,tvals,required',
				'tablename'		=> '_reports',
				'fieldname'		=> 'departments',
				'displayname'	=> 'Limit Access To These Departments',
				'inputtype'		=> 'checkbox',
				'width'			=> 5,
				'tvals'			=> 'select distinct(department) from _users where active=1 and department is not null order by department',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,tvals,required,width'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'query',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '150',
				'behavior'		=> 'sqleditor',
				'-upsert'		=> 'inputtype,width,height,behavior'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> '325',
				'inputmax'		=> 100,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'editlist'		=> 1,
				'defaultval'	=> 1,
				'tvals'			=> 1,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,editlist,defaultval,tvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'menu',
				'inputtype'		=> 'text',
				'tvals'			=> 'select distinct(menu) from _reports order by menu',
				'width'			=> 200,
				'height'		=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,tvals,width,height,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_reports',
				'fieldname'		=> 'options',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '150',
				'displayname'	=> 'Options - one per line.  key=value, or name:key=value.  -- lines are ignored',
				'defaultval'	=> "autorun=false\r\ntotal_columns=none\r\n--Color:type=number\r\n--Color:default=2\r\n--Color:values=1:Green,2:Red,3:Blue",
				'-upsert'		=> 'inputtype,displayname,defaultval,width,height'
				));
			break;
		//_forms
		case '_synchronize':
			$id=addDBRecord(array('-table'=>"_tabledata",
				'tablename'		=> '_synchronize',
				'formfields'	=> "tablename\r\ntarget\r\nids\r\nnotes\r\nresults",
				'listfields'	=> "_cdate\r\n_cuser\r\ntarget\r\ntablename\r\nids",
				'sortfields'	=> '_cdate desc',
				'-upsert'		=> 'formfields,listfields,sortfields'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'tablename',
				'inputtype'		=> 'text',
				'width'			=> '300',
				'inputmax'		=> 100,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'target',
				'inputtype'		=> 'text',
				'width'			=> '300',
				'inputmax'		=> 100,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,inputmax,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'ids',
				'inputtype'		=> 'textarea',
				'width'			=> '500',
				'height'		=> '40',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'notes',
				'inputtype'		=> 'textarea',
				'width'			=> '500',
				'height'		=> '100',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_synchronize',
				'fieldname'		=> 'results',
				'inputtype'		=> 'textarea',
				'width'			=> '500',
				'height'		=> '100',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			break;
		//_history
		case '_history';
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'record_id',
				'inputtype'		=> 'text',
				'width'			=> 40,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'action',
				'inputtype'		=> 'text',
				'width'			=> 60,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'md5',
				'inputtype'		=> 'text',
				'width'			=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'page_id',
				'inputtype'		=> 'text',
				'width'			=> 40,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_history',
				'fieldname'		=> 'xmldata',
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 400,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
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
				'-upsert'		=> 'inputtype,width,height,required'
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
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,tvals,editlist,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_log',
				'inputtype'		=> 'checkbox',
				'tvals'			=> 1,
				'editlist'		=> 1,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,tvals,editlist,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_now',
				'inputtype'		=> 'checkbox',
				'synchronize'	=> 0,
				'defaultval'	=> 0,
				'tvals'			=> '1',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,tvals,required,defaultval'
			));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'stop_now',
				'inputtype'		=> 'checkbox',
				'synchronize'	=> 0,
				'defaultval'	=> 0,
				'tvals'			=> '1',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,tvals,required,defaultval'
			));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_as',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "Run As",
				'tvals'			=> "SELECT _id FROM _users WHERE active=1 order by firstname,lastname,_id",
				'dvals'			=> "SELECT firstname,lastname FROM _users WHERE active=1 ORDER BY firstname,lastname,_id",
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_length',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 100,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'records_to_keep',
				'inputtype'		=> 'text',
				'width'			=> 100,
				'defaultval'	=> 100,
				'mask'			=> 'integer',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,mask,required,defaultval',
			));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'cron_pid',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 60,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required,synchronize'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'active',
				'synchronize'	=> 0,
				'defaultval'	=> 1,
				'inputtype'		=> 'checkbox',
				'tvals'			=> 1,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required,synchronize,defaultval'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'frequency',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "Frequency",
				'tvals'			=> "1\r\n5\r\n10\r\n15\r\n30\r\n60\r\n1440\r\n720\r\n10080\r\n43829",
				'dvals'			=> "Every Minute\r\nEvery 5 minutes\r\nEvery 10 Minutes\r\nEvery 15 Minutes\r\nEvery 30 Minutes\r\nEvery Hour\r\nOnce Every  Day\r\nTwice Every Day\r\nOnce a Week\r\nOnce a Month",
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_date',
				'synchronize'	=> 0,
				'inputtype'		=> 'text',
				'width'			=> 250,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required,synchronize'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_result',
				'synchronize'	=> 0,
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,synchronize,width,height,required'
				));

			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_values',
				'inputtype'		=> 'text',
				'width'			=> 240,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'logfile',
				'inputtype'		=> 'text',
				'maxlength'		=> 255,
				'width'			=> 350,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,maxlength,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'logfile_maxsize',
				'displayname'	=> 'Logfile Maxsize (bytes)',
				'mask'			=> 'integer',
				'inputtype'		=> 'text',
				'width'			=> 130,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,displayname,mask,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_format',
				'inputtype'		=> 'frequency',
				'width'			=> 800,
				'height'		=> 40,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'frequency_max',
				'inputtype'		=> 'select',
				'displayname'	=> "Frequency Max",
				'tvals'			=> "minute\r\nhourly\r\ndaily\r\nweekly\r\nmonthly\r\nquarterly\r\nyearly",
				'dvals'			=> "Once per Minute\r\nOnce Per Hour\r\nOnce Per Day\r\nOnce Per Week\r\nOnce Per Month\r\nOnce Per Quarter\r\nOnce Per Year",
				'-upsert'		=> 'tvals,dvals,inputtype,displayname',
			));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_cmd',
				'inputtype'		=> 'text',
				'width'			=> 500,
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,required'
				));
			break;
		case '_files':
			$id=addDBRecord(array('-table'=>"_tabledata",
				'tablename'		=> '_files',
				'formfields'	=> "file\r\ntablename tablename_id\r\ncategory\r\ndescription",
				'listfields'	=> "_cdate\r\n_edate\r\nfile\r\nfile_size\r\nfile_type\r\ntablename\r\ntablename_id",
				'-upsert'		=> 'formfields,listfields'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'file',
				'inputtype'		=> 'file',
				'width'			=> 400,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'category',
				'inputtype'		=> 'text',
				'width'			=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'description',
				'inputtype'		=> 'textarea',
				'width'			=> 400,
				'height'		=> 70,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_files',
				'fieldname'		=> 'tablename',
				'inputtype'		=> 'select',
				'width'			=> 150,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_files',
				'fieldname'		=> 'tablename_id',
				'mask'			=> 'integer',
				'inputtype'		=> 'text',
				'width'			=> 30,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,mask,required'
				));
			break;
		case '_prompts':
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_prompts',
				'fieldname'		=> 'name',
				'inputtype'		=> 'text',
				'width'			=> 600,
				'-upsert'		=> 'inputtype,width,height'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_prompts',
				'fieldname'		=> 'body',
				'inputtype'		=> 'textarea',
				'width'			=> 600,
				'height'		=> 250,
				'-upsert'		=> 'inputtype,width,height'
				));
		break;
		case '_triggers':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_triggers',
				'fieldname'		=> 'name',
				'inputtype'		=> 'select',
				'width'			=> 150,
				'tvals'			=> '&getDBTables',
				'dvals'			=> '&getDBTables',
				'required'		=> 1,
				'-upsert'		=> 'inputtype,width,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_triggers',
				'fieldname'		=> 'mtype',
				'displayname'	=> 'Type',
				'inputtype'		=> 'select',
				'tvals'			=> "0\r\n1",
				'dvals'			=> "Class\r\nFunctions",
				'onchange'		=> "changeTriggerType(this)",
				'required'		=> 1,
				'defaultval'	=> 1,
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,onchange,defaultval,required'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_triggers',
				'fieldname'		=> 'functions',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '275',
				'-upsert'		=> 'inputtype,width,height'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_triggers',
				'fieldname'		=> 'active',
				'inputtype'		=> 'checkbox',
				'defaultval'	=> 1,
				'tvals'			=> 1,
				'-upsert'		=> 'inputtype,tvals,defaultval'
				));
			$id=addDBRecord(array('-table'=>'_tabledata',
				'tablename'		=> '_triggers',
				'formfields'	=> "name mtype active\r\nfunctions",
				'listfields'	=> 'name mtype active',
				'sortfields'	=> 'name',
				'synchronize'	=> 1,
				'-upsert'		=> 'formfields,listfields,sortfields,synchronize'
				));
			break;
		case '_sessions':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_sessions',
				'fieldname'		=> 'session_data',
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '275',
				'-upsert'		=> 'inputtype,width,height'
				));
		break;
		case '_queries':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'run_length',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'function',
				'inputtype'		=> 'text',
				'width'			=> '100',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'page_id',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'fields',
				'inputtype'		=> 'text',
				'width'			=> '500',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'row_count',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'field_count',
				'inputtype'		=> 'text',
				'width'			=> '60',
				'-upsert'		=> 'inputtype,width'
				));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_queries',
				'fieldname'		=> 'user_id',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "User",
				'tvals'			=> "select _id from _users order by firstname,lastname,_id",
				'dvals'			=> "select firstname,lastname from _users order by firstname,lastname,_id",
				'-upsert'		=> 'inputtype,displayname,tvals,dvals,required'
				));
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_queries',
				'fieldname'		=> 'query',
				'inputtype'		=> 'textarea',
				'width'			=> 500,
				'height'		=> 200,
				'required'		=> 0,
				'-upsert'		=> 'inputtype,width,height,required'
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
		'_fielddata','_tabledata','_errors',
		'_access','_access_summary','_history','_changelog','_cron','_cronlog','_pages','_queries',
		'_templates','_settings','_synchronize','_users','_forms','_files','_minify',
		'_reports','_triggers','_sessions','_html_entities','_posteditlog','_config','_prompts'
		);
	//include wpass table?
	//if(isset($CONFIG['wpass']) && $CONFIG['wpass']){$tables[]='_wpass';}
	return $tables;
	}
//---------- begin function schemaAddFileData
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function schemaAddFileData($table){
	global $CONFIG;
	if(!isset($CONFIG['starttype'])){return;}
	$progpath=dirname(__FILE__);
	$dir=realpath("{$progpath}/schema/{$CONFIG['starttype']}");
	
	$files=listFilesEx($dir,array('name'=>$table));
	//echo $dir.printValue($files);exit;
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
				if($field=='body' && !preg_match('/^(functions|mobile|css|js)$/i',$fname)){
                	$tables[$table][$fid]['_template']=2;
				}
				switch(strtolower($fname)){
		            case 'index':
		                $tables[$table][$fid]['title']="Home";
		                $tables[$table][$fid]['permalink']="home";
		                $tables[$table][$fid]['sort_order']=1;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['_template']=2;
						break;
					case 'about':
		                $tables[$table][$fid]['title']="About";
		                $tables[$table][$fid]['sort_order']=2;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['_template']=2;
						break;
					case 'contact':
		                $tables[$table][$fid]['title']="Contact";
		                $tables[$table][$fid]['permalink']="home";
		                $tables[$table][$fid]['sort_order']=3;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['_template']=2;
						break;
					case 'products':
						$tables[$table][$fid]['title']="Products";
		                $tables[$table][$fid]['sort_order']=4;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['_template']=2;
						break;
					case 'blog':
						$tables[$table][$fid]['title']="Blog";
		                $tables[$table][$fid]['sort_order']=6;
		                $tables[$table][$fid]['page_type']=1;
						$tables[$table][$fid]['parent']="contact";
						$tables[$table][$fid]['_template']=2;
						break;
					case 'forum':
						$tables[$table][$fid]['title']="Support";
		                $tables[$table][$fid]['sort_order']=7;
		                $tables[$table][$fid]['page_type']=1;
		                $tables[$table][$fid]['parent']="contact";
		                $tables[$table][$fid]['_template']=2;
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
	if(!isset($tables[$table])){return 0;}
	return count($tables[$table]);
}