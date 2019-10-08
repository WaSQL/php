<?php
//Set upload size
//Set Post Max Size
ini_set('POST_MAX_SIZE', '34M');
ini_set('UPLOAD_MAX_FILESIZE', '30M');
ini_set('max_execution_time', 5000);
set_time_limit(5500);

error_reporting(E_ERROR | E_WARNING | E_PARSE);
$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');

//includes
//echo "Before COMMON";exit;
include_once("{$progpath}/common.php");
global $CONFIG;
$CONFIG['translate_source_id']=-1;
include_once("{$progpath}/config.php");
//is SSL required for admin?
if(isset($CONFIG['admin_secure']) && in_array($CONFIG['admin_secure'],array(1,'true')) && !isSecure()){
	header("Location: https://{$_SERVER['HTTP_HOST']}/php/admin.php",true,301);
	exit;
}
//change timezone if set
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
include_once("{$progpath}/sessions.php");
include_once("{$progpath}/schema.php");

loadExtras('translate');
loadExtrasJs('chart');
set_error_handler("wasqlErrorHandler",E_STRICT | E_ALL);

//check for url_eval
if(isset($CONFIG['admin_eval'])){
	$out=includePage($CONFIG['admin_eval'],array());	
}
global $USER;

include_once("{$progpath}/user.php");

global $wtables;
$wtables=getWasqlTables();
//check WaSQL tables
foreach($wtables as $wtable){
	if(!isDBTable($wtable)){$ok=createWasqlTable($wtable);}
}
if(isset($_REQUEST['_pushfile'])){
	$ok=pushFile(decodeBase64($_REQUEST['_pushfile']));
}
//check for synchronize calls
if(isset($_REQUEST['_menu']) && (strtolower($_REQUEST['_menu'])=='synchronize' || strtolower($_REQUEST['_menu'])=='datasync') && isset($_REQUEST['load'])){
	$json=json_decode(base64_decode($_REQUEST['load']),true);
	if(!isset($json['func'])){
		echo json_encode(array('error'=>'invalid request'));
		exit;
	}
	//echo printValue($json);exit;
	switch(strtolower($json['func'])){
		case 'auth':
			if(!isAdmin()){
				echo json_encode(array('error'=>"auth: User '{$USER['username']}' is not an admin",'user'=>$USER,'request'=>$json));
				exit;
			}
			//send them the _auth
			global $USER;
			echo base64_encode(json_encode(array('auth'=>$USER['_auth'])));
			exit;
		break;
		case 'get_tables':
			global $USER;
			global $CONFIG;
			if(!isUser()){
				echo base64_encode(json_encode(array('error'=>"Not logged in")));
				exit;
			}
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"get_tables: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			$query=<<<ENDOFQUERY
				SELECT
					table_name tablename
					,table_rows records
				FROM information_schema.tables
				WHERE
					table_schema='{$CONFIG['dbname']}'
				ORDER BY table_name
ENDOFQUERY;
			$recs=getDBRecords(array('-query'=>$query,'-index'=>'tablename'));
			if(!is_array($recs)){$recs=array();}
			foreach($recs as $table=>$rec){
				$recs[$table]['fields']=array();
				$frecs=getDBFieldInfo($table);
				foreach($frecs as $field=>$frec){
					$recs[$table]['fields'][$field]=$frec['_dbtype_ex'];
				}
			}
			echo base64_encode(json_encode($recs));
			exit;
		break;
		case 'get_record':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"get_record:User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table']) || !isset($json['id']) || !isset($json['fields'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			$opts=array('-table'=>$json['table'],'_id'=>$json['id'],'-fields'=>implode(',',$json['fields']));
			$rec=getDBRecord($opts);
			//echo json_encode($rec);exit;
			if(!is_array($rec)){$rec=array();}
			foreach($rec as $k=>$v){
				if(strlen(trim($v))){
					$rec[$k]=base64_encode($v);
				}
			}
			echo base64_encode(json_encode($rec));
			exit;
		break;
		case 'get_records':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"get_records: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table']) || !isset($json['limit']) || !isset($json['offset'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			$recs=getDBRecords(array('-table'=>$json['table'],'-limit'=>$json['limit'],'-offset'=>$json['offset'],'-order'=>'_id'));
			if(!is_array($recs)){$recs=array();}
			//convert the record values into Base64 so they will for sure convert to json
			foreach($recs as $i=>$rec){
				foreach($rec as $k=>$v){
					if(strlen(trim($v))){
						$recs[$i][$k]=base64_encode($v);
					}
				}
			}
			echo base64_encode(json_encode($recs));
			exit;
		break;
		case 'datasync_records':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"datasync_records: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table']) || !isset($json['records']) || !isset($json['offset'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			if($json['offset']==0){
				$ok=truncateDBTable($json['table']);
			}
			$cnt=0;
			foreach($json['records'] as $rec){
				$opts=array();
				foreach($rec as $k=>$v){
					if(!strlen($v)){continue;}
					$opts[$k]=base64_decode($v);
				}
				$opts['-table']=$json['table'];
				$id=addDBRecord($opts);
				if(!isNum($id)){
					echo base64_encode(json_encode(array('error'=>$id)));
					exit;
				}
				$cnt++;
			}
			echo base64_encode(json_encode(array('count'=>$cnt)));
			exit;
		break;
		case 'get_schema':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"get_schema: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			$rec=array();
			$fields=getDBFieldInfo($json['table']);
			foreach($fields as $field=>$info){
				if(isWasqlField($field)){continue;}
				$rec[]="{$field} {$info['_dbtype_ex']}";
			}
			echo base64_encode(json_encode($rec));
			exit;
		break;
		case 'update_schemas':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"update_schemas:User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table']) || !isset($json['records'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			$out=array();
			foreach($json['records'] as $table=>$fields){
				$new=isDBTable($table)?0:1;
				$ok=updateDBSchema($table,$fields,$new);
				if($new==1){
					$out[]="<span class=\"icon-mark w_success\"></span> added table {$table} ".printValue($ok);
				}
				else{
					$out[]="<span class=\"icon-mark w_success\"></span> updated table {$table} ".printValue($ok);
				}
			}
			echo base64_encode(json_encode($out));
			exit;
		break;
		case 'update_records':
			global $USER;
			if(!isAdmin()){
				echo base64_encode(json_encode(array('error'=>"update_records: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			if(!isset($json['table']) || !isset($json['records'])){
				echo base64_encode(json_encode(array('error'=>'missing params')));
				exit;
			}
			//get ids
			$ids=array();
			foreach($json['records'] as $id=>$rec){$ids[]=$id;}
			$idstr=implode(',',$ids);
			$recs=getDBRecords(array('-table'=>$json['table'],'-index'=>'_id','-where'=>"_id in ({$idstr})"));
			$out=array();
			foreach($json['records'] as $id=>$rec){
				if(!isset($recs[$id])){
					//new record - add it
					$opts=array();
					foreach($rec as $k=>$v){
						if(isWasqlField($k)){continue;}
						if(!strlen($v) || $v=='null'){continue;}
						$opts[$k]=base64_decode($v);
					}
					$opts['-table']=$json['table'];
					$opts['_id']=$id;
					$opts['-nodebug']=true;
					$nid=addDBRecord($opts);
					if(isNum($nid)){
						$out[]="<span class=\"icon-mark w_success\"></span> added record {$id} success: ";
					}
					else{
						$out[]="<span class=\"icon-warning w_danger\"></span>  adding record {$id} failed: ".$nid;
					}
				}
				else{
					//existing record - edit it
					$opts=array();
					foreach($rec as $k=>$v){
						if(isWasqlField($k)){continue;}
						if(sha1($v) != sha1($recs[$id][$k])){
							$opts[$k]=base64_decode($v);
						}
					}
					$opts['-table']=$json['table'];
					$opts['-where']="_id={$id}";
					$ok=editDBRecord($opts);
					if(!isNum($ok)){
						$out[]="<span class=\"icon-warning w_danger\"></span> edited record {$id} failed: ".printValue($ok);
					}
					else{
						$out[]="<span class=\"icon-mark w_success\"></span> edited record {$id}";
					}
				}
			}
			echo base64_encode(json_encode($out));
			exit;
		break;
		case 'get_changes':
			if(!isset($json['fields']) || !isset($json['username'])){
				echo base64_encode(json_encode(array('error'=>'missing info')));
				exit;
			}
			//request to get sync changes
			//confirm user is an admin in this system
			if(!isAdmin()){
				//echo printValue($_REQUEST);exit;
				echo base64_encode(json_encode(array('error'=>"get_changes: User '{$USER['username']}' is not an admin [{$USER['_id']},{$USER['utype']}]")));
				exit;
			}
			$rtn=array();
			$fields=$json['fields'];
			foreach($fields as $table=>$fieldset){
				if(!in_array('_id',$fieldset)){$fieldset[]='_id';}
				$fieldstr=implode(',',$fieldset);
				$rtn[$table]=getDBRecords(array('-table'=>$table,'-fields'=>$fieldstr,'-eval'=>'md5','-noeval'=>'_id,_cuser,_cdate,_euser,_edate,_marker_','-index'=>'_id'));
			}
			//schema
			//get _dbtype_ex
			$tables=getDBTables();
			foreach($tables as $table){
				//if(isWasqlField($table)){continue;}
				$info=getDBFieldInfo($table);
				foreach($info as $field=>$f){
				$rtn['_schema_'][$table][$field]=$f['_dbtype_ex'];
				}
			}
			echo base64_encode(json_encode($rtn));
			exit;
		break;
	}
}

//Fix up REQUEST
foreach($_REQUEST as $key=>$val){
	if(is_array($val)){continue;}
	//get rid of some dumb Request keys like __ktv
	if(preg_match('/^\_\_[a-z]{2,4}$/',$key)){unset($_REQUEST[$key]);}
	//Dreamhost FIX - body field was getting stripped out in $_REQUEST, but not $_POST
 	if(isset($_REQUEST[$key]) && !strlen($_REQUEST[$key]) && isset($_POST[$key]) && strlen($_POST[$key])){
    	$_REQUEST[$key]=$_POST[$key];
	}
	//Check for inline image value
	if(isset($_REQUEST["{$key}_inline"]) && $_REQUEST["{$key}_inline"]==1){
        	$ok=processInlineImage($val,$key);
	}
}
//show phpinfo if that is the only request param
if(count($_REQUEST)==1){
	$k=implode('',array_keys($_REQUEST));
	if(!isAdmin() && $k != '_logout'){
		global $USER;
		echo buildHtmlBegin();
		echo '<div class="container-fluid">'.PHP_EOL;
		echo '	<div class="row">'.PHP_EOL;
		echo '		<div class="col-xs-12" style="padding:25px;">'.PHP_EOL;
		echo "			<h3><img src=\"/wfiles/wasql.png\" class=\"w_middle\" alt=\"\" /> '{$k}' requires admin access to view. Login first.</h3>".PHP_EOL;
		//echo printValue($USER).printValue($_COOKIE);
		echo userLoginForm(array('-action'=>$PHP_SELF.'?'.$k));
		echo '		</div>'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
		echo '</div>'.PHP_EOL;
	    echo buildHtmlEnd();
		exit;
	}
	switch(strtolower($k)){
		case 'phpinfo':
			phpinfo();
			exit;
		break;
		case 'env':
			echo buildHtmlBegin();
			echo '<div class="w_lblue w_bold w_big"><span class="icon-server w_grey w_big"></span> REMOTE Variables</div>'.PHP_EOL;
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Variable','Value'));
			foreach($_SERVER as $key=>$val){
				if(!stringBeginsWith($key,'remote') && !stringBeginsWith($key,'http')){continue;}
				echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
		        }
		    echo buildTableEnd();
		    echo buildHtmlEnd();
			exit;
		break;
	}
}

if(isset($_REQUEST['sqlprompt']) && strtolower($_REQUEST['sqlprompt'])=='csv export'){
    //export the query to csv
    $recs=getDBRecords(array('-query'=>stripslashes($_REQUEST['sqlprompt_command'])));
    $data=arrays2CSV($recs);
    pushData($data,'csv','sqlprompt_export.csv');
    exit;
}
$_REQUEST['debug']=1;


//get_magic_quotes_gpc fix if it is on
wasqlMagicQuotesFix();
//require user

global $PAGE;
global $TEMPLATE;
global $databaseCache;
global $USER;
//set AdminUserID to current user id - this will turn off query logging for queries on the backend
$_SERVER['WaSQL_AdminUserID']=$USER['_id'];
//Verify all the WaSQL internal tables are built
if(!isDBTable('_users')){$ok=createWasqlTable('_users');}
if(!isDBTable('_models')){$ok=createWasqlTable('_models');}
if(!isDBTable('_minify')){$ok=createWasqlTable('_minify');}
//uncomment below to see hidden debug statements in output
//$_SERVER['_admin_']=1;
$_SERVER['_start_']=microtime(true);
if(isAdmin() && isset($_REQUEST['_su_']) && isNum($_REQUEST['_su_'])){
	//Switch User - only if current user is an admin
	$suser=getDBUserById($_REQUEST['_su_']);
	if(is_array($suser)){
		$USER=$suser;
		$guid=getGUID(1);
		$ok=editDBUser($USER['_id'],array('guid'=>'NULL'));
		$ok=editDBUser($suser['_id'],array('guid'=>$guid));
		setUserInfo($guid);
		}
    }
$PAGE['name']='php/admin.php';
$PAGE['_id']=0;
$TEMPLATE['_id']=0;
//$wasql_version=wasqlVersion();
//process actions
if(isset($_SERVER['CONTENT_TYPE']) && preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
 	if(isset($_REQUEST['processFileUploads']) && $_REQUEST['processFileUploads']=='off'){}
 	else{processFileUploads();}
    }
if(isset($_REQUEST['_action'])){
 	$ok=processActions();
   	}
//get rid of some dumb Requests keys like __ktv
foreach($_REQUEST as $key=>$val){
	if(preg_match('/^\_\_[a-z]{2,4}$/',$key)){unset($_REQUEST[$key]);}
	}
//check for Config Settings
global $ConfigSettings;
$ConfigSettings=getDBAdminSettings();

//Handle ajax requests
if(isAjax()){
	if(!isUser()){
		echo 'Not logged in';
		exit;
	}
	//facebook functions
	if(isset($_REQUEST['_fbupdate'])){
    	$str=decodeBase64($_REQUEST['_fbupdate']);
    	list($id,$email)=preg_split('/\:/',$str,2);
    	executeSQL("update _users set facebook_id='{$id}',facebook_email='{$email}' where _id={$USER['_id']}");
    	echo buildOnLoad("facebook_id='{$id}';facebook_email='{$email}';facebookLinked();");
    	exit;
	}
	elseif(isset($_REQUEST['_fblink'])){
    	$str=decodeBase64($_REQUEST['_fblink']);
    	list($id,$email)=preg_split('/\:/',$str,2);
    	executeSQL("update _users set facebook_id='{$id}',facebook_email='{$email}' where _id={$USER['_id']}");
    	echo buildOnLoad("facebook_id='{$id}';facebook_email='{$email}';facebookLinked();");
    	exit;
	}

	switch(strtolower($_REQUEST['_menu'])){
		case 'tempfiles':
		case 'git':
		case 'reports':
		case 'htmlbox':
		case 'settings':
		case 'synchronize':
		case 'datasync':
		case 'test':
			echo adminViewPage($_REQUEST['_menu']);exit;
		break;
		case 'clearmin':
			wasqlClearMinCache();
			echo "Min Cache Cleared<br />Refreshing Page";
			echo buildOnLoad("window.location=window.location;");
			exit;
		break;
		case 'export':
			echo adminViewPage('export');exit;
		break;
    	case 'cronboard':
    		echo adminCronBoard();
    		exit;
    		break;
    	case 'updatecheck':
    		echo wasqlUpdateCheck();
    		exit;
    		break;
    	case 'explore':
    		echo fileExplorer();
    		exit;
    		break;
    	case 'add':
    		//echo '<div class="w_centerpop_title"> Add Record '.$_REQUEST['_table_'].'</div>'.PHP_EOL;
			//echo '<div class="w_centerpop_content">'.PHP_EOL;
			switch(strtolower($_REQUEST['_table_'])){
				case '_pages':
					$ok=adminDefaultPageValues();
				break;
				case '_templates':
					$ok=adminDefaultTemplateValues();
				break;
				case '_reports':
					if(isset($_REQUEST['sqlprompt']) && strlen($_REQUEST['sqlprompt_command'])){
						$_REQUEST['query']=$_REQUEST['sqlprompt_command'];
					}
				break;
			}
			
			if(isset($CONFIG['dbname_stage'])){
                $xtables=adminGetSynchronizeTables($CONFIG['dbname_stage']);
                if(in_array($_REQUEST['_table_'],$xtables)){
					echo '<div class="w_bold">'.PHP_EOL;
					echo '	<span class="icon-warning w_danger w_bold"></span>'.PHP_EOL;
					echo '	"'.$_REQUEST['_table_'].'" is a synchronize table. New records must be added on the staging site.'.PHP_EOL;
					echo '</div>'.PHP_EOL;
					echo '</div>'.PHP_EOL;
					exit;
				}
				else{
					$addopts=array(
						'-action'=>'/php/admin.php',
						'-table'=>$_REQUEST['_table_'],
						'_table_'=>$_REQUEST['_table_'],
						'_menu'=>'list',
						'_sort'=>$_REQUEST['_sort'],
						'_start'=>$_REQUEST['_start']
					);
					//echo printValue($addopts);
					echo addEditDBForm($addopts);
				}
			}
			else{
				$addopts=array(
					'-action'=>'/php/admin.php',
					'-table'=>$_REQUEST['_table_'],
					'_table_'=>$_REQUEST['_table_'],
					'_menu'=>'list'
				);
				if(isset($_REQUEST['_sort'])){$addopts['_sort']=$_REQUEST['_sort'];}
				if(isset($_REQUEST['_start'])){$addopts['_start']=$_REQUEST['_start'];}
				//echo printValue($addopts);
	    		echo addEditDBForm($addopts);
			}
			//echo '</div>'.PHP_EOL;
			exit;
    		break;
    	case 'decode':
			echo adminViewPage('decode');exit;
    	break;
    	case 'ab':
			echo adminViewPage('apachebench');exit;
    	break;
    	case 'sysmon':
    		adminSetPageName();
    		echo includeModule('sysmon');exit;
			//echo adminViewPage('translate');exit;
    	break;
    	case 'translate':
    		adminSetPageName();
    		echo includeModule('translate');exit;
			//echo adminViewPage('translate');exit;
    	break;
    	case 'manual':
			echo adminViewPage('manual');exit;
		break;
    	case 'sqlprompt':
			echo adminViewPage('sqlprompt');exit;
		break;
		case 'phpprompt':
			echo '<div class="container-fluid">'.PHP_EOL;
			echo adminViewPage('phpprompt');
			echo '</div>'.PHP_EOL;
			exit;
		break;
    	case 'tabledetails':
    		if(isset($_REQUEST['table'])){
				//echo '<div style="margin-left:15px;">'.PHP_EOL;
				$finfo=getDBFieldInfo($_REQUEST['table']);
				/*
					get the used count for these fields if as follows:
					SELECT
					  sum(IF(description IS NULL OR description='', 0, 1 )) as description,
					  sum(IF(functions IS NULL OR functions='', 0, 1 )) as functions
					 FROM _pages
				*/
				$sums=array();
				foreach($finfo as $info){
					$fname=$info['_dbfield'];
					//if(isWasqlField($fname)){continue;}
					$sums[]="sum(IF({$fname} IS NULL OR {$fname}='', 0, 1 )) as {$fname}";
				}
				$query="SELECT count(*) as _total_, ".implode(', ',$sums)." from {$_REQUEST['table']}";
				$srecs=getDBRecords(array('-query'=>$query));
				//echo $query.printValue($srecs);
				$tcnt=$srecs[0]['_total_'];
				foreach($srecs[0] as $sfield=>$cnt){
					if($sfield=='_total_'){continue;}
					$finfo[$sfield]['rec_cnt']=$cnt;

					$finfo[$sfield]['null_cnt']=$tcnt-$cnt;
					}
				//show total records for this table
				echo "<div>Record Count: {$tcnt}</div>\n";
				echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
				echo buildTableTH(array('Field','Type','Len','Null','Recs','Nulls'));
				//echo printValue($finfo);
				foreach($finfo as $info){
					echo '	<tr>'.PHP_EOL;
					echo '		<td>'.$info['_dbfield'].'</td>'.PHP_EOL;
					echo '		<td>'.$info['_dbtype'].'</td>'.PHP_EOL;
					echo '		<td>'.$info['_dblength'].'</td>'.PHP_EOL;
					echo '		<td>'.$info['_dbnull'].'</td>'.PHP_EOL;
					echo '		<td align="right">'.$info['rec_cnt'].'</td>'.PHP_EOL;
					echo '		<td align="right">'.$info['null_cnt'].'</td>'.PHP_EOL;
					echo '	</tr>'.PHP_EOL;
				}
				echo buildTableEnd();
				//echo printValue($finfo);
				//echo '</div>'.PHP_EOL;
			}
			elseif(isset($_REQUEST['sqlprompt_command'])){
				$cmds=preg_split('/\;+/',evalPHP(trim($_REQUEST['sqlprompt_command'])));
				foreach($cmds as $cmd){
					$singleline=preg_replace('/[\r\n]+/',' ',$cmd);
					if(preg_match('/^(explain|select|show|desc)\ /is',$singleline)){
						//test select statements first
						if(preg_match('/^select\ /is',$cmd)){
							$recs=getDBRecords(array('-query'=>'explain '.$cmd));
							if(!is_array($recs) && strlen($recs)){
								echo $recs;
							}
							elseif(isset($recs[0]['sql_error'])){
								echo $recs[0]['sql_error'];
			            	}
			        		else{
								//echo $cmd;
			        			echo listDBResults($cmd,array('-hidesearch'=>1));
							}
						}
						else{
			        		echo listDBResults($cmd,array('-hidesearch'=>1));
						}
	        		}
					else{
						$result=executeSQL($cmd);
						if(is_array($result) && isset($result['error'])){
							echo $result['error'];
							echo $result['query'];
	                	}
	                	elseif($result!=1){echo printValue($result);}
	                	else{echo 'ok ';}
					}
				}
        	}
        	else{
            	echo printValue($_REQUEST);
			}
    		exit;
    		break;
    	case 'contentmanager':
    		switch(strtolower($_REQUEST['emenu'])){
            	case 'edit':
		    		if(isNum($_REQUEST['id'])){
						$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_REQUEST['id']));
						if(!is_array($rec)){
                        	echo "No page found";
                        	break;
						}
						//table record edit
			    		$dname = '<span class="w_bold w_bigger w_dblue">Content for '.$rec['name'].' Page</span>'.PHP_EOL;

						echo '<div style="position:relative;">'.PHP_EOL;
						$opts=array(
							'-table'=>'_pages',
							'_id'=>$_REQUEST['id'],
							'-action'=>$_SERVER['PHP_SELF'],
							'user_content_width'=>700,
							'user_content_height'=>400,
							'user_content_dname'=>$dname,
							'emenu'=>'record',
							'_menu'=>'contentmanager',
							'-hide'=>'clone,delete',
							'-onsubmit'=>"this._preview.value='';ajaxSubmitForm(this,'modal');return false;",
							'-fields'=>'user_content'
							);
						echo addEditDBForm($opts);
						echo '</div>'.PHP_EOL;
					}
		    		break;
		    	case 'add':
		    		if(isset($_REQUEST['table'])){
		    			switch(strtolower($_REQUEST['table'])){
							case '_pages':
								$ok=adminDefaultPageValues();
							break;
							case '_templates':
								$ok=adminDefaultTemplateValues();
							break;
							case '_reports':
								if(isset($_REQUEST['sqlprompt']) && strlen($_REQUEST['sqlprompt_command'])){
									$_REQUEST['query']=$_REQUEST['sqlprompt_command'];
								}
							break;
						}
						//table record add
			    		//echo '<div class="w_centerpop_title">New Record in '.$_REQUEST['table'].' table.</div>'.PHP_EOL;
						//echo '<div class="w_centerpop_content">'.PHP_EOL;
						$addopts=array(
							'-table'=>'_pages',
							'-action'=>$_SERVER['PHP_SELF'],
							'content_width'=>700,
							'name_width'=>700,
							'permalink_width'=>700,
							'description_width'=>700,
							'emenu'=>'record',
							'_menu'=>'contentmanager',
							'-onsubmit'=>"ajaxSubmitForm(this,'modal');return false;"
						);
						//echo printValue($addopts);
						echo addEditDBForm($addopts);
						//echo '</div>'.PHP_EOL;
					}
		    		break;
		    	case 'record':
		    		if(isNum($_REQUEST['edit_id']) || isNum($_REQUEST['add_id'])){
						echo '<b style="color:#0d7d0c;">Saved!</b>';
						unset($_REQUEST['edit_rec']);
						//echo printValue($_REQUEST);
						//update menubar
	            		echo '	<div id="w_editor_nav_update" style="display:none;">'.PHP_EOL;
						echo contentManager();
						echo '	</div>'.PHP_EOL;
						echo buildOnLoad("setText('w_editor_nav',getText('w_editor_nav_update'));");

					}
		    		break;
		    	default:
		    		echo 'Error: Invalid eMenu option :' . printValue($_REQUEST['emenu']);
		    		break;
			}
			exit;
    		break;
    	case 'editor':
    		//waSQL Inline Editor menu options
    		switch(strtolower($_REQUEST['emenu'])){
		    	case 'edit':
		    		if(isset($_REQUEST['table']) && isNum($_REQUEST['id'])){
						//table record edit
			    		//echo '<div class="w_centerpop_title">Editing Record #'.$_REQUEST['id'].' in '.$_REQUEST['table'].' table.</div>'.PHP_EOL;
						//echo '<div class="w_centerpop_content">'.PHP_EOL;
						$opts=array(
							'-table'=>$_REQUEST['table'],
							'_id'=>$_REQUEST['id'],
							'-action'=>$_SERVER['PHP_SELF'],
							'body_width'=>800,
							'functions_width'=>800,
							'name_width'=>600,
							'permalink_width'=>800,
							'description_width'=>800,
							'emenu'=>'record',
							'_menu'=>'editor',
							'-hide'=>'clone',
							'-onsubmit'=>"this._preview.value='';ajaxSubmitForm(this,'modal');return false;"
							);
						if($_REQUEST['table']=='_pages'){
		                	$opts['-preview']=$_REQUEST['id'];
						}
						echo addEditDBForm($opts);
						if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['table'])){
							echo buildOnLoad("document.addedit.name.focus();");
						}
						//echo '</div>'.PHP_EOL;
					}
					elseif(isset($_REQUEST['file'])){
						echo editorFileEdit($_REQUEST['file']);
					}
		    		break;
		    	case 'add':
		    		if(isset($_REQUEST['table'])){
		    			switch(strtolower($_REQUEST['table'])){
							case '_pages':
								$ok=adminDefaultPageValues();
							break;
							case '_templates':
								$ok=adminDefaultTemplateValues();
							break;
							case '_reports':
								if(isset($_REQUEST['sqlprompt']) && strlen($_REQUEST['sqlprompt_command'])){
									$_REQUEST['query']=$_REQUEST['sqlprompt_command'];
								}
							break;
						}
						//table record edit
			    		//echo '<div class="w_centerpop_title">New Record in '.$_REQUEST['table'].' table.</div>'.PHP_EOL;
						//echo '<div class="w_centerpop_content">'.PHP_EOL;
						echo addEditDBForm(array(
							'-table'=>$_REQUEST['table'],
							'-action'=>$_SERVER['PHP_SELF'],
							'body_width'=>800,
							'functions_width'=>800,
							'name_width'=>600,
							'permalink_width'=>800,
							'description_width'=>800,
							'emenu'=>'record',
							'_menu'=>'editor',
							'-onsubmit'=>"ajaxSubmitForm(this,'modal');return false;"
							));
						if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['table'])){
							echo buildOnLoad("document.addedit.name.focus();");
						}
						//echo '</div>'.PHP_EOL;
					}
					elseif(isset($_REQUEST['filetype'])){
						echo editorFileAdd($_REQUEST['filetype']);
					}
		    		break;
		    	case 'file':
		    		if(isset($_REQUEST['file']) && isset($_REQUEST['file_content'])){
						$afile=realpath($_REQUEST['file']);
						$content=$_REQUEST['file_content'];
		            	$ok=setFileContents($afile,$content);
		            	if(isNum($ok) && $ok > 0){
		            		echo '<b style="color:#0d7d0c;">Saved! '.$ok.'</b>';
		            		//echo "<hr>{$content}";
		            		//update menubar
		            		echo '	<div id="w_editor_nav_update" style="display:none;">'.PHP_EOL;
							echo editorNavigation();
							echo '	</div>'.PHP_EOL;
							echo buildOnLoad("setText('w_editor_nav',getText('w_editor_nav_update'));");
						}
						else{
							echo '<b style="color:#a30001;">Failed to save! '.$ok.'</b>';
							echo $afile;
						}
					}
					elseif(isset($_REQUEST['filetype']) && isset($_REQUEST['file_content'])){
						switch(strtolower(getFileExtension($_REQUEST['filetype']))){
					    	case 'js':$path='../wfiles/js/custom';break;
					    	case 'css':$path='../wfiles/css/custom';break;
					    	case 'xml':$path='../wfiles';break;
						}
						$apath=realpath($path);
						$afile="{$path}/{$_REQUEST['filename']}.{$_REQUEST['filetype']}";
						$content=$_REQUEST['file_content'];
		            	$ok=setFileContents($afile,$content);
		            	if(isNum($ok) && $ok > 0){
		            		echo '<b style="color:#0d7d0c;">Saved! '.$ok.'</b>';
		            		echo "<hr>{$content}";
		            		//update menubar
		            		echo '	<div id="w_editor_nav_update" style="display:none;">'.PHP_EOL;
							echo editorNavigation();
							echo '	</div>'.PHP_EOL;
							echo buildOnLoad("setText('w_editor_nav',getText('w_editor_nav_update'));");
						}
						else{
							echo '<b style="color:#a30001;">Failed to save! '.$ok.'</b>';
							echo $afile;
						}
		            	//redraw menu
		            	echo '<div style="display:none" id="navigation_refresh">'.PHP_EOL;
		            	echo editorNavigation();
		            	echo '</div>'.PHP_EOL;
		            	echo buildOnLoad("setText('w_editor_nav',getText('navigation_refresh'));");

					}
					else{
		            	echo 'invalid request';
		            	echo printValue(array_keys($_REQUEST));
					}
		    		break;
		    	case 'record':
		    		if(isNum($_REQUEST['edit_id']) || isNum($_REQUEST['add_id'])){
						if(isset($_REQUEST['_preview']) && strlen($_REQUEST['_preview'])){
			            	echo includePage($_REQUEST['_preview']);
						}
						else{echo '<b style="color:#0d7d0c;">Saved!</b>';}
						//update menubar
	            		echo '	<div id="w_editor_nav_update" style="display:none;">'.PHP_EOL;
						echo editorNavigation();
						echo '	</div>'.PHP_EOL;
						echo buildOnLoad("setText('w_editor_nav',getText('w_editor_nav_update'));");

					}
		    		break;
		    	default:
		    		echo 'Error: Invalid eMenu option :' . printValue($_REQUEST['emenu']);
		    		break;
			}
			exit;
    		break;
    	case 'sandbox':
    		echo evalPHP($_REQUEST['sandbox_code']);
    		exit;

    		break;
    	case 'addmultiple':
    		//echo printValue($_REQUEST);
    		$lines=preg_split('/[\r\n]+/',trim($_REQUEST['_schema']));
    		if(!count($lines)){

				echo 'No schema tables or fields found to process';
				exit;
			}
			$schemas=array();
			$ctable='';
			foreach($lines as $line){
				if(!preg_match('/^[\t\s]/',$line)){
                	$ctable=trim($line);
                	//echo "ctable:{$ctable}<br>\n";
                	continue;
				}
				if(!strlen($ctable)){continue;}
				if(!strlen(trim($line))){continue;}
				$schemas[$ctable] .= trim($line) . "\r\n";
			}
			if(!count($schemas)){
            	echo 'No tables found to process';
            	echo printValue($lines);
            	exit;
			}
			echo '<div style="width:500px;height:300px;padding-right:25px;overflow:auto;">'.PHP_EOL;
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Tablename','Status','More Info'));
			foreach($schemas as $table=>$fieldstr){
				echo '	<tr valign="top">'.PHP_EOL;
				//dropDBTable($table,1);
				unset($databaseCache['isDBTable'][$table]);
            	echo "		<td>{$table}</td>";
            	$ok=createDBTableFromText($table,$fieldstr);
				if(!isNum($ok)){
					echo '<td class="w_red w_bold">FAILED</td><td>'.$ok.'</td>'.PHP_EOL;
				}
				else{
                	echo '<td>SUCCESS</td><td>'.nl2br($fieldstr).'</td>'.PHP_EOL;
				}
				echo '</tr>'.PHP_EOL;
			}
			echo buildTableEnd();
    		exit;
    		break;
    	case 'session_errors':
    		$sessionID=session_id();
			echo adminShowSessionLog($sessionID);
			$t=isNum($_REQUEST['t'])?$_REQUEST['t']:10;
			echo buildOnLoad("scheduleSessionErrors('session_errors',{$t});");
			exit;
			break;
		case 'clear_session_errors':
    		$sessionID=session_id();
			adminClearSessionLog($sessionID);
			echo adminShowSessionLog($sessionID);
			$t=isNum($_REQUEST['t'])?$_REQUEST['t']:10;
			echo buildOnLoad("scheduleSessionErrors('session_errors',{$t});");
			exit;
			break;
	}
}
//set a minify session variable
wasqlSetMinify(1);
//check for user
if(!isUser()){
	$params=array(
		'title'=>$_SERVER['HTTP_HOST'].' Admin Login'
	);
	echo buildHtmlBegin($params);
	echo adminViewPage('topmenu');
	echo '	<div style="padding:5px;color:'.$ConfigSettings['mainmenu_text_color'].';">'.PHP_EOL;
	$formopts=array(
		'-action'=>'/php/admin.php',
		'-show_icons'=>$ConfigSettings['mainmenu_icons']
		);
	if(isset($_REQUEST['_menu'])){$formopts['_menu']=$_REQUEST['_menu'];}
	if(isset($_REQUEST['_table_'])){$formopts['_table_']=$_REQUEST['_table_'];}
	echo userLoginForm($formopts);
	echo '</div>'.PHP_EOL;
	echo buildHtmlEnd();
	exit;
	}
elseif($USER['utype'] != 0){
	echo buildHtmlBegin();
	echo adminViewPage('topmenu');
	echo '<div class="well" style="border-radius:0px;">'.PHP_EOL;
	echo '	<span class="icon-block w_danger w_biggest w_bold"></span><b class="w_danger w_biggest"> Administration access denied.</b>'.PHP_EOL;
	echo '	<div class="w_big">You must log in as an administrator to access the administration area.</div>'.PHP_EOL;
	echo '</div>'.PHP_EOL;
	$formopts=array(
		'-action'=>'/php/admin.php',
		'-show_icons'=>$ConfigSettings['mainmenu_icons']
		);
	if(isset($_REQUEST['_menu'])){$formopts['_menu']=$_REQUEST['_menu'];}
	if(isset($_REQUEST['_table_'])){$formopts['_table_']=$_REQUEST['_table_'];}
	echo '<div style="margin-top:20px;margin-left:20px;">'.PHP_EOL;
	echo userLoginForm($formopts);
	echo '</div>'.PHP_EOL;
	echo buildHtmlEnd();
	exit;
	}
//

if(isset($_REQUEST['_menu']) && strtolower($_REQUEST['_menu'])=='export' && isset($_REQUEST['func']) && $_REQUEST['func']=='export'){
	echo adminViewPage('export');
	exit;
}
//Create new table?
if(isset($_REQUEST['_menu']) && $_REQUEST['_menu']=='add' && isset($_REQUEST['_table_']) && isset($_REQUEST['_schema'])){
	$_SESSION['admin_errors']=array();
	$ok=createDBTableFromText($_REQUEST['_table_'],$_REQUEST['_schema']);
	if(!isNum($ok)){$_SESSION['admin_errors'][]=$ok;}
	}
//process pre-menu commands
if(isset($_REQUEST['_groupwith']) && isset($_REQUEST['_table_'])){
	//group with
	$rec=getDBRecord(array('-table'=>'_tabledata','tablename'=>$_REQUEST['_table_']));
	if(is_array($rec)){
    	$ok=editDBRecord(array('-table'=>'_tabledata',
		'-where'=>"_id={$rec['_id']}",
		'tablegroup'=>$_REQUEST['_groupwith']
		));
	}
	else{
    	$ok=addDBRecord(array('-table'=>'_tabledata','tablename'=>$_REQUEST['_table_'],'tablegroup'=>$_REQUEST['_groupwith']));
	}
}
if(isset($_REQUEST['_menu'])){
	switch(strtolower($_REQUEST['_menu'])){
		case 'tables':
			if(isset($_REQUEST['update'])){
            	foreach($_REQUEST as $key=>$val){
                	if(preg_match('/^g\_(.+)/',$key,$m)){
						$tablename=$m[1];
						$rec=getDBRecord(array('-table'=>'_tabledata','tablename'=>$tablename));
						if(is_array($rec)){
							if(!strlen($val)){$val='NULL';}
                        	$ok=editDBRecord(array('-table'=>'_tabledata','-where'=>"_id={$rec['_id']}",'tablegroup'=>$val));
						}
						elseif(strlen($val)){
                        	$ok=addDBRecord(array('-table'=>'_tabledata','tablename'=>$tablename,'tablegroup'=>$val));
						}
					}
					if(preg_match('/^d\_(.+)/',$key,$m)){
						$tablename=$m[1];
						$rec=getDBRecord(array('-table'=>'_tabledata','tablename'=>$tablename));
						if(is_array($rec)){
							if(!strlen($val)){$val='NULL';}
                        	$ok=editDBRecord(array('-table'=>'_tabledata','-where'=>"_id={$rec['_id']}",'tabledesc'=>$val));
						}
						elseif(strlen($val)){
                        	$ok=addDBRecord(array('-table'=>'_tabledata','tablename'=>$tablename,'tabledesc'=>$val));
						}
					}
				}
			}
			break;
		case 'drop':
			if(isset($_REQUEST['_table_'])){
			 	$dropResult = dropDBTable($_REQUEST['_table_'],1);
				}
			break;
		case 'postedit_xml':
			$xml='';
			$xml .= xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
			$xml .= '<hosts>'.PHP_EOL;
			$xml .= '	<host'.PHP_EOL;
			$xml .= '		name="'.$_SERVER['HTTP_HOST'].'"'.PHP_EOL;
			$xml .= '		group="'.$_SERVER['UNIQUE_HOST'].'"'.PHP_EOL;
			$xml .= '		apikey="'.$USER['apikey'].'"'.PHP_EOL;
			$xml .= '		username="'.$USER['username'].'"'.PHP_EOL;
			$xml .= '		groupby="name"'.PHP_EOL;
			$xml .= '	/>'.PHP_EOL;
			$xml .= '</hosts>'.PHP_EOL;
			pushData($xml,"xml","postedit.xml");
			break;
		}
	}
//cache the page for 5 seconds
$expire=gmdate('D, d M Y H:i:s', time()+10);
adminSetHeaders();
$js=<<<ENDOFJSSCRIPT
<script type="text/javascript">
	//---------- begin function syncTableClick ----
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	function syncTableClick(c){
		c=getObject(c);
		var tabs=GetElementsByAttribute('button', 'data-group', 'synctabletabs');
		for(var i=0;i<tabs.length;i++){
			setClassName(tabs[i],'btn');
		}
		setClassName(c,'btn yellow active');
		var sdiv=c.getAttribute('data-div');
		setText('syncDiv',getText(sdiv));
		return true;
	}
	//---------- begin function renameBackup ----
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	function renameBackup(obj){
		alertify.renameBackup=getObject(obj);
		alertify.prompt('<span class="icon-rename w_bigger w_grey"> New Filename:</span>', function (e, str) {
			if (e) {
				var obj=alertify.renameBackup;
				obj.href+='&name='+str;
				alertify.renameBackup='';
				window.location=obj.href;
			}
		});
		return false;
	}
</script>
ENDOFJSSCRIPT;
$params=array(
	'js'=>$js,
	'title'=>$_SERVER['HTTP_HOST'].' Admin'
	);
echo buildHtmlBegin($params);
echo adminViewPage('topmenu');
echo '<div id="admin_body" style="position:relative;padding:0 10px 3px 15px;">'.PHP_EOL;
//process _menu request
if(isset($_REQUEST['_menu'])){
	switch(strtolower($_REQUEST['_menu'])){
		case 'tempfiles':
		case 'git':
		case 'reports':
		case 'htmlbox':
		case 'test':
			echo adminViewPage($_REQUEST['_menu']);exit;
		break;
		case 'export':
			echo adminViewPage('export');exit;
		break;
		case 'editor':
			echo '<table class="table table-responsive table-striped table-bordered" width="100%"><tr valign="top">'.PHP_EOL;
			echo '	<td class="nowrap">'.PHP_EOL;
			echo '	<div class="w_bold" style="padding-bottom:8px;border-bottom:1px solid #000;"><img src="/wfiles/wasql_admin.png" class="w_middle" alt="Inline Editor Menu" /> Inline Editor Menu</div>'.PHP_EOL;
			echo '	<div id="w_editor_nav">'.PHP_EOL;
			echo editorNavigation();
			echo '	</div></td>'.PHP_EOL;
			echo '	<td width="100%"><div id="w_editor_main">'.PHP_EOL;
			echo '	</div></td>'.PHP_EOL;
			echo '</tr></table>'.PHP_EOL;
			break;
		case 'contentmanager':
			echo '<table class="table table-responsive table-striped table-bordered" width="100%"><tr valign="top">'.PHP_EOL;
			echo '	<td class="nowrap">'.PHP_EOL;
			echo '	<div class="w_bold" style="padding-bottom:8px;border-bottom:1px solid #000;"><img src="/wfiles/iconsets/32/contentmanager.png" class="w_middle" alt="content manager" /> Content Manager</div>'.PHP_EOL;
			echo '	<div id="w_editor_nav">'.PHP_EOL;
			echo contentManager();
			echo '	</div></td>'.PHP_EOL;
			echo '	<td width="100%"><div id="w_editor_main">'.PHP_EOL;
			echo '	</div></td>'.PHP_EOL;
			echo '</tr></table>'.PHP_EOL;
			break;
		case 'phpinfo':
			//Server Variables
			$data=adminGetPHPInfo();
			if(preg_match('/\<body\>(.+)\<\/body\>/ism',$data,$m)){
				echo <<<ENDOFX
				<style type="text/css">
				table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
				.center {text-align: center;}
				.center table {margin: 1em auto; text-align: left;}
				.center th {text-align: center !important;}
				td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
				h1 {font-size: 150%;}
				h2 {font-size: 125%;}
				.p {text-align: left;}
				.e {background-color: #ccf; width: 300px; font-weight: bold;}
				.h {background-color: #99c; font-weight: bold;}
				.v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
				.v i {color: #999;}
				</style>
ENDOFX;
				echo $m[1];
			}
			else{
				echo $data;
			}
			break;
		case 'env':
			//Server Variables
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-server w_grey"></span> Server Variables</div>'.PHP_EOL;
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Variable','Value'));
			foreach($_SERVER as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
            	}
            echo buildTableEnd();
			break;
		case 'iconsets':
			//Server Variables
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-file-image w_big"></span> List Iconsets</div>'.PHP_EOL;
			echo '<hr size="1" style="padding:0px;margin:0px;">'.PHP_EOL;
			$iconsets=listIconsets();
			echo '<table class="table table-responsive table-striped">'.PHP_EOL;
			echo '<tr>';
			$cnt=0;
			foreach($iconsets as $name){
				if(preg_match('/^thumbs$/i',$name)){continue;}
            	echo '<td class="w_pad w_smallest w_lblue" align="center">'.PHP_EOL;
            	echo '	<div><img src="/wfiles/iconsets/64/'.$name.'.png" width="64" height="64" class="w_middle" alt="'.$name.'" /></div>'.PHP_EOL;
            	echo '	<div class="w_bold w_dblue w_bigger">'.$name.'</div>'.PHP_EOL;
				echo '	<div><b>16:</b> /wfiles/iconsets/16/'.$name.'.png</div>'.PHP_EOL;
            	echo '	<div><b>32:</b> /wfiles/iconsets/32/'.$name.'.png</div>'.PHP_EOL;
            	echo '	<div><b>64:</b> /wfiles/iconsets/64/'.$name.'.png</div>'.PHP_EOL;
				echo '</td>'.PHP_EOL;
				$cnt++;
				if($cnt==4){
                	echo '</tr><tr>'.PHP_EOL;
                	$cnt=0;
				}
			}
			echo '</tr></table>'.PHP_EOL;
			//echo printValue($iconsets);
			break;
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Variable','Value'));
			foreach($_SERVER as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
            	}
            echo buildTableEnd();
			break;
		case 'font_icons':
			//Server Variables
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-slideshow"></span> WaSQL Font Icons</div>'.PHP_EOL;
			echo 'Usage: <span class="icon-tag"></span> <xmp style="display:inline;"><span class="icon-tag"></span></xmp>'.PHP_EOL;
			echo '<hr size="1" />'.PHP_EOL;
			$icons=wasqlFontIcons();
			$sets=arrayColumns($icons,4);
			echo '<div class="row">'.PHP_EOL;
			foreach($sets as $icons){
				echo '		<div class="col-sm-3 w_nowrap">'.PHP_EOL;
            	foreach($icons as $icon){
                	echo '			<div class="w_biggest w_dblue"><span class="'.$icon.'"></span> '.$icon.'</div>'.PHP_EOL;
				}
				echo '		</div>'.PHP_EOL;
			}
			echo '</div>'.PHP_EOL;
		break;
		case 'system':
			//Server Variables
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-server w_black"></span> System Info</div>'.PHP_EOL;
			$info=getServerInfo();
			//first show all the items that are not arrays
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Name','Description'));
			foreach($info as $key=>$val){
				if(!strlen($key)){continue;}
				if(is_array($val)){continue;}
				$name=str_replace('_',' ',$key);
				$name=ucwords($name);
				echo buildTableTD(array($name,$val));
            	}
            //show array values
            foreach($info as $key=>$val){
				if(!strlen($key)){continue;}
				if(!is_array($val)){continue;}
				$name=str_replace('_',' ',$key);
				$name=ucwords($name);
				echo '	<tr><td>'.$name.'</td><td class="nowrap">'.PHP_EOL;
				//if(count($val) > 5 && is_array($val[0])){echo '		<div style="height:150px;overflow:auto;padding-right:20px;">'.PHP_EOL;}
				$fields=array();
				foreach($val as $x=>$subval){
					if(is_array($subval)){
						foreach($subval as $key2=>$val2){$fields[$key2]=1;}
						}
					else{$fields[$x]=1;}
					}
				echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
				echo buildTableTH(array_keys($fields));
				foreach($val as $x=>$subval){
					$svals=array();
					if(is_array($subval)){
						foreach($fields as $field=>$cnt){$svals[]=isset($subval[$field])?$subval[$field]:'';}
						echo buildTableTD($svals);
						}
					else{
						foreach($fields as $field=>$cnt){$svals[]=isset($val[$field])?$val[$field]:'';}
						echo buildTableTD($svals);
						break;
                    	}
                	}
				echo buildTableEnd();
				//if(count($val) > 5){echo '		</div>'.PHP_EOL;}
				echo '	</td></tr>'.PHP_EOL;
            	}
            echo buildTableEnd();
            //echo printValue($info);
			break;
			//$info=getServerInfo();
			echo printValue($info);
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			//echo buildTableTH(array('Variable','Value'));

            echo buildTableEnd();
			break;
		case 'terminal':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-prompt"></span> Terminal</h2>'.PHP_EOL;
			$shortcuts=array(
				'Show Help'=>'help',
				'Identify Server'=>'uname -a'
			);
			if(isWindows()){
				$shortcuts['IPConfig']='ipconfig';
				$shortcuts['Drive Space']='wmic logicaldisk get size,freespace,caption,description,filesystem';
		}
			else{
				$shortcuts['IFConfig']='ifconfig';
				$shortcuts['Drive Space']='df -h';
				$log=getFilePath($CONFIG['websocketd_file'])."/websocketd_terminal.log";
				$shortcuts['Terminal Log']='tail -n 50 '.$log;
				$shortcuts['Kill the Terminal']="sudo pkill -f websocketd";
			}
			ksort($shortcuts);
			echo commonBuildTerminal(array('-shortcuts'=>$shortcuts));
		break;
		case 'rebuild':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-refresh"></span> Rebuild WaSQL Tables</h2>'.PHP_EOL;
			if(isset($_REQUEST['_table_'])){
            	if(dropDBTable($_REQUEST['_table_'],1)){
					$ok=createWasqlTables($_REQUEST['_table_']);
					$_REQUEST['_menu']='list';
					goto LIST_TABLE;
				}
			}
			else{
	    		clearDBCache(array('databaseTables','isDBTable'));
				$wtables=getWasqlTables();
				$tables=getDBTables();
				if(count($tables)){
					foreach($wtables as $wtable){
						if(!in_array($wtable,$tables)){
							$ok=createWasqlTables($wtable);
							echo printValue($ok);
							continue;
				        	}
				        echo checkDBTableSchema($wtable);
						}
					}
				else{
					$ok=createWasqlTables();
					echo printValue($ok);
					}
			}
			clearDBCache(array('databaseTables','isDBTable'));
			echo '<div>Complete</div>'.PHP_EOL;
    		break;
    	case 'rebuild_meta':
    		echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-refresh"></span> Rebuild Meta Tables</h2>'.PHP_EOL;
			if(isset($_REQUEST['_table_'])){
				$table=addslashes(trim($_REQUEST['_table_']));
				addMetaData($table);
			}
			$_REQUEST['_menu']='list';
			goto LIST_TABLE;
		break;
		case 'decode':
			echo adminViewPage('decode');exit;
		break;
		case 'ab':
			echo adminViewPage('apachebench');exit;
		break;
		case 'sysmon':
			adminSetPageName();
			echo includeModule('sysmon');exit;
			//echo adminViewPage('translate');exit;
		break;
		case 'translate':
			adminSetPageName();
			echo includeModule('translate');exit;
			//echo adminViewPage('translate');exit;
		break;
		case 'manual':
			echo adminViewPage('manual');exit;
			break;
		case 'profile':
			//My Profile
			$uinfo=getUserInfo($USER);
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-user"></span> Profile  <a href="#" onclick="return ajaxGet(\'/php/index.php\',\'modal\',{_action:\'editform\',_table:\'_users\',_id:'.$USER['_id'].',cp_title:\'Edit My Profile\'});" class="w_link w_lblue w_bold"><span class="icon-edit"></span> edit</a></h2>'.PHP_EOL;
			
			echo '<table class="table table-responsive table-bordered table-striped">'.PHP_EOL;
			echo buildTableTH(array('Field','Value'));
			foreach($USER as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				if(preg_match('/^(utype)$/i',$key)){continue;}
				if(is_array($val)){
					//echo buildTableTD(array("<b>{$key}</b>",printValue($val)),array('valign'=>'top'));
					continue;
					}
				if(strlen($val)==0){continue;}
				if(preg_match('/\_password$/is',$key)){
					$val=preg_replace('/./','*',$val);
                	}
				echo buildTableTD(array("<b>{$key}</b>",$val),array('valign'=>'top'));
            	}
            echo buildTableTD(array("<b>PHP SessionID</b>",session_id()),array('valign'=>'top'));
			echo buildTableTD(array("<b>API Auth Key</b>",$USER['_auth']),array('valign'=>'top'));
			$minutes=isset($CONFIG['sessionid_timeout'])?$CONFIG['sessionid_timeout']:10;
			$seconds=$minutes*60;
			echo buildTableTD(array("<b>API SessionID</b> (good for <span id=\"session_countdown\" data-behavior=\"countdown\">{$seconds}</span> seconds)",$USER['_sessionid']),array('valign'=>'top'));
            echo buildTableEnd();
			break;
		case 'settings':
			echo adminViewPage('settings');exit;
		break;
		case 'update_wasql':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-refresh"></span> Update WaSQL</h2>'.PHP_EOL;
			if(isWindows()){
				$cmd="git pull";
				$out=cmdResults($cmd);
			}
			else{
				$out=cmdResults("sudo git pull");
				if(!isset($out['stdout'])){
					$out=cmdResults("git pull");
				}
			}
			echo nl2br($out['stdout']);
		break;
		case 'about':
			//show DB Info, Current User, Link to WaSQL, Version
			global $CONFIG;
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-info-circled"></span> About WaSQL</h2>'.PHP_EOL;
			echo '<div class="row"><div class="col s12 m6">';
			echo '<table class="table striped bordered">'.PHP_EOL;
			//Database Information
			echo '<tr><th colspan="2">Config.xml Settings for '.$_SERVER['HTTP_HOST'].'</th></tr>'.PHP_EOL;
			ksort($CONFIG);
			foreach($CONFIG as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				if(preg_match('/pass$/i',$key)){
					$val=preg_replace('/./','*',$val);
				}
				echo "<tr><th align=\"left\">{$key}:</th><td>{$val}</td></tr>\n";
            	}
			//Version Information
			//$cver=curl_version();
			$versions=getAllVersions();
			echo '<tr><th colspan="2">Version Information</th></tr>'.PHP_EOL;
			foreach($versions as $key=>$version){
				if(!strlen($version)){continue;}
				echo "<tr><th align=\"left\">{$key}:</th><td>{$version}</td></tr>\n";
            	}
            //Server information
            $versions=array(
				'OS'			=> php_uname('s'),
				'Host'			=> php_uname('n'),
				'Release'		=> php_uname('r'),
				'Version'		=> php_uname('v'),
				'Machine Type'	=> php_uname('m'),
				);
			ksort($versions);
            echo '<tr><th colspan="2">Server Information</th></tr>'.PHP_EOL;
            foreach($versions as $key=>$version){
				if(!strlen($version)){continue;}
				echo "<tr><th align=\"left\">{$key}:</th><td>{$version}</td></tr>\n";
            	}
            //Loaded Extensions
         //   $exts=get_loaded_extensions();
            //Other information
            $versions=array(
            	'Current Path'	=> dirname(__FILE__),
				'Current User'	=> $USER['username'],
				'Magic Quotes GPC' => get_magic_quotes_gpc()?'On':'Off',
				'Magic Quotes Runtimie' => get_magic_quotes_runtime()?'On':'Off',
				'PHP Include Path'	=> get_include_path(),
				'PHP Script Owner'	=> get_current_user(),
				'PHP Temp Dir'		=> sys_get_temp_dir(),
				'PHP SAPI Name'		=> php_sapi_name(),
				'PHP Ini Path'		=> php_ini_loaded_file()
				);
			ksort($versions);
            echo '<tr><th colspan="2">Other Information</th></tr>'.PHP_EOL;
            foreach($versions as $key=>$version){
				if(!strlen($version)){continue;}
				echo "<tr valign=\"top\"><th align=\"left\">{$key}:</th><td>{$version}</td></tr>\n";
            	}
            echo '</table>'.PHP_EOL;
            echo '</div></div>';
			break;
		case 'stats':
			//Site Stats from the _access table
			if(!isDBTable('_access')){$ok=createWasqlTable('_access');}
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-chart-line"></span> Usage Stats</h2>'.PHP_EOL;
			echo getDBSiteStats();
			//echo printValue($stats);
			break;
		case 'tables':
			//Table Summary
			/*
				todo: TGIF - only if synchronize is set
					disallow editing on sync tables on live
					add column to see records and fields for Stage and Live
					add links to push data: live to stage, stage to live
			*/
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-table"></span> Tables</h2>'.PHP_EOL;
			echo buildFormBegin('',array('_menu'=>'tables','update'=>1));
			echo '<table class="table table-responsive table-bordered table-striped sortable">'.PHP_EOL;
			echo '<thead>'.PHP_EOL;
			echo '	<tr>'.PHP_EOL;
			echo '		<th>Action</th>'.PHP_EOL;
			echo '		<th>Tablename</th>'.PHP_EOL;
			echo '		<th>Records</th>'.PHP_EOL;
			echo '		<th>Fields</th>'.PHP_EOL;
			echo '		<th><span class="icon-group w_success w_big"></span> Group</th>'.PHP_EOL;
			echo '		<th>Description</th>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			echo '</thead>'.PHP_EOL;
			echo '<tbody>'.PHP_EOL;
			$tablegroup=array();
			$groups=array();
			$query="select tablename,tablegroup,tabledesc from _tabledata";
			$recs=getDBRecords(array('-query'=>$query));
			if(is_array($recs)){
				foreach($recs as $rec){
                	$tablegroup[$rec['tablename']]=$rec;
                	$groups[$rec['tablegroup']]=$rec['tablegroup'];
				}
			}
			unset($rec);
			$tables=getDBTables();
			$recs=array();
			foreach($tables as $table){
				$fields=getDBFields($table);
				$rec=array(
					'name'=>$table,
					'records'=>getDBCount(array('-table'=>$table)),
					'fields'=>count($fields)
					);
				if(isset($tablegroup[$table]['tablegroup'])){$rec['group']=$tablegroup[$table]['tablegroup'];}
				if(isset($tablegroup[$table]['tabledesc'])){$rec['desc']=$tablegroup[$table]['tabledesc'];}
				$recs[]=$rec;
            	}
            //echo printValue($recs);
            //get wfiles path
			$wfiles=getWfilesPath();
            foreach($recs as $rec){
				$table=$rec['name'];
				//build a list of actions
				$img='';
				$lname=strtolower($table);
				$src=getImageSrc($lname);
				if(strlen($src)){$img='<img src="'.$src.'" class="w_bottom" alt="'.$lname.'" />';}
				if(preg_match('/^\_/',$table)){
					//wasql table - do not allow people to change group
                	echo buildTableTD(array(
                		tableOptions($table,array('-format'=>'none','-options'=>'list,properties','-notext'=>1)),
						'<a class="w_link w_block" href="/'.$PAGE['name'].'?_menu=list&_table_='.$rec['name'].'">'.$img.' '.$rec['name'].'</a>',
						$rec['records'],
						$rec['fields'],
						'WaSQL',
						'Internal WaSQL Table',
						));
				}
				else{
					if(!isset($rec['group'])){$rec['group']='';}
					if(!isset($rec['desc'])){$rec['desc']='';}
                	echo buildTableTD(array(
                		tableOptions($table,array('-format'=>'none','-options'=>'list,properties','-notext'=>1)),
						'<a class="w_link w_block" href="/'.$PAGE['name'].'?_menu=list&_table_='.$rec['name'].'">'.$img.' '.$rec['name'].'</a>',
						$rec['records'],
						$rec['fields'],
						'<input type="text" name="g_'.$rec['name'].'" class="form-control" style="width:100%" maxlength="50" value="'.$rec['group'].'">',
						'<input type="text" name="d_'.$rec['name'].'" class="form-control" style="width:100%" maxlength="255" value="'.$rec['desc'].'">',
						));
				}
            	}
            echo '</tbody>'.PHP_EOL;
			echo buildTableEnd();
			echo buildFormSubmit('Save Changes','','','icon-save');
			echo buildFormEnd();
			break;
		case 'summary':
		case 'charset':
			//Table Summary
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-properties"></span> Table Properties</h2>'.PHP_EOL;
			$cmessage='';
			if(isset($_REQUEST['_charset']) && strlen($_REQUEST['_charset'])){
				$cmessage .= '<h3>'.$_REQUEST['_charset'].' conversion results:</h3><hr>'.PHP_EOL;
				$tables=getDBTables();
				foreach($tables as $table){
					$runsql='ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.$_REQUEST['_charset'];
					$cmessage .= 'Converting Table '.$table.'...'.PHP_EOL;
					$ck=executeSQL($runsql);
					if(isset($ck['result'])){
						if($ck['result'] != 1){$cmessage .= "FAILED: {$ck['query']}<br>\n";}
						else{$cmessage .= 'SUCCESS<br>'.PHP_EOL;}
						}
					elseif($ck !=1){$cmessage .= "FAILED: {$ck}<br>\n";}
					else{$cmessage .= 'SUCCESS<br>'.PHP_EOL;}
	            	}
	            $runsql='ALTER DATABASE '.$CONFIG['dbname'].' CHARACTER SET '.$_REQUEST['_charset'];
				$cmessage .= 'Converting Database '.$CONFIG['dbname'].'...'.PHP_EOL;
				$ck=executeSQL($runsql);
				if(isset($ck['result'])){
					if($ck['result'] != 1){$cmessage .= "FAILED: {$ck['query']}<br>\n";}
					else{$cmessage .= 'SUCCESS<br>'.PHP_EOL;}
					}
				elseif($ck !=1){$cmessage .= "FAILED: {$ck}<br>\n";}
				else{$cmessage .= 'SUCCESS<br>'.PHP_EOL;}
		        }
			$charsets=getDBCharsets();
			$current_charset=getDBCharset();
			//echo '<div class="w_lblue w_bold">Current Character Set: '.$current_charset.'</div>'.PHP_EOL;
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'">Available Character Sets</h2>'.PHP_EOL;
			echo '		<form method="POST" name="charset_form" action="/'.$PAGE['name'].'" class="w_form">'.PHP_EOL;
			echo '			<input type="hidden" name="_menu" value="charset">'.PHP_EOL;
			echo '			<div class="w_flexgroup" style="max-width:400px;margin-bottom:10px;">'.PHP_EOL;
			echo buildFormSelect('_charset',$charsets,array('value'=>$current_charset));
			echo buildFormSubmit('Convert');
			echo '			</div>'.PHP_EOL;
			echo '		</form>'.PHP_EOL;
			echo $cmessage;
			echo databaseListRecords(array(
				'-query'				=>	"show table status",
				'-hidesearch'				=> 1,
				'-translate'	=> 1,
				'-tableclass'			=> "table table-responsive table-bordered table-striped",
				'name_href'				=> "/php/admin.php?_menu=list&_table_=%name%",
				'data_length_eval'		=>	"return verboseSize(%data_length%);",
				'data_length_align'		=> 'right',
				'index_length_eval'		=>	"return verboseSize(%index_length%);",
				'avg_row_length_eval'	=>	"return verboseSize(%avg_row_length%);",
				'avg_row_length_align'	=> 'right',
				'index_length_align'	=> 'right',
				'-fields'				=> 'name,engine,version,row_format,rows,avg_row_length,data_length,index_length,auto_increment,create_time,collation'

			));
			break;
		case 'add':
			if(isset($_REQUEST['_table_'])){
				if($_REQUEST['_table_']=='_new_' || isset($_REQUEST['_schema'])){
					//add new table
					$error=0;
					if(isset($_SESSION['admin_errors']) && is_array($_SESSION['admin_errors']) && count($_SESSION['admin_errors'])){
						echo '<div class="w_padding w_left">'.PHP_EOL;
						echo '	<div class="w_bold"><span class="icon-warning w_danger w_bold"></span> Error Adding Table:</div>'.PHP_EOL;
						foreach($_SESSION['admin_errors'] as $adderror){
							echo "	<div class=\"w_marginleft w_red w_bold\"> - {$adderror}</div>\n";
                    	}
                    	echo '</div>'.PHP_EOL;
                    	echo '<br clear="both">'.PHP_EOL;
						$error=1;
						$_SESSION['admin_errors']=array();
					}
					//echo printValue($_REQUEST);
					echo buildTableBegin(2,0);
					echo '<tr valign="top"><td>'.PHP_EOL;
					echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-plus"></span> Add New Table</h2>'.PHP_EOL;
					echo '		<form method="POST" name="new_table" action="/'.$PAGE['name'].'" class="w_form" onSubmit="return submitForm(this);">'.PHP_EOL;
					echo '			<input type="hidden" name="_menu" value="add">'.PHP_EOL;
					$value=$error==1?$_REQUEST['_table_']:'';
					echo '			<b>Table Name:</b> <input type="text" data-required="1" data-requiredmsg="Enter a table name" class="form-control" maxlength="150" name="_table_" value="'.$value.'" onFocus="this.select();"><br />'.PHP_EOL;
					//echo '			<img src="/wfiles/iconsets/16/group.png" border="0"> Table Group: <input type="text"  style="width:310px;" maxlength="150" name="tablegroup" value="'.$value.'" onFocus="this.select();"><br />'.PHP_EOL;
					//echo '			<img src="/wfiles/iconsets/16/info.png" border="0"> Table Desc: <input type="text" style="width:315px;" maxlength="150" name="tabledesc" value="'.$value.'" onFocus="this.select();"><br />'.PHP_EOL;
					echo '			<div class="w_small">Enter fields below (i.e. firstname varchar(255) NOT NULL)</div>'.PHP_EOL;
					$value=$error==1?$_REQUEST['_schema']:'';
					echo '			<textarea data-required="1" data-behavior="sqleditor" data-requiredmsg="Enter table fields" name="_schema" style="width:450px;height:400px;">'.$value.'</textarea>'.PHP_EOL;
					echo '			<div class="w_padtop"><button type="submit" class="btn">Create</button></div>'.PHP_EOL;
					echo '		</form>'.PHP_EOL;
					echo buildOnLoad('document.new_table._table_.focus();');
					echo '</td><td>'.PHP_EOL;
					//reference: http://www.htmlite.com/mysql003.php
					echo adminListDataTypes();
					echo '</td></tr></table>'.PHP_EOL;
                }
				else{
					if(isset($CONFIG['dbname_stage'])){
                    	$xtables=adminGetSynchronizeTables($CONFIG['dbname_stage']);
                    	if(in_array($_REQUEST['_table_'],$xtables)){
							echo '<div class="w_bold">'.PHP_EOL;
							echo '	<span class="icon-warning w_danger w_bold"></span>'.PHP_EOL;
							echo '	"'.$_REQUEST['_table_'].'" is a synchronize table. New records must be added on the staging site.'.PHP_EOL;
							echo '</div>'.PHP_EOL;
						}
						else{
							echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
							echo '<div class="w_lblue w_bold w_bigger">Add New Record to '.$_REQUEST['_table_'].' table.</div>'.PHP_EOL;
							$addopts=array(
								'-action'=>'/php/admin.php',
								'-table'=>$_REQUEST['_table_'],
								'_table_'=>$_REQUEST['_table_'],
								'_menu'=>"list",
								'_sort'=>$_REQUEST['_sort'],
								'_start'=>$_REQUEST['_start']
							);
							if($addopts['-table']=='_models'){$addopts['mtype_defaultval']='';}
							echo addEditDBForm($addopts);
						}
					}
					else{
						switch(strtolower($_REQUEST['_table_'])){
							case '_pages':
								$ok=adminDefaultPageValues();
							break;
							case '_templates':
								$ok=adminDefaultTemplateValues();
							break;
							case '_reports':
								if(isset($_REQUEST['sqlprompt']) && strlen($_REQUEST['sqlprompt_command'])){
									$_REQUEST['query']=$_REQUEST['sqlprompt_command'];
								}
							break;
						}
						echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
						echo '<div class="w_lblue w_bold w_bigger">Add New Record to '.$_REQUEST['_table_'].' table.</div>'.PHP_EOL;
						$addopts=array(
							'-action'=>'/php/admin.php',
							'-table'=>$_REQUEST['_table_'],
							'_table_'=>$_REQUEST['_table_'],
							'_menu'=>"list"
						);
						if(isset($_REQUEST['_sort'])){$addopts['_sort']=$_REQUEST['_sort'];}
						if(isset($_REQUEST['_start'])){$addopts['_start']=$_REQUEST['_start'];}
						if($addopts['-table']=='_models'){$addopts['mtype_defaultval']='';}
						echo addEditDBForm($addopts);
					}
				}
            }
			break;
		case 'addmultiple':
			echo buildTableBegin(2,0);
			echo '<tr valign="top"><td>'.PHP_EOL;
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-table-add"></span> Add Multiple Tables</h2>'.PHP_EOL;
			echo '	<form method="POST" name="mform" action="/'.$PAGE['name'].'" class="w_form" onSubmit="ajaxSubmitForm(this,\'modal\');return false;">'.PHP_EOL;
			echo '		<input type="hidden" name="_menu" value="addmultiple">'.PHP_EOL;
			echo '		<div class="w_smallest">Enter tablename followed by fields for that table tabbed in. See example on right.</div>'.PHP_EOL;
			$val=isset($_REQUEST['_schema'])?$_REQUEST['_schema']:'';
			echo '		<textarea data-behavior="sqleditor" data-required="1" name="_schema" style="width:450px;height:400px;">'.$val.'</textarea>'.PHP_EOL;
			echo '		<div class="w_padtop"><button type="submit" class="btn">Create</button>'.PHP_EOL;
			echo '	</form>'.PHP_EOL;
			echo '</td><td>'.PHP_EOL;
			//reference: http://www.htmlite.com/mysql003.php
			//Text Types
			echo '<div class="w_lblue w_bold"><span class="icon-info"></span> Sample Entry</div>'.PHP_EOL;
			echo '<div style="margin-left:25px;"><pre>'.PHP_EOL;
			echo 'employees'.PHP_EOL;
			echo '	name varchar(55)'.PHP_EOL;
			echo '	age int'.PHP_EOL;
			echo '	hiredate datetime'.PHP_EOL;
			echo 'companies'.PHP_EOL;
			echo '	name varchar(100)'.PHP_EOL;
			echo '	url varchar(255)'.PHP_EOL;
			echo '	phone varchar(15)'.PHP_EOL;
			echo '	email varchar(255)'.PHP_EOL;
			echo '</pre></div>'.PHP_EOL;
			echo adminListDataTypes();
			
			echo '</td></tr></table>'.PHP_EOL;
			break;
		case 'drop':
			echo $dropResult;
			break;
		case 'edit':
			if(isset($_REQUEST['_table_']) && isNum($_REQUEST['_id'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">Edit Record #'.$_REQUEST['_id'].' in '.$_REQUEST['_table_'].' table.</div>'.PHP_EOL;
				$rec=getDBRecord(array(
					'-table'=>$_REQUEST['_table_'],
					'_id'=>$_REQUEST['_id'],
					'-relate'=>array('_euser'=>'_users','_cuser'=>'_users'),
					'-fields'=>'_cdate,_cuser,_edate,_euser'
				));
				echo '<div class="w_lblue w_smaller" style="margin-left:20px;">'.PHP_EOL;
				if(isset($rec['_cuser_ex']['firstname'])){
					$cdate=date('l F j, Y, g:i a',$rec['_cdate_utime']);
                	echo "Created by {$rec['_cuser_ex']['firstname']} {$rec['_cuser_ex']['lastname']} on {$cdate}";
				}
				elseif(isNum($rec['_cdate_utime'])){
					$cdate=date('l F j, Y, g:i a',$rec['_cdate_utime']);
                	echo "Created on {$cdate}";
				}
				if(isset($rec['_euser_ex']['firstname'])){
					$edate=date('l F j, Y, g:i a',$rec['_edate_utime']);
                	echo "  - | - Edited by {$rec['_euser_ex']['firstname']} {$rec['_euser_ex']['lastname']} on {$edate}";
				}
				echo '</div>'.PHP_EOL;
				$menu=isset($_REQUEST['_menu2'])?$_REQUEST['_menu2']:'list';
				$addopts=array(
					'-action'=>'/php/admin.php',
					'-table'=>$_REQUEST['_table_'],
					'_table_'=>$_REQUEST['_table_'],
					'_menu'=>$menu,
					'_id'=>$_REQUEST['_id']
				);
				if(isset($_REQUEST['_sort'])){$addopts['_sort']=$_REQUEST['_sort'];}
				if(isset($_REQUEST['_start'])){$addopts['_start']=$_REQUEST['_start'];}

				echo addEditDBForm($addopts);
            }
			break;
		case 'phpinfo':
			ob_start();
			phpinfo();
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
			break;
		case 'pdo':
			if(extension_loaded('pdo')){
				echo 'pdo_mysql is loaded<br>'.PHP_EOL;
				$funcs=get_extension_funcs('pdo');
				foreach($funcs as $func){
                	$tmp=&$func();
                	echo $func.printValue($tmp);
				}
			}
			else{
            	echo 'pdo_mysql is not loaded';
			}
			break;
		case 'phpcredits':
			ob_start();
			phpcredits();
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
			break;
		case 'list':
LIST_TABLE:
			if(isset($_REQUEST['_table_'])){
				//params
                $parts=array();
			    foreach($_REQUEST as $key=>$val){
					if(!preg_match('/^\_(sort|table_|start|search)$/i',$key)){continue;}
					$parts[$key]=$val;
			    	}
			    $parts['_menu']='edit';
			    $idurl=buildUrl($parts) . '&_id=%_id%';
				$recopts=array(
					'_menu'			=>$_REQUEST['_menu'],
					'-tableclass'	=> "table table-responsive table-bordered table-striped",
					'-bulkedit'		=> 1,
					'-translate'	=> 1,
					'-export'		=> 1,
					'_table_'=>$_REQUEST['_table_'],
					'-table'=>$_REQUEST['_table_'],
					'-formaction'=>'/php/admin.php',
					'_id_href'=>'/php/admin.php?'.$idurl,
					'-sorting'=>1,
					'-theadclass'=>'w_pointer',
					'-editfields'=>'*'
				);
				//get listfields from tablemeta
				$tinfo=getDBTableInfo(array('-table'=>$_REQUEST['_table_']));
				if(!empty($tinfo['listfields'])){
					$recopts['-listfields']=$tinfo['listfields'];
					//add _id if it does not exist
					if(!in_array('_id',$recopts['-listfields'])){
						array_unshift($recopts['-listfields'],'_id');
					}
				}
				if(!empty($tinfo['sortfields'])){
					if(is_array($tinfo['sortfields'])){
						$tinfo['listfields']=implode(', ',$tinfo['sortfields']);
					}
					$recopts['-order']=$tinfo['listfields'];
				}
				//table Options header
                echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">List Records in ';
				switch(strtolower($_REQUEST['_table_'])){
					case '_cron':echo '<span class="icon-cron w_success w_big"></span>';break;
					case '_cronlog':echo '<span class="icon-cronlog w_success w_big"></span>';break;
					case '_users':echo '<span class="icon-users w_info w_big"></span>';break;
					case '_reports':echo '<span class="icon-chart-pie w_big"></span>';break;
					case '_templates':echo '<span class="icon-file-docs w_big"></span>';break;
					case '_pages':echo '<span class="icon-file-doc w_big"></span>';break;
					case '_queries':echo '<span class="icon-database-empty w_danger w_big"></span>';break;
					default:
						$img=getImageSrc(strtolower($_REQUEST['_table_']));
						if(strlen($img)){
							echo  '<img src="'.$img.'" class="w_bottom" alt="" /> ';
				        }
				    break;
				}
				echo $_REQUEST['_table_'].' table.'.PHP_EOL;
				if(strtolower($_REQUEST['_table_'])=='_cron'){
					echo ' <a href="/php/admin.php?_menu=list&_table_=_cronlog" class="icon-cronlog w_link w_small w_grey"> View Logs</a>'.PHP_EOL;
				}
				elseif(strtolower($_REQUEST['_table_'])=='_cronlog'){
					echo ' <a href="/php/admin.php?_menu=list&_table_=_cron" class="icon-cron w_link w_small w_grey"> View Crons</a>'.PHP_EOL;
				}
				echo '</div>'.PHP_EOL;
				//special options for some tables
				switch(strtolower($_REQUEST['_table_'])){
                	case '_cron':
                		//format the run_date
						$recopts['run_date_dateage']=1;
						$recopts['run_date_displayname']="Last Run";
						//format the frequency
						$recopts['frequency_eval']="\$t=%frequency%*60;return 'every '.verboseTime(round(\$t,0));";
						$recopts['frequency_align']="right";
						//format the run length
						$recopts['run_length_eval']="\$t='%run_length%';return verboseTime(round(\$t,0));";
						$recopts['run_length_align']="right";
						//format active
						$recopts['active_checkmark']=1;
						$recopts['active_align']="center";
						$recopts['-fields']="_id,name,active,running,frequency,run_date,run_length,run_cmd,run_as";
						$_REQUEST['filter_field']='name';
                	break;
                	case '_cronlog':
                		$_REQUEST['filter_field']='name';
                	break;
                	case '_forms':
                		$_REQUEST['filter_field']='formname';
                	break;
                	case '_history':
                		$_REQUEST['filter_field']='tablename';
                	break;
                	case '_html_entities':
                		$recopts['entity_name_eval']="return str_replace('&','&amp;','%entity_name%');";
                		$recopts['entity_name_class']='text-right';
                		$recopts['entity_number_eval']="return str_replace('&','&amp;',\"%entity_number%\");";
                		$recopts['entity_number_class']='text-right';
                		$recopts['-listfields']=array('_id','category','description','entity_name','entity_number','display');
                		$recopts['display_eval']="return \"%entity_number%\";";
                		$recopts['display_class']='text-right';
                		$_REQUEST['filter_field']='description';
                	break;
                	case '_pages':
                		$recopts['_template_relate']="id,name";
                		$recopts['-relate']=array('_template'=>'_templates');
                		$_REQUEST['filter_field']='name';
                	break;
                	case '_templates':
                		$_REQUEST['filter_field']='name';
                	break;
                	case '_tabledata':
                		$_REQUEST['filter_field']='tablename';
                	break;
                	case '_fielddata':
                		$_REQUEST['filter_field']='tablename';
                	break;
                	case '_reports':
                		$_REQUEST['filter_field']='name';
                	break;
                	case '_users':
                		$_REQUEST['filter_field']='lastname';
                	break;
                	case '_queries':
                		global $SETTINGS;
						$recopts['run_length_align']="right";
						$recopts['-relate']=array('page_id'=>'_pages','user_id'=>'_users');
						$recopts['page_id_relate']='name';
						$recopts['user_id_relate']='firstname,lastname';
						$recopts['page_id_displayname']='Page Name';
						$recopts['page_id_align']='right';
						$recopts['tablename_align']='right';
						$recopts['tablename_href']="?_menu=indexes&_table_=%tablename%";
						$recopts['-row_actions']=array(
							array('<a href="#" onclick="return ajaxGet(\'/php/index.php\',\'centerpopIDX\',\'ajaxid=centerpopIDX&_queryid_=%_id%&explain=1\');"><span class="icon-optimize w_gold w_big" alt="Show Indexes" data-tooltip="Explain Query"></span></a>','function','getDBRecords'),
							array('<a href="#" onclick="return ajaxGet(\'/php/index.php\',\'centerpopIDX\',\'ajaxid=centerpopIDX&_queryid_=%_id%&view=1\');"><img src="/wfiles/iconsets/16/sql.png" data-tooltip="View This Query" class="w_middle" alt="view query" /></a>')
						);
						echo '<div class="w_small w_lblue" style="margin-left:20px;"><a class="w_lblue w_link" href="?_menu=settings">Query Settings:</a> Days: '.$SETTINGS['wasql_queries_days'].', Time:'.$SETTINGS['wasql_queries_time'].' seconds</div>'.PHP_EOL;
                	break;
				}
				if(isset($_REQUEST['add_result']['error'])){
					echo '<div class="w_tip w_pad w_border"><span class="icon-warning w_danger w_big"></span><b class="w_red"> Add Failed:</b> '.printValue($_REQUEST['add_result']).'</div>'.PHP_EOL;
                	}
                elseif(isset($_REQUEST['edit_result']['error'])){
					echo '<div class="w_tip w_pad w_border"><span class="icon-warning w_danger w_big"></span><b class="w_red"> Edit Failed:</b>: '.printValue($_REQUEST['edit_result']).'</div>'.PHP_EOL;
                	}
                echo '<div style="padding:15px;">'.PHP_EOL;
                //echo printValue($recopts);
				echo databaseListRecords($recopts);
				//echo printValue($_REQUEST);
				echo '</div>'.PHP_EOL;
            	}
			break;
		case 'synchronize':
			echo adminViewPage('synchronize');exit;
		break;
		case 'schema':
			if(isset($_REQUEST['_table_'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/schema.gif" alt="schema" /> Schema for '.$_REQUEST['_table_'].'</div>'.PHP_EOL;
				if(isset($_REQUEST['_schema'])){
					$lines=preg_split('/[\r\n]+/',trim($_REQUEST['_schema']));
					//common fields to all wasql tables
					$cfields=array(
						'_id'	=> databasePrimaryKeyFieldString(),
						'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
						'_cuser'=> "int NOT NULL",
						'_edate'=> databaseDataType('datetime')." NULL",
						'_euser'=> "int NULL",
						);
					switch(strtolower($_REQUEST['_table_'])){
						case '_forms':
							$cfields['_formname']="varchar(100) NOT NULL";
							$cfields['_xmldata']="text NOT NULL";
							break;
						case '_users':
							$cfields['_adate']=databaseDataType('datetime')." NULL";
							$cfields['_apage']="INT NULL";
							$cfields['_aip']="char(45) NULL";
							$cfields['_env']="text NULL";
							$cfields['_sid']="varchar(150) NULL";
							break;
						case '_templates':
							$cfields['_adate']=databaseDataType('datetime')." NULL";
							break;
						case '_pages':
							$cfields['_adate']=databaseDataType('datetime')." NULL";
							$cfields['_aip']="char(45) NULL";
							$cfields['_amem']=databaseDataType('bigint')." NULL";
							$cfields['_auser']="integer NULL";
							$cfields['_env']="text NULL";
							$cfields['_template']="integer NOT NULL Default 1";
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
				        $ok = alterDBTable($_REQUEST['_table_'],$fields);
				        echo "alterDBTable {$ok}";
                		}
                	}
				$list=getDBSchema(array($_REQUEST['_table_']));
				echo '<table class="w_pad">'.PHP_EOL;
				echo '<tr valign="top"><td>'.PHP_EOL;
				echo databaseListRecords(array(
					'_menu'			=>$_REQUEST['_menu'],
					'-tableclass'	=> "table table-responsive table-bordered table-striped",
					'_table_'		=>$_REQUEST['_table_'],
					'-list'			=>$list
				));
				echo '</td><td>'.PHP_EOL;
				echo buildFormBegin('',array('_menu'=>"schema",'_table_'=>$_REQUEST['_table_']));
				echo '<textarea name="_schema" style="width:300px;height:400px;">'.PHP_EOL;
				foreach($list as $field){
					if(preg_match('/^\_/',$field['field'])){continue;}
					$type=$field['type'];
					if($field['null']=='NO'){$type .= ' NOT NULL';}
					else{$type .= ' NULL';}
					if($field['key']=='PRI'){$type .= ' Primary Key';}
					elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
					if(strlen($field['default'])){$type .= ' Default '.$field['default'];}
					if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
					echo "{$field['field']} {$type}\r\n";

	                }
				echo '</textarea><br />'.PHP_EOL;
				echo buildFormSubmit("Modify");
				echo buildFormEnd();
				echo '</td></tr></table>'.PHP_EOL;
				}
			else{
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/schema.gif" alt="all schema" /> Schema for All Tables</div>'.PHP_EOL;
				$list=getDBSchema();
				echo databaseListRecords(array(
					'_menu'			=>$_REQUEST['_menu'],
					'-tableclass'	=> "table table-responsive table-bordered table-striped",
					'_table_'		=>$_REQUEST['_table_'],
					'-list'			=>$list
				));
			}

			break;
		case 'truncate':
			if(isset($_REQUEST['_table_'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">Truncate '.$_REQUEST['_table_'].' table.</div>'.PHP_EOL;
			 	echo truncateDBTable($_REQUEST['_table_']);
				}
			break;
		case 'indexes':
			if(isset($_REQUEST['_table_'])){
				//echo printValue($_REQUEST);
				//exit;
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				//create index request?
				if(isset($_REQUEST['_indexfields_']) && is_array($_REQUEST['_indexfields_']) && count($_REQUEST['_indexfields_'])){
					$ok=addDBIndex(array(
						'-table'	=> $_REQUEST['_table_'],
						'-fields' 	=> $_REQUEST['_indexfields_'],
						'-fulltext'	=> isset($_REQUEST['fulltext'])?true:false,
						'-unique'	=> isset($_REQUEST['unique'])?true:false
					));
					if(stringContains($ok,'error')){
                    	echo '<div class="w_tip w_pad w_border">'.$ok.'</div>'.PHP_EOL;
					}
                }
                elseif(isset($_REQUEST['_index_drop']) && strlen($_REQUEST['_index_drop'])){
					$ok=dropDBIndex(array(
						'-table'	=> $_REQUEST['_table_'],
						'-name' 	=> $_REQUEST['_index_drop'],
					));
					if(stringContains($ok,'error')){
                    	echo '<div class="w_tip w_pad w_border">'.$ok.'</div>'.PHP_EOL;
					}
                }
                //show indexes
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/indexes.gif" alt="indexes" /> Indexes for ';
				$img=getImageSrc(strtolower($_REQUEST['_table_']));
				if(strlen($img)){
					echo  '<img src="'.$img.'" class="w_bottom" alt="" /> ';
		        }
				echo $_REQUEST['_table_'].'</div>'.PHP_EOL;
				$list=getDBIndexes(array($_REQUEST['_table_']));
				$cnt=count($list);
				for($i=0;$i<$cnt;$i++){
					if(preg_match('/Primary/i',$list[$i]['key_name'])){
						$list[$i]['_id']='';
						continue;
					}
					$list[$i]['_id']='<a title="Drop index" onclick="return confirm(\'Drop index for column \\\''.$list[$i]['key_name'].'\\\'?\');" href="/'.$PAGE['name'].'?_menu=indexes&_table_='.$_REQUEST['_table_'].'&_index_drop='.$list[$i]['key_name'].'"><img src="/wfiles/drop.gif" alt="drop" /></a>';
                }
				//echo printValue($list);exit;
				echo buildFormBegin('',array('_menu'=>"indexes",'_table_'=>$_REQUEST['_table_']));
				$used=array();
				$tfields=getDBFields($_REQUEST['_table_'],1);
				foreach($list as $index){$used[$index['column_name']]=1;}
				foreach($tfields as $tfield){
					$fields[]=$tfield;
				}
				echo '<div><b>Select fields to index</b></div>'.PHP_EOL;
				echo '<div style="margin-left:30px;">'.PHP_EOL;
				$opts=array();
				foreach($fields as $field){
                	$opts[$field]=$field;
				}
				echo buildFormCheckbox('_indexfields_',$opts,array('width'=>6));
				echo '</div>'.PHP_EOL;
				echo '<input type="checkbox" name="fulltext" value="1" id="fulltextid"><label for="fulltextid"> FullText</label>'.PHP_EOL;
				echo '<input type="checkbox" name="unique" value="1" id="uniqueid"><label for="uniqueid"> Unique</label>'.PHP_EOL;
				echo buildFormSubmit("Create Index");
				echo buildFormEnd();
				//echo printValue($_REQUEST);
			}
			else{
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/indexes.gif" alt="all indexes" /> Indexes for All Tables</div>'.PHP_EOL;
				$list=getDBIndexes();
			}
			echo databaseListRecords(array(
				'_menu'			=>$_REQUEST['_menu'],
				'_table_'		=>$_REQUEST['_table_'],
				'-tableclass'	=>"table table-striped table-bordered",
				'-list'			=>$list,

			));
			//echo printValue($list);
			break;
		case 'postedit':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-postedit"></span> PostEdit Manager</h2>'.PHP_EOL;
			echo '<div style="width:800px;">'.PHP_EOL;
			echo '<p><b>PostEdit Manager</b> is a php application that WaSQL uses to create pages and templates into a <b>PostEdit</b> folder on your local hard drive.'.PHP_EOL;
			echo 'This allows you to use any editor you wish to update your pages and templates.'.PHP_EOL;
			echo 'When the <b>PostEdit Manager</b> detects a file changed it checks for syntax and commits your changes to your WaSQL database.'.PHP_EOL;
			echo '</p>'.PHP_EOL;
			echo '<p>'.PHP_EOL;
			echo '	If you have WaSQL on your local computer then the postedit program is already installed.  If not, you will need to download it via git. '.PHP_EOL;
			echo '</p>'.PHP_EOL;
			echo '<p>'.PHP_EOL;
			echo '</p><p>'.PHP_EOL;
			echo '<b>PostEdit Manager</b> requires a configuration file called <b>postedit.xml</b>.'.PHP_EOL;
			echo 'This file contains authentication information for each domain/website you want to connect to.'.PHP_EOL;
			echo 'Add the following entry to postedit.xml found in the postedit directory to authenticate to this domain as the current user:'.PHP_EOL;
			echo '<pre><xmp>'.PHP_EOL;
			$alias=preg_replace('/\.(com|net|org)$/i','',$_SERVER['UNIQUE_HOST']);
			echo '<host'.PHP_EOL;
			echo '	name="'.$_SERVER['HTTP_HOST'].'"'.PHP_EOL;
			echo '	alias="'.$alias.'"'.PHP_EOL;
			echo '	apikey="'.$USER['apikey'].'"'.PHP_EOL;
			echo '	username="'.$USER['username'].'"'.PHP_EOL;
			echo '	groupby="name"'.PHP_EOL;
			if(!isSecure()){
				echo '	insecure="1"'.PHP_EOL;
			}
			echo '/>'.PHP_EOL;
			echo '</xmp></pre>'.PHP_EOL;
			echo 'Possible host attributes and their explanations are as follows (red attributes are required):'.PHP_EOL;
			echo '<ul>'.PHP_EOL;
			echo '	<li><b class="w_red">name</b> - this is the hostname you want to connect to. It should correlate to the host name in yourr config.xml file</li>'.PHP_EOL;
			echo '	<li><b class="w_red">username</b> - the username to authenticate as. This must be a valid username for this domain.</li>'.PHP_EOL;
			echo '	<li><b class="w_red">apikey</b> - the apikey for the authenticating user. This is found in the user profile menu after logging in.'.PHP_EOL;
			echo '		<ul>'.PHP_EOL;
			echo '			<li> Changing your username or password will change your apikey.'.PHP_EOL;
			echo '		</ul>'.PHP_EOL;
			echo '	</li>'.PHP_EOL;
			echo '	<li><b>alias</b> - This gives a more friendly alias to the hostname. For instance, dev.wasql.com may have an alias of wasql.</li>'.PHP_EOL;
			echo '	<li><b>insecure</b> - If your website is not HTTPS then set this attribute to 1.</li>'.PHP_EOL;
			echo '	<li><b>tables</b> - tables to download locally so you can modify them. This defaults to "_pages,_templates".</li>'.PHP_EOL;
			echo '</ul>'.PHP_EOL;
			echo '</p><p>'.PHP_EOL;
			echo '</p><br /><br /><br /><br />'.PHP_EOL;
			echo '</div>'.PHP_EOL;
		break;
		case 'properties':
			if(!isset($_REQUEST['_table_']) || !strlen($_REQUEST['_table_'])){echo "No Table";break;}
			$currentTable=$_REQUEST['_table_'];
			$tinfo=getDBTableInfo(array('-table'=>$currentTable,'-fieldinfo'=>1));
			//exit;
			$fields=array_keys($tinfo['fieldinfo']);
			sort($fields);
			echo tableOptions($currentTable,array('-format'=>'table','-notext'=>1));
			$formfields=array('width','height','inputmax','required','mask','editlist');
			//Process do request
			$dirty=0;
			if(isset($_REQUEST['do']) && strtolower($_REQUEST['do'])=='save changes'){
                //Tabledata edits
               // echo printValue($_REQUEST);
                $fields=array('websockets','synchronize','tablegroup','tabledesc','listfields','sortfields','formfields','listfields_mod','sortfields_mod','formfields_mod');
                $editopts=array();
                foreach($fields as $field){
					$val=array2String($_REQUEST[$field]);
					$cval=array2String($tinfo[$field]);
					//echo "field:{$field},val:{$val},cval:{$cval}<br />";
					switch($field){
                    	case 'websockets':
                    	case 'synchronize':
                    		//checkbox fields
							$editopts[$field]=strlen($val)?$val:0;
                    	break;
                    	default:
                    		if($cval != $val){
                            	$editopts[$field]=$val;
							}
                    	break;
					}
				}
				//echo printValue($editopts);
                if(count($editopts)){
                	$editopts['-table']='_tabledata';
					$erec=getDBRecord(array(
						'-table'=>'_tabledata',
						'tablename'=>$currentTable
					));
					if(isset($erec['_id'])){
						$editopts['-where']="_id = '{$erec['_id']}'";
						$ok=editDBRecord($editopts);
					}
					else{
						$editopts['tablename']=$currentTable;
						$ok=addDBRecord($editopts);
						}
					//echo $ok . printValue($editopts);
					$dirty++;
					}
            	}
            elseif(isset($_REQUEST['_schema'])){
				//common fields to all wasql tables
				$ok=updateDBSchema($currentTable,trim($_REQUEST['_schema']));
                }
            //clear the cache
            $databaseCache=array();
			$tinfo=getDBTableInfo(array('-table'=>$currentTable,'-fieldinfo'=>1));
			$fields=array_keys($tinfo['fieldinfo']);
			sort($fields);
			//echo printValue($_REQUEST);
			unset($_REQUEST);
            echo '<div class="w_lblue w_bold w_bigger"><span class="icon-properties w_danger"></span> Table Properties for ';
			$img=getImageSrc(strtolower($currentTable));
				if(strlen($img)){
					echo  '<img src="'.$img.'" class="w_bottom" alt="" /> ';
		        }
			echo $currentTable.' Table</div>'.PHP_EOL;
			echo '<table class="w_nopad"><tr valign="top"><td>'.PHP_EOL;
			echo '<table class="table table-responsive table-striped table-bordered table-hover">'.PHP_EOL;
			echo '<tr><th colspan="7"><span class="icon-database-empty"></span> Database Properties</th><th colspan="8"><span class="icon-newspaper"></span> META Properties</th></tr>'.PHP_EOL;
			echo '	<tr>'.PHP_EOL;
			echo '		<th class="w_smallest">Name</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Type</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Len</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Null</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Key</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Val</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Extra/Comment</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Name</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Type</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Width</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Height</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Max</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Req</th>'.PHP_EOL;
			echo '		<th class="w_smallest">Mask</th>'.PHP_EOL;
			echo '		<th class="w_smallest">List</th>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			$row=0;
			foreach($fields as $field){
				$row++;
				$frec=getDBRecord(array('-table'=>"_fielddata",'tablename'=>$currentTable,'fieldname'=>$field));
				$id=is_array($frec)?$frec['_id']:0;
				if(isset($frec['_id'])){
					//edit
					$onclick="return ajaxGet('/php/index.php','modal',{title:'Edit META for {$currentTable}.{$field} - {$id}',_action:'editform','_table':'_fielddata',_id:{$id},_menu:'properties',fieldname:'{$field}',tablename:'{$currentTable}',_table_:'{$currentTable}'});";
				}
				else{
					//new
					$onclick="return ajaxGet('/php/index.php','modal',{title:'Add New Record',_action:'editform','_table':'_fielddata',_id:{$id},_menu:'properties',fieldname:'{$field}',tablename:'{$currentTable}',_table_:'{$currentTable}'});";
				}
				echo '	<tr class="w_pointer" onclick="'.$onclick.'">'.PHP_EOL;

				$extras=array();
				if(isset($tinfo['fieldinfo'][$field]['_dbextra']) && strlen($tinfo['fieldinfo'][$field]['_dbextra'])){
					$extras[]=$tinfo['fieldinfo'][$field]['_dbextra'];
				}
				if(isset($tinfo['fieldinfo'][$field]['_dbcomment']) && strlen($tinfo['fieldinfo'][$field]['_dbcomment'])){
					$extras[]=$tinfo['fieldinfo'][$field]['_dbcomment'];
				}
				$extra=implode('/',$extras);
				if(preg_match('/^\_/',$field)){
					echo '		<td class="w_gray w_smaller">'.$field.'</td>'.PHP_EOL;
					}
				else{
					echo '		<td class="w_lblue w_bold w_smaller">'.$field.'</td>'.PHP_EOL;
					}
				$val=isset($tinfo['fieldinfo'][$field]['_dbtype'])?$tinfo['fieldinfo'][$field]['_dbtype']:'';
				echo '		<td class="w_gray w_smaller">'.$val.'</td>'.PHP_EOL;
				$val=isset($tinfo['fieldinfo'][$field]['_dblength'])?$tinfo['fieldinfo'][$field]['_dblength']:'';
				echo '		<td class="w_gray w_smaller" align="right">'.$val.'</td>'.PHP_EOL;
				$val=isset($tinfo['fieldinfo'][$field]['_dbnull'])?$tinfo['fieldinfo'][$field]['_dbnull']:'';
				echo '		<td class="w_gray w_smaller">'.$val.'</td>'.PHP_EOL;
				$val=isset($tinfo['fieldinfo'][$field]['_dbkey'])?$tinfo['fieldinfo'][$field]['_dbkey']:'';
				echo '		<td class="w_gray w_smaller">'.$val.'</td>'.PHP_EOL;
				$val=isset($tinfo['fieldinfo'][$field]['_dbdefault'])?$tinfo['fieldinfo'][$field]['_dbdefault']:'';
				echo '		<td class="w_gray w_smaller" align="right">'.$val.'</td>'.PHP_EOL;
				echo '		<td class="w_gray w_smaller">'.$extra.'</td>'.PHP_EOL;
				$val=isset($tinfo['fieldinfo'][$field]['displayname'])?$tinfo['fieldinfo'][$field]['displayname']:'';
				echo '		<td class="w_gray w_smaller" class="w_nowrap">'.$val.'</td>'.PHP_EOL;
				if(isset($tinfo['fieldinfo'][$field]['inputtype']) && strlen($tinfo['fieldinfo'][$field]['inputtype'])){
					echo '		<td class="w_gray w_smaller w_nowrap" data-tooltip="'.$tinfo['fieldinfo'][$field]['inputtype'].'"><img style="vertical-align:middle" src="/wfiles/icons/form/'.$tinfo['fieldinfo'][$field]['inputtype'].'.png" alt="'.$tinfo['fieldinfo'][$field]['inputtype'].'" width="16" height="16"></td>'.PHP_EOL;
				}
				else{
                	echo '		<td></td>'.PHP_EOL;
				}
				foreach($formfields as $formfield){
					$val=isset($tinfo['fieldinfo'][$field][$formfield])?$tinfo['fieldinfo'][$field][$formfield]:'';
					if(isNum($val)){
						if($val==1){
							if($formfield=='required'){$val='<span class="icon-mark"></span>';}
							elseif($formfield=='editlist'){$val='<span class="icon-list"></span>';}
							}
						elseif($val==0){$val='';}
						echo '		<td align="right" class="w_smaller" title="'.$formfield.'">'.$val.'</td>'.PHP_EOL;
                    	}
					else{echo '		<td title="'.$formfield.'" class="w_smaller" nowrap>'.$val.'</td>'.PHP_EOL;}
					}
				$recopts=array('-table'=>"_fielddata",'-where'=>"tablename = '{$currentTable}' and fieldname = '{$field}'");
				$rec=getDBRecord($recopts);
				$id=is_array($rec)?$rec['_id']:"''";
				echo '	</tr>'.PHP_EOL;
            	}
            echo '</table>';
            echo '</td><td>'.PHP_EOL;
            //$list=getDBSchema(array($currentTable));
            $list=$tinfo['fieldinfo'];
			echo buildFormBegin('',array('_menu'=>"properties",'_table_'=>$currentTable));
			echo '<table class="table table-responsive table-bordered">'.PHP_EOL;
            echo '	<tr><th><span class="icon-edit"></span> Table Schema Editor</th></tr>'.PHP_EOL;
            echo '	<tr valign="top"><td>'.PHP_EOL;
            $height=300;
            if(count($list) > 15){
            	$height=round((count($list)*12),0);
            	if($height > 700){$height=700;}
            	if($height < 300){$height=300;}
			}
			echo '		<textarea name="_schema" wrap="off" spellcheck="false" style="font-size:9pt;width:400px;height:'.$height.'px;">'.PHP_EOL;
			//echo printValue($list);
			foreach($list as $field){
				if(preg_match('/^\_/',$field['_dbfield'])){continue;}
				$type=$field['_dbtype_ex'];
				// if($field['_dbnull']=='NO'){$type .= ' NOT NULL';}
				// else{$type .= ' NULL';}
				// if(isset($field['_dbkey']) && $field['_dbkey']=='PRI'){$type .= ' Primary Key';}
				// elseif(isset($field['_dbkey']) && $field['_dbkey']=='UNI'){$type .= ' UNIQUE';}
				// if(isset($field['_dbdefault']) && strlen($field['_dbdefault'])){$type .= ' Default '.$field['_dbdefault'];}
				if(isset($field['_dbextra']) && strlen($field['_dbextra'])){
					if(stringContains($field['_dbextra'],' generated')){
						echo "{$field['_dbfield']} {$type} {$field['_dbextra']}\r\n";
						continue;
					}
				}
				if(isset($field['_dbcomment']) && strlen($field['_dbcomment'])){$type .= " COMMENT '{$field['_dbcomment']}'";}
				echo "{$field['_dbfield']} {$type}\r\n";
            }
			echo '		</textarea><br clear="both" />'.PHP_EOL;
			echo '<div align="right">'.buildFormSubmit('Save Schema Changes','','','icon-save').'</div>'.PHP_EOL;
			echo buildFormEnd();
			echo '	</td></tr>'.PHP_EOL;
			echo '</table>'.PHP_EOL;
            echo '</td></tr>'.PHP_EOL;
            echo '<tr valign="top"><td colspan="2">'.PHP_EOL;
            echo buildFormBegin('',array('_menu'=>"properties",'_table_'=>$currentTable));
            echo buildFormSubmit("Save Changes","do",'',$CONFIG['admin_color']);
            echo '<table class="table table-responsive table-bordered table-striped table-responsive">'.PHP_EOL;
            //General Table Settings
            echo '	<tr valign="top">'.PHP_EOL;
			echo '		<th colspan="2" class="w_align_left"><span class="icon-table w_grey w_big"></span> General Table Settings</th>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			//synchronize and websockets
			if(isset($tinfo['synchronize']) && $tinfo['synchronize']){$_REQUEST['synchronize']=$tinfo['synchronize'];}
			//if(isset($tinfo['websockets']) && $tinfo['websockets']){$_REQUEST['websockets']=$tinfo['websockets'];}
			echo '	<tr valign="top">'.PHP_EOL;
			echo '		<td>'.PHP_EOL;
			echo '			<div>'.buildFormCheckbox('synchronize',array(1=>'Synchronize'),array('class'=>'w_green','id'=>'prop_synchronize')).'</div>'.PHP_EOL;
			//echo '			<div>'.buildFormCheckbox('websockets',array(1=>'Websockets'),array('class'=>'w_orange','id'=>'prop_websockets')).'</div>'.PHP_EOL;
			//echo '</table>';
			echo '		</td>'.PHP_EOL;
			echo '		<td>'.PHP_EOL;
			echo '			<div class="w_dblue">Check to synchronize this table</div>'.PHP_EOL;
			//echo '			<div class="w_dblue">Check to enable websocket events for this table</div>'.PHP_EOL;
			echo '		</td>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			//table group and description
			if(isset($tinfo['tablegroup'])){$_REQUEST['tablegroup']=$tinfo['tablegroup'];}
			echo '	<tr valign="top">'.PHP_EOL;
			echo '		<td class="w_dblue">'.PHP_EOL;
			echo '			<div style="width:150px">'.PHP_EOL;
			echo '				<div data-tooltip="Allows grouping in admin table menu."><span class="icon-group w_success w_big"></span> Table Group</div>'.PHP_EOL;
			echo '					'.buildFormField('_tabledata','tablegroup').PHP_EOL;
			echo '			</div>'.PHP_EOL;
			echo '		</td>'.PHP_EOL;
			if(isset($tinfo['tabledesc']) && is_array($tinfo['tabledesc'])){$_REQUEST['tabledesc']=array2String($tinfo['tabledesc']);}
			echo '		<td>'.PHP_EOL;
			echo '			<div class="w_dblue"><span class="icon-info"></span> Table Description:</div>'.PHP_EOL;
			echo '					'.buildFormField('_tabledata','tabledesc').PHP_EOL;
			echo '		</td>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			//Table Admin List fields
   //          echo '	<tr valign="top">'.PHP_EOL;
			// echo '		<th colspan="2" class="w_align_left"><span class="icon-user-admin w_danger w_big"></span> Administrator Settings</th>'.PHP_EOL;
			// echo '	</tr>'.PHP_EOL;
            echo '	<tr valign="top">'.PHP_EOL;
			echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-list"></span> List Fields - fields to display when listing records</div></td>'.PHP_EOL;
			if(isset($tinfo['listfields']) && is_array($tinfo['listfields'])){$_REQUEST['listfields']=array2String($tinfo['listfields']);}
			echo '		<td>'.buildFormField('_tabledata','listfields').'</td>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			echo '	<tr valign="top">'.PHP_EOL;
			echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-sort-name-up"></span> Sort Fields -  default sorting order</div></td>'.PHP_EOL;
			if(isset($tinfo['sortfields']) && is_array($tinfo['sortfields'])){$_REQUEST['sortfields']=array2String($tinfo['sortfields']);}
			echo '		<td>'.buildFormField('_tabledata','sortfields').'</td>'.PHP_EOL;
			//echo '		<td><textarea style="width:550px;height:30px;" onfocus="autoGrow(this)" onblur="this.style.height=\'30px\';" onKeypress="autoGrow(this)" name="sortfields">'.$val.'</textarea></td>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			echo '	<tr valign="top">'.PHP_EOL;
			echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-newspaper"></span> Form Fields - order of fields to display when showing a form. Place fields as you would see them with spaces between fieldnames and carriage returns between rows.</div></td>'.PHP_EOL;
			if(isset($tinfo['formfields']) && is_array($tinfo['formfields'])){$_REQUEST['formfields']=array2String($tinfo['formfields']);}
			echo '		<td>'.buildFormTextarea('formfields',array('height'=>170)).'</td>'.PHP_EOL;
			//echo '		<td><textarea style="width:550px;height:100px;" onfocus="autoGrow(this)" onblur="this.style.height=\'100px\';" onKeypress="autoGrow(this)" name="formfields">'.$val.'</textarea></td>'.PHP_EOL;
			echo '	</tr>'.PHP_EOL;
			// //Non Admin Settings
			// echo '	<tr valign="top">'.PHP_EOL;
			// echo '		<th colspan="2" class="w_align_left"><span class="icon-user w_big w_grey"></span> Non-Administrator Settings</th>'.PHP_EOL;
			// echo '	</tr>'.PHP_EOL;
   //          echo '	<tr valign="top">'.PHP_EOL;
			// echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-list"></span> List Fields - fields to display when listing records</div></td>'.PHP_EOL;
			// if(isset($tinfo['listfields_mod']) && is_array($tinfo['listfields_mod'])){$_REQUEST['listfields_mod']=array2String($tinfo['listfields_mod']);}
			// echo '		<td>'.buildFormField('_tabledata','listfields_mod').'</td>'.PHP_EOL;
			// //echo '		<td><textarea style="width:550px;height:50px;" onfocus="autoGrow(this)" onblur="this.style.height=\'50px\';" onKeypress="autoGrow(this)" name="listfields_mod">'.$val.'</textarea></td>'.PHP_EOL;
			// echo '	</tr>'.PHP_EOL;
			// echo '	<tr valign="top">'.PHP_EOL;
			// echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-sort-name-up"></span> Sort Fields -  default sorting order</div></td>'.PHP_EOL;
			// if(isset($tinfo['sortfields_mod']) && is_array($tinfo['sortfields_mod'])){$_REQUEST['sortfields_mod']=array2String($tinfo['sortfields_mod']);}
			// echo '		<td>'.buildFormField('_tabledata','sortfields_mod').'</td>'.PHP_EOL;
			// //echo '		<td><textarea style="width:550px;height:30px;" onfocus="autoGrow(this)" onblur="this.style.height=\'30px\';" onKeypress="autoGrow(this)" name="sortfields_mod">'.$val.'</textarea></td>'.PHP_EOL;
			// echo '	</tr>'.PHP_EOL;
			// echo '	<tr valign="top">'.PHP_EOL;
			// echo '		<td class="w_dblue"><div style="width:150px"><span class="icon-newspaper"></span> Form Fields - order of fields to display when showing a form when not logged in as administrator</div></td>'.PHP_EOL;
			// if(isset($tinfo['formfields_mod']) && is_array($tinfo['formfields_mod'])){$_REQUEST['formfields_mod']=array2String($tinfo['formfields_mod']);}
			// echo '		<td>'.buildFormField('_tabledata','formfields_mod').'</td>'.PHP_EOL;
			// //echo '		<td><textarea style="width:550px;height:100px;" onfocus="autoGrow(this)" onblur="this.style.height=\'100px\';" onKeypress="autoGrow(this)" name="formfields_mod">'.$val.'</textarea></td>'.PHP_EOL;
			// echo '	</tr>'.PHP_EOL;
			echo '</table>'.PHP_EOL;
			echo buildFormSubmit("Save Changes","do",'',$CONFIG['admin_color']);
			echo buildFormEnd();
			echo '</td></tr>'.PHP_EOL;
			echo '</table>'.PHP_EOL;
			break;
		case 'sqlprompt':
			echo adminViewPage('sqlprompt');exit;
		break;
		case 'phpprompt':
			echo '<div class="container-fluid">'.PHP_EOL;
			echo adminViewPage('phpprompt');
			echo '</div>'.PHP_EOL;
			exit;
		break;
		case 'optimize':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-optimize"></span> Optimize Tables</h2>'.PHP_EOL;
			$rtn=optimizeDB();
			echo "<div>Command: {$rtn['command']}</div>\n";
			echo nl2br($rtn['result']);
			break;
		case 'backup':
			$_REQUEST['func']="backup";
		case 'backups':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-save"></span> Backup / <span class="icon-undo"></span> Restore</h2>'.PHP_EOL;
			$backupdir=getWasqlPath('sh/backups');
			if(isset($_REQUEST['func'])){
            	switch(strtolower($_REQUEST['func'])){
					case 'restore':
						$file=decodeBase64($_REQUEST['file']);
						if(preg_match('/\.gz$/i',$file)){
                        	$ok=cmdResults("gunzip '{$file}'");
                        	//echo printValue($ok);
                        	$file=preg_replace('/\.gz$/i','',$file);
						}
						if(is_file($file) && preg_match('/\.sql$/i',$file)){
							$cmds=array(
								"mysql -h {$CONFIG['dbhost']} --user='{$CONFIG['dbuser']}' --password='{$CONFIG['dbpass']}' --execute=\"DROP DATABASE {$CONFIG['dbname']}; CREATE DATABASE {$CONFIG['dbname']} CHARACTER SET utf8 COLLATE utf8_general_ci;\"",
								"mysql -h {$CONFIG['dbhost']} --user='{$CONFIG['dbuser']}' --password='{$CONFIG['dbpass']}' --max_allowed_packet=128M --default-character-set=utf8 {$CONFIG['dbname']} < \"{$file}\""
							);
							foreach($cmds as $cmd){
								//echo "<div>{$cmd}</div>\n";
								$ok=cmdResults($cmd);
								if(isset($ok['rtncode']) && $ok['rtncode'] != 0){
									echo printValue($ok);
									break;
								}
							}
						}
					break;
					case 'rename':
						$file=decodeBase64($_REQUEST['file']);
						$newname=addslashes($_REQUEST['name']);
						$newname=str_replace(' ','_',$newname);
						$newname=preg_replace('/[^a-z0-9\_\-\.]/i','',$newname);
						$newname=preg_replace('/\_+/','_',$newname);
						$newname=preg_replace('/\.gz$/i','',$newname);
						$newname=preg_replace('/\.sql$/i','',$newname);
						if(strlen($newname)){
							$newname=$CONFIG['dbname'].'__'.$newname;
							//$newname=getFileName($newname,1);
							$filename=getFileName($file);
							if(preg_match('/\.sql\.gz$/i',$filename)){
								$afile=str_replace($filename,"{$newname}.sql.gz",$file);
							}
							elseif(preg_match('/\.sql$/i',$filename)){
								$afile=str_replace($filename,"{$newname}.sql",$file);
							}
							if($afile != $file){
                            	rename($file,$afile);
                            	//echo "{$file} renamed to {$afile}<br>\n";
							}
						}
					break;
					case 'download':
						if(isset($_REQUEST['filename'])){
							$afile=base64_decode($_REQUEST['filename']);
							if(file_exists(($afile))){
								pushFile($afile);
							}
						}
					break;
                	case 'backup':
					case 'backup now':
						//echo "here".printValue($_REQUEST);exit;
                		$dump=dumpDB($_REQUEST['_table_']);
						if(isset($_REQUEST['push']) && $_REQUEST['push']=='filename'){
							if(file_exists($dump['afile']) && filesize($dump['afile'])){
								echo '<backup>'.base64_encode($dump['afile']).'</backup>';
							}
							else{
								echo '<backup>ERROR:'.$dump['error'].'</backup>';
							}
							exit;
						}
                		elseif(!isset($dump['success'])){
							echo '<span class="icon-cancel w_danger"></span> <b>Backup Command Failed</b><br>'.PHP_EOL;
							echo '<div style="margin-left:50px;">'.PHP_EOL;
							echo '	<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'.PHP_EOL;
							echo '	<div><b>Error:</b> '.$dump['error'].'</div>'.PHP_EOL;
							echo '	<div><b>Result:</b> '.printValue($dump['result']).'</div>'.PHP_EOL;
							echo '</div>'.PHP_EOL;
						}
						else{
							echo '<span class="icon-check w_success"></span> <b>Backup Successful</b><br>'.PHP_EOL;
							echo '<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'.PHP_EOL;
			            }
                	break;
                	case 'delete':
                		if(!is_array($_REQUEST['name']) || !count($_REQUEST['name'])){
                        	echo '<div>No Files Selected to Delete</div>'.PHP_EOL;
						}
						else{
                        	foreach($_REQUEST['name'] as $name){
								unlink("{$backupdir}/{$name}");
							}
						}
                	break;
				}
			}
			echo '<div>Backup Directory: '.$backupdir.'</div>'.PHP_EOL;
			echo '<div>DBName: '.$CONFIG['dbname'].'</div>'.PHP_EOL;
			$files=listFilesEx($backupdir,array('name'=>$CONFIG['dbname'].'__'));

			echo buildFormBegin('',array('_menu'=>'backups','func'=>'','-name'=>'backupform'));
			echo buildFormSubmit('Backup Now','func','','icon-save');
			if(is_array($files) && count($files)){
				$list=array();
				$filecnt=count($files);
				for($x=0;$x<$filecnt;$x++){
					if(!stringBeginsWith($files[$x]['name'],$CONFIG['dbname'].'__')){
                    	continue;
					}
					$rec=$files[$x];
	            	$rec['action']='<a class="w_link w_block" style="padding:0 3px 0 3px" href="/php/admin.php?_pushfile='.encodeBase64($rec['afile']).'" data-tooltip="Click to Download" data-tooltip_position="bottom"><span class="icon-download w_big"></span></a>';
	            	$rec['action'].=' <a class="w_link w_block" style="padding:0 3px 0 3px" href="/php/admin.php?_menu=backups&func=restore&file='.encodeBase64($rec['afile']).'" onclick="return confirm(\'This will restore the entire database back to this point.\\r\\n\\r\\n ARE YOU ABSOLUTELY SURE? If so, click OK.\');" data-tooltip="Restore Database" data-tooltip_position="bottom"><span class="icon-undo w_danger w_big"></span></a>';
	            	$rec['action'].=' <a class="w_link w_block" style="padding:0 3px 0 3px" href="/php/admin.php?_menu=backups&func=rename&file='.encodeBase64($rec['afile']).'" onclick="return renameBackup(this);" data-tooltip="Rename Backup File" data-tooltip_position="bottom"><span class="icon-rename w_grey w_big"></span></a>';
					$list[]=$rec;
				}
				//echo printValue($list);
				//sort by newest first
				$list=sortArrayByKey($list,'_cdate_age',SORT_ASC);
				//display list
				echo '<div style="padding:15px;">'.PHP_EOL;
				echo databaseListRecords(array(
					'-list'					=>$list,
					'-listfields'			=> "name,action,size_verbose,_cdate,_cdate_age_verbose",
					'-tableclass'			=> "table table-responsive table-bordered table-striped",
					'action_displayname'	=> '<span class="icon-download w_big"></span>  <span class="icon-undo w_big"></span> Actions',
					'size_verbose_displayname'	=> 'Size',
					'_cdate_displayname'	=> 'Date Created',
					'type_align'			=>'center',
					'_cdate_age_verbose_displayname'	=> 'Age',
					'_cdate_age_verbose_align'	=> 'right',
					'name_checkbox'			=>1
					));
				echo '</div>'.PHP_EOL;
				echo buildFormSubmit('Delete','func',"return confirm('Delete selected backup files?');",'icon-cancel w_big');
			}
			echo buildFormEnd();
			//echo printValue($_REQUEST);
			break;
		case 'email':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-email"></span> Email</h2>'.PHP_EOL;
			echo buildFormBegin('/php/admin.php',array('-multipart'=>true,'_menu'=>"email",'-name'=>"emailform"));
			echo '<table class="table table-responsive table-striped table-bordered">'.PHP_EOL;
			echo '	<tr valign="top" class="w_align_left">'.PHP_EOL;
			$tables=getDBTables();
			echo '		<th>Table<br><select onchange="document.emailform.submit();" name="_table_"><option value=""></option>'.PHP_EOL;
			foreach($tables as $table){
				echo '				<option value="'.$table.'"';
				if(isset($_REQUEST['_table_']) && $_REQUEST['_table_']==$table){echo ' selected';}
				echo '>'.$table.'</option>'.PHP_EOL;
            	}
			echo '			</select>'."</th>\n";
			if(isset($_REQUEST['_table_'])){
				//show fields for this table
				$fields=getDBFields($_REQUEST['_table_']);
				echo '		<th>Email Field<br><select onchange="document.emailform.submit();" name="_field_"><option value=""></option>'.PHP_EOL;
				foreach($fields as $field){
					echo '				<option value="'.$field.'"';
					if(isset($_REQUEST['_field_']) && $_REQUEST['_field_']==$field){echo ' selected';}
					echo '>'.$field.'</option>'.PHP_EOL;
	            	}
				echo '			</select>'."</th>\n";
            	}
			echo '	</tr>'.PHP_EOL;
			unset($recs);
			if(isset($_REQUEST['_table_']) && isset($_REQUEST['_field_'])){
				$field=$_REQUEST['_field_'];
				echo '		<tr class="w_align_left"><th colspan="2">Where: <input type="text" style="width:300px;" name="_search_" value="'.$_REQUEST['_search_'].'"></td></tr>'.PHP_EOL;
				$recopts=array('-query'=>"select distinct {$field} from {$_REQUEST['_table_']} where not({$field} is null) and not({$field}='')");
				if(isset($_REQUEST['_search_']) && strlen($_REQUEST['_search_'])){
					$recopts['-query'] .= " and ({$_REQUEST['_search_']})";
                	}
				$recs=getDBRecords($recopts);
				echo '		<tr class="w_align_left"><th colspan="2">'.count($recs).' email addresses found.</td></tr>'.PHP_EOL;
				if(!isset($_REQUEST['_from_']) && isEmail($USER['email'])){$_REQUEST['_from_']=$USER['email'];}
				echo '		<tr class="w_align_left"><th colspan="2">From: <input type="email" style="width:300px;" name="_from_" mask="email" maskmsg="From must be a valid email address" data-required="1" data-requiredmsg="From is required" value="'.$_REQUEST['_from_'].'"></td></tr>'.PHP_EOL;
				echo '		<tr class="w_align_left"><th colspan="2">Subject: <input type="text" style="width:285px;" name="_subject_" data-required="1" data-requiredmsg="Subject is required" value="'.$_REQUEST['_subject_'].'"></td></tr>'.PHP_EOL;
				echo '		<tr class="w_align_left"><th colspan="2">Message<br><textarea name="message" style="width:350px;height:100px;">'.$_REQUEST['message'].'</textarea></td></tr>'.PHP_EOL;

				}
			echo '</table>'.PHP_EOL;
			echo '<button type="submit" class="btn" name="do">Refresh</button>'.PHP_EOL;
			if(isset($recs) && is_array($recs)){
				if(strlen($_REQUEST['message'])){
					echo '<button class="btn" type="submit" name="do" onclick="return confirm(\'Send Email Now to recipients shown?\');">Send Email</button>'.PHP_EOL;
					}
				echo '<p><b>Recipeints List:</b></p>'.PHP_EOL;
				echo '<div style="margin-left:25px;height:200px;overflow:auto;padding-right:25px;">'.PHP_EOL;
				foreach($recs as $rec){
					$email=$rec[$_REQUEST['_field_']];
					echo '<div class="w_small">'.$email;
					if(isset($_REQUEST['do']) && strtolower($_REQUEST['do'])=='send email' && strlen($_REQUEST['message']) && isEmail($email)){
						$ok=wasqlMail(array('to'=>$email,'from'=>$_REQUEST['_from_'],'subject'=>$_REQUEST['_subject_'],'message'=>$_REQUEST['message']));
						echo " ... ";
						if(is_array($ok)){echo printValue($ok);}
						else{echo '<span class="icon-check w_success"></span>'.PHP_EOL;}
                    	}
					echo '</div>'.PHP_EOL;
					}
				echo '</div>'.PHP_EOL;
            	}
            echo buildFormEnd();
			break;
		case 'user_report':
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-user w_grey"></span><span class="icon-chart-bar w_grey"></span> Password Report</div>'.PHP_EOL;
			$recs=getDBRecords(array(
				'-table'=>'_users',
				'-order'=>"utype,lastname"
			));
			foreach($recs as $i=>$rec){
				$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
				$pwscore=passwordScore($pw);
				if($recs[$i]['utype']==0){
					//admins must have at 10/10 to get green
					if($pwscore == 10){$recs[$i]['pw_score']='<span class="icon-mark w_success w_bold"></span> '.$pwscore.'/10';}
					elseif($pwscore >= 8){$recs[$i]['pw_score']='<span class="icon-warning w_warning w_bold"></span> '.$pwscore.'/10';}
					else{$recs[$i]['pw_score']=$recs[$i]['pw_score']='<span class="icon-cancel w_danger w_bold"></span> '.$pwscore.'/10';}
				}
				else{
					if($pwscore >= 8){$recs[$i]['pw_score']='<span class="icon-mark w_success w_bold"></span> '.$pwscore.'/10';}
					elseif($pwscore >= 4){$recs[$i]['pw_score']='<span class="icon-warning w_warning w_bold"></span> '.$pwscore.'/10';}
					else{$recs[$i]['pw_score']=$recs[$i]['pw_score']='<span class="icon-cancel w_danger w_bold"></span> '.$pwscore.'/10';}
				}
				$ctime=time();
				$recs[$i]['created']=verboseTime($ctime-$recs[$i]['_cdate_utime']);
				if(isNum($recs[$i]['_cuser']) && $recs[$i]['_cuser'] > 0){
					$crec=getDBUserById($recs[$i]['_cuser'],array('username'));
					if(isset($crec['username'])){
						$recs[$i]['created'] .= " by {$crec['username']}" ;
					}
				}
				$recs[$i]['edited']=verboseTime($ctime-$recs[$i]['_edate_utime']);
				if(isNum($recs[$i]['_euser']) && $recs[$i]['_euser'] > 0){
					$erec=getDBUserById($recs[$i]['_euser'],array('username'));
					if(isset($erec['username'])){
						$recs[$i]['edited'] .= " by {$erec['username']}" ;
					}
				}
				$recs[$i]['accessed']=verboseTime($ctime-$recs[$i]['_adate_utime']);
				$recs[$i]['type']=$recs[$i]['utype']==0?'<span class="icon-user-admin w_red"></span>':'<span class="icon-user w_grey"></span>';

			}
			echo databaseListRecords(array(
				'-list'				=>$recs,
				'-tableclass'			=> "table table-responsive table-bordered table-striped",
				'-fields'			=> "_id,active,firstname,lastname,username,type,pw_score,created,edited,accessed",
				'strong_pw_align'	=>'center',
				'username_href'		=> "/php/admin.php?_menu=edit&_id=%_id%&_table_=_users&_menu2=user_report",
				'_id_href'			=> "/php/admin.php?_menu=edit&_id=%_id%&_table_=_users&_menu2=user_report",
				'type_align'		=>'center',
				'pw_score_align'	=>'center'
				));
			//echo printValue($recs);
			//$pw=userIsEncryptedPW($ruser['password'])?userDecryptPW($ruser['password']):$ruser['password'];
			break;
		case 'grep':
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-search"></span> Database Search</h2>'.PHP_EOL;
			echo buildFormBegin('/php/admin.php',array('-multipart'=>true,'_menu'=>"grep",'-name'=>"grepform"));
			echo '<table class="table table-responsive table-striped table-bordered" style="width:600px;height:auto;">'.PHP_EOL;
			echo '	<tr valign="top" align="center"><th>Filters:</th>'.PHP_EOL;
			echo '		<th>Schema<br><input type="checkbox" class="form-control" name="_grep_schema" value="1">'."</th>\n";
			echo '		<th>Records<br><input type="checkbox" class="form-control" name="_grep_records" value="1" checked>'."</th>\n";
			$tables=getDBTables();
			echo '		<th>Table<br><select name="_table_" class="form-control"><option value=""></option>'.PHP_EOL;
			foreach($tables as $table){
				echo '				<option value="'.$table.'"';
				if(isset($_REQUEST['_table_']) && $_REQUEST['_table_']==$table){echo ' selected';}
				echo '>'.$table.'</option>'.PHP_EOL;
            	}
			echo '			</select>'."</th>\n";
			echo '	</tr><tr><th>Text:</th>'.PHP_EOL;
			echo '		<td colspan="3"><input type="text" name="_grep_string" value="'.encodeHtml(requestValue('_grep_string')).'" class="form-control" maxlength="255"> '."</td>\n";
			echo '	</tr></table>'.PHP_EOL;
			echo buildFormSubmit('Search Database','','','icon-search');
			echo buildFormEnd();
			echo buildOnLoad("document.grepform._grep_string.focus();");
			if(isset($_REQUEST['_grep_string']) && strlen($_REQUEST['_grep_string'])){
				$grep=array('schema'=>0,'records'=>0,'table'=>'');
				$grep['string']=$_REQUEST['_grep_string'];
				if(isset($_REQUEST['_grep_schema']) && $_REQUEST['_grep_schema']==1){$grep['schema']=1;}
				if(isset($_REQUEST['_grep_records']) && $_REQUEST['_grep_records']==1){$grep['records']=1;}
				//echo printValue($grep);
				//grep Schema?
				if($grep['schema']==1){
					echo '<div class="w_bold w_big w_dblue">Schema Results</div>'.PHP_EOL;
					echo '<table class="table table-responsive table-striped table-bordered">'.PHP_EOL;
					echo '	<tr><th>Table</th><th>Fields</th></tr>'.PHP_EOL;
					foreach($tables as $table){
						if(strlen($grep['table']) && $table != $grep['table']){continue;}
						$info=getDBFieldInfo($table);
						$vals=array();
						foreach($info as $field=>$finfo){
							if(stringContains($field,$grep['string'])){$vals[]=$field;}
                    		}
                    	if(count($vals)){echo '	<tr valign="top"><td>'.$table.'</td><td>'.implode(', ',$vals).'</td></tr>'.PHP_EOL;}
                    	}
                    echo '</table>'.PHP_EOL;
                	}
                //grep records?
                if($grep['records']==1){
					$tables=array();
					if(isset($_REQUEST['_table_']) && strlen($_REQUEST['_table_'])){$tables[]=$_REQUEST['_table_'];}
					$results=grepDBTables($grep['string'],$tables);
					if(is_array($results)){
						echo '<div class="w_bold w_big w_dblue">Record Results</div>'.PHP_EOL;
						echo databaseListRecords(array(
							'-list'=>$results,
							'-tableclass'	=> "table table-responsive table-bordered table-striped",
							'_id_href'=>"/php/admin.php?_table_=%tablename%&_menu=edit&_id=%_id%"
						));
					}
					else{
						echo $results;
					}
            	}
			}
		break;
		case 'import':
			//echo adminViewPage('import');exit;
			echo '<h2 style="margin:0px;padding:6px;" class="'.configValue('admin_color').'"><span class="icon-import"></span> Import</h2>'.PHP_EOL;
			$importmsg='';
			global $progpath;
			if(isset($_SERVER['CONTENT_TYPE']) && preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
				echo '<div>Processing Uploaded File</div>'.PHP_EOL;
	 			if(isset($_REQUEST['file_abspath']) && file_exists($_REQUEST['file_abspath'])){
					if(preg_match('/XML/is',$_REQUEST['do'])){
						echo '<div class="w_lblue w_bold w_big">XML Import</div>'.PHP_EOL;
						echo "<div>Reading File: {$_REQUEST['file_abspath']}</div>\n";
						$items=exportFile2Array($_REQUEST['file_abspath']);
						unlink($_REQUEST['file_abspath']);
						echo "<div>Importing Items </div>\n";
	    				ob_flush();
						$importmsg .= importXmlData($items,$_REQUEST);
						}
					elseif(preg_match('/CSV/is',$_REQUEST['do'])){
						echo '<div class="w_lblue w_bold w_big">CSV Import</div>'.PHP_EOL;
						if(!strlen($_REQUEST['_table_'])){
							echo '<div class="w_red w_bold">You must select a table for CSV imports</div>'.PHP_EOL;
                        	}
                        elseif($_REQUEST['_table_']=='Create NEW Table' && !strlen($_REQUEST['_tablename_'])){
							echo '<div class="w_red w_bold">You must choose a name for the new table</div>'.PHP_EOL;
                        	}
						else{
							$lines = getCSVFileContents($_REQUEST['file_abspath']);
							//echo printValue($lines);exit;
							if($_REQUEST['_table_']=='Create NEW Table'){
								//Create a new table based on the data
								$fields=array(
									'_id'	=> databasePrimaryKeyFieldString(),
									'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
									'_cuser'=> "int NOT NULL",
									'_edate'=> databaseDataType('datetime')." NULL",
									'_euser'=> "int NULL",
									);
								$fieldtype=array();
								$maxlen=array();
								foreach($lines['items'] as $item){
									foreach($item as $key=>$val){
										//maxlength
										if(!isset($maxlen[$key]) || strlen($val) > $maxlen[$key]){
											$maxlen[$key]=strlen($val);
											}
										//type
										if(isNum($val)){
											if(!isset($fieldtype[$key])){$fieldtype[$key]='int';}
                                        	}
                                        elseif(isDateTime($val)){
											if(!isset($fieldtype[$key])){$fieldtype[$key]='datetime';}
                                        	}
                                        elseif(isDate($val)){
											if(!isset($fieldtype[$key])){$fieldtype[$key]='date';}
                                        	}
                                        else{
											if(!isset($fieldtype[$key])){$fieldtype[$key]='varchar';}
                                        	}
                                    	}
                                	}
                                foreach($fieldtype as $key=>$type){
									$fld=preg_replace('/[^a-z0-9]+/i','_',$key);
									switch($type){
										case 'int':
											$fields[$fld]="int NULL";
											break;
										case 'datetime':
											$fields[$fld]=databaseDataType('datetime')." NULL";
											break;
										case 'date':
											$fields[$fld]="date NULL";
											break;
										case 'varchar':
											$max=$maxlen[$key];
											if($max > 2000){$fields[$fld]="text NULL";}
											else{$fields[$fld]="varchar({$max}) NULL";}
											break;
                                    	}
                                	}
                                //create the table
                                $_REQUEST['_table_']=$_REQUEST['_tablename_'];
                                unset($_REQUEST['_tablename_']);
                                $ok = createDBTable($_REQUEST['_table_'],$fields);
		        				if(!isNum($ok)){abort($ok);}
                            	}
							$info=getDBFieldInfo($_REQUEST['_table_'],1);
							$row=1;
							foreach($lines['items'] as $item){
								$row++;
								$opts=array();
								foreach($item as $key=>$val){
									if(!isset($info[$key])){continue;}
									if(!strlen($val)){continue;}
									$opts[$key]=$val;
                                	}
                                if(count($opts) > 0){
									$opts['-table']=$_REQUEST['_table_'];
									$id=addDBRecord($opts);
									if(isNum($id)){echo ".";}
									else{
										$importmsg .= "addDBRecord Error on row {$row}" . printValue($id).printValue($opts);
                                    	}
                                	}
                            	}
							}
                    	}
                	}
				}
			if(isset($_REQUEST['file_error'])){
				echo '<div class="w_red"><span class="icon-cancel w_danger w_big"></span> '.$_REQUEST['file_error'].'</div>'.PHP_EOL;
            	}
            $progpath=dirname(__FILE__);
			$filepath="{$progpath}/temp";
			echo buildFormBegin('/php/admin.php',array('-class'=>"w_form form-inline",'-multipart'=>true,'_menu'=>"import",'file_path'=>$filepath,'file_autonumber'=>1));
			if(!isset($_REQUEST['_types'])){$_REQUEST['_types']=array('xmlschema','xmlmeta','xmldata');}
			if(!isset($_REQUEST['_options'])){$_REQUEST['_options']=array('drop','ids');}
			echo buildFormFile('file',array('accept'=>'.xml,.csv','acceptmsg'=>'Only valid xml and csv files are allowed'));
			echo '<div style="width:500px;"'.PHP_EOL;
			echo '<table class="table"><tr valign="top">'.PHP_EOL;
			//XML File Options
			echo '<td class="nowrap">'.PHP_EOL;
			echo '<div class="w_lblue w_bold w_big">XML File Options</div>'.PHP_EOL;
			$opts=array(
				'xmlschema'=>'Schema',
				'xmlmeta'=>'Meta',
				'xmldata'=>'Data'
			);
			$params=array('value'=>$_REQUEST['_types'],'width'=>3);
			echo buildFormCheckbox('_types[]',$opts,$params);
			$opts=array(
				'drop'=>'Drop Existing Tables with same names',
				'truncate'=>'Truncate Existing Records',
				'ids'=>'Import IDs'
			);
			$params=array('value'=>$_REQUEST['_types'],'width'=>1);
			echo buildFormCheckbox('_options[]',$opts,$params);
			echo '	Merge Fields:<br /> <textarea name="_merge" style="width:300px;height:50px;" class="w_small form-control"></textarea>'."<br />\n";
			echo '</td><td>'.PHP_EOL;
			echo '<div class="w_lblue w_bold w_big">CSV File Options</div>'.PHP_EOL;
			$tables=getDBTables();
			array_unshift($tables,'Create NEW Table');
			echo '<div>Table '.PHP_EOL;
			echo getDBFieldTag(array('-table'=>'_tabledata','onchange'=>"if(this.value=='Create NEW Table'){showId('newtable');hideId('picktable');}else{hideId('newtable');showId('picktable');}",'-field'=>'fieldname','name'=>"_table_",'message'=>"--Select Table--",'inputtype'=>'select','tvals'=>join("\r\n",$tables)));
			//echo "TEST:" . strtotime("yesterday");
			echo '</div>'.PHP_EOL;
			echo '<div style="width:300px;margin-top:5px;position:relative;height:75px;">'.PHP_EOL;
			echo '	<div id="newtable" style="display:none;position:absolute;">New Tablename '.PHP_EOL;
			echo getDBFieldTag(array('-table'=>'_tabledata','-field'=>'fieldname','name'=>"_tablename_",'inputtype'=>'text','width'=>150,'maxlength'=>150));
			echo '	<br>This will create a table with fields based on the data in the csv file you upload.'.PHP_EOL;
			echo '	</div>'.PHP_EOL;
			echo '	<div id="picktable">'.PHP_EOL;
			echo '		Select the table you want to import into. Make sure the first line of the csv file matches the field names of the table.'.PHP_EOL;
			echo '	</div>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
			echo '</td></tr>'.PHP_EOL;
			echo '<tr><td style="padding-top:25px;">'.buildFormSubmit('Import XML File','do','','icon-import').'</td><td>'.buildFormSubmit('Import CSV File','do','','icon-import').'</td></tr>'.PHP_EOL;
			echo '</table>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
			echo buildFormEnd();
			if(strlen($importmsg)){
				echo '<div class="w_lblue w_bold"> Import results:</div>'.PHP_EOL;
				echo $importmsg;
				}
			//echo printValue($_REQUEST);
			//echo printValue($_FILES);
			break;
		case 'export':
			echo adminViewPage('export');exit;
		break;
		case 'datasync':
			echo adminViewPage($_REQUEST['_menu']);exit;
		case 'searchreplace':
			echo '<div class="w_lblue w_bold w_bigger">Search & Replace</div>'.PHP_EOL;
			break;
		case 'files':
			echo '<div class="w_lblue w_bold w_bigger"><span class="icon-attach"></span> File Manager</div>'.PHP_EOL;
			echo fileManager();
			break;
    	}
   	}
echo '</div>'.PHP_EOL;
//echo '<div style="display:none" id="null">'.PHP_EOL;
//echo "ConfigSettings" . printValue($ConfigSettings);
//echo '</div>'.PHP_EOL;
//$elapsed=round((microtime(true)-$_SERVER['_start_']),2);
//echo buildOnLoad("setText('admin_elapsed','{$elapsed} seconds');");
echo showWasqlErrors();
echo "</body>\n</html>";
exit;
//---------- begin function adminDefaultPageValues ----
/**
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminDefaultPageValues(){
	$_REQUEST['body'] = <<<ENDOFDEFAULT
<view:default>
</view:default>

<view:login>
<?=userLoginForm(array('-action'=>'/'.pageValue('name')));?>
</view:login>
ENDOFDEFAULT;
	$_REQUEST['controller'] =  <<<ENDOFDEFAULT
<?php
//require user
if(!isUser()){
	setView('login',1);
	return;
}
global \$USER;
global \$PASSTHRU;
switch(strtolower(\$PASSTHRU[0])){
	default:
		setView('default');
	break;
}
?>
ENDOFDEFAULT;
	$_REQUEST['functions'] = <<<ENDOFDEFAULT
<?php

?>
ENDOFDEFAULT;
	$_REQUEST['css'] = <<<ENDOFDEFAULT
.pagesamplecss{}
ENDOFDEFAULT;
	$_REQUEST['js'] = <<<ENDOFDEFAULT
function pageSampleJs(){}
ENDOFDEFAULT;
}
//---------- begin function adminDefaultTemplateValues ----
/**
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminDefaultTemplateValues(){
	$_REQUEST['body'] = <<<ENDOFDEFAULT
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title><?=templateMetaTitle();?></title>
	<link rel="canonical" href="//<?=\$_SERVER['HTTP_HOST'];?>/<?=pageValue('name');?>/" />
	<!-- Icons -->
	<link rel="apple-touch-icon" sizes="76x76" href="/wfiles/apple-touch-icon.png">
	<link rel="shortcut icon" href="/wfiles/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="/wfiles/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/wfiles/favicon-16x16.png">
	<link rel="manifest" href="/wfiles/site.webmanifest">
	<meta name="msapplication-TileColor" content="#2d89ef">
	<meta name="theme-color" content="#ffffff">
	<!-- Mobal Meta -->
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<!-- SEO Meta -->
	<meta name="description" content="<?=templateMetaDescription();?>" />
	<meta name="keywords" content="<?=templateMetaKeywords();?>" />
	<!-- Open graph info for Facebook -->
	<meta property="og:title" content="<?=templateMetaTitle();?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="//<?=\$_SERVER['HTTP_HOST'];?>/" />
	<meta property="og:site_name" content="<?=templateMetaSite();?>" />
	<meta property="og:image" content="<?=templateMetaImage();?>" />
	<meta property="og:description" content="<?=templateMetaDescription();?>" />
	<!-- Twitter card info -->
	<meta name="twitter:card" content="summary">
	<meta name="twitter:site" content="<?=templateMetaSite();?>" />
	<meta name="twitter:title" content="<?=templateMetaTitle();?>" />
	<meta name="twitter:description" content="<?=templateMetaDescription();?>" />
	<meta name="twitter:creator" content="@wasqlcom" />
	<meta name="twitter:image:src" content="<?=templateMetaImage();?>" />
	<meta name="twitter:domain" content="//<?=\$_SERVER['HTTP_HOST'];?>" />
	<!-- Minified CSS and JS -->
	<link type="text/css" rel="stylesheet" href="<?=minifyCssFile('wacss');?>" />
  	<script type="text/javascript" src="<?=minifyJsFile('wacss');?>"></script>
</head>
<body>
	<div class="container">
		<?=pageValue('body');?>
	</div>
	<div style="display:none"><div id="null"></div></div>
</body>
</html>
ENDOFDEFAULT;
	$_REQUEST['controller'] = $_REQUEST['functions'] = <<<ENDOFDEFAULT
<?php
?>
ENDOFDEFAULT;
	$_REQUEST['css'] = <<<ENDOFDEFAULT
.templatesamplecss{}
ENDOFDEFAULT;
	$_REQUEST['js'] = <<<ENDOFDEFAULT
function templateSampleJs(){}
ENDOFDEFAULT;
}
function adminListDataTypes(){
	if(isPostgreSQL()){return postgresqlListDBDataTypes($params);}
	elseif(isSqlite()){return sqliteListDBDataTypes($params);}
	elseif(isOracle()){return oracleListDBDataTypes($params);}
	elseif(isMssql()){return mssqlListDBDataTypes($params);}
	return listDBDataTypes();
}
//---------- begin function adminGetPHPInfo ----
/**
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminGetPHPInfo(){
    ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();
    return $data;
}
//---------- begin function adminViewPage ----
/**
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminViewPage($menu){
	$progpath=dirname(__FILE__);
	$menu=strtolower($menu);
	if(is_file("{$progpath}/admin/{$menu}_functions.php")){
    	include_once("{$progpath}/admin/{$menu}_functions.php");
	}
	$body=getFileContents("{$progpath}/admin/{$menu}_body.htm");
	$controller=getFileContents("{$progpath}/admin/{$menu}_controller.php");
	$rtn = evalPHP(array($controller,$body));
	$rtn=processTranslateTags($rtn);
    return $rtn;
}
//---------- begin function adminShowSessionLog ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminShowSessionLog($sessionID){
	//exclude:true
	$progpath=dirname(__FILE__);
	$errpath="{$progpath}/errors";
	if(!is_dir($errpath)){
		return "{$errpath} does not exist";
	}
	cleanupDirectory($errpath,10,'min');
	$errfile="{$errpath}/{$sessionID}.log";
	if(!is_file($errfile)){
		return "No errors found for your session";
	}
	$rtn='';
	$files=listFilesEx($errpath,array('name'=>"{$sessionID}.log"));
	$rtn .= '	<div class="w_bold" style="border-bottom:1px solid #000;padding:10px;">Written ' . $files[0]['_edate_age_verbose'] . ' ago  <a href="#" class="w_link w_bold w_required" onclick="return ajaxGet(\'/php/admin.php\',\'session_errors\',\'_menu=clear_session_errors&t=10\');"><span class="icon-erase w_danger"></span> Clear Error Log</a></div>'.PHP_EOL;
	$rtn .= getFileContents($errfile);
	return $rtn;
}
function adminSetPageName(){
	global $PAGE;
	$PAGE['name']='php/admin.php';
	if(isset($_SERVER['SCRIPT_NAME'])){$PAGE['name']=$_SERVER['SCRIPT_NAME'];}
	elseif(isset($_SERVER['PHP_SELF'])){$PAGE['name']=$_SERVER['PHP_SELF'];}
	$PAGE['name']=preg_replace('/^\//','',$PAGE['name']);
	if(isset($_REQUEST['_menu'])){
		$PAGE['name'].="?_menu={$_REQUEST['_menu']}&_pass=";
	}
	if(isset($_REQUEST['_pass'])){
		$_REQUEST['_pass']=preg_replace('/^\//','',$_REQUEST['_pass']);
		$_REQUEST['_pass']=preg_replace('/\?$/','',$_REQUEST['_pass']);
		$_REQUEST['passthru']=$PASSTHRU=preg_split('/\//',$_REQUEST['_pass']);
	}
	//echo printValue($_REQUEST);exit;
}
//---------- begin function adminConfigSettings ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminConfigSettings(){
	$recs=getDBRecords(array(
		'-table'=>'_settings',
		'user_id'=>0,
		'-index'=>'key_name',
		'-fields'=>'_id,key_name,key_value'
	));
	foreach($recs as $key=>$rec){
		if($rec['key_value']==1){
			$recs[$key]['checked']=' checked';
		}
		elseif(!strlen($rec['key_value'])){
			$recs[$key]['checked']='';
			$recs[$key]['key_value']=0;
		}
	}
	return $recs;
}

//---------- begin function adminClearSessionLog ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminClearSessionLog($sessionID){
	//exclude:true
	$progpath=dirname(__FILE__);
	$errpath="{$progpath}/errors";
	if(!is_dir($errpath)){
		return "{$errpath} does not exist";
	}
	$errfile="{$errpath}/{$sessionID}.log";
	unlink($errfile);
	return;
}
//---------- begin function expandAjaxTables ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function expandAjaxTables(){
	//show a list of tables. Clicking on the tables expands to field info for that table
	$rtn='';
	$tables=getDBTables();
	//build a section for wasql tables using createExpandDiv($title,$expand,'#0d0d7d',0);
	$title='<img src="/wfiles/wasql_admin.png" class="w_middle" alt="tables" /> Tables';
	$expand = '<table class="w_nopad">'.PHP_EOL;
	foreach($tables as $table){
		if(!isWasqlTable($table)){continue;}
		$divid=$table .'_'. sha1($table);
		$expand .= '<tr><td class="nowrap">'.createExpandDiv($table,'','#0d0d7d',0,'/php/admin.php','_menu=tabledetails&table='.$table).'</td></tr>'.PHP_EOL;;
	}
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);

	//build a section for non-wasql tables using createExpandDiv($title,$expand,'#0d0d7d',0);
	$title='<span class="icon-user w_grey"><span class="icon-table"></span></span> User Tables';
	$expand = '<table class="w_nopad">'.PHP_EOL;
	foreach($tables as $table){
		if(isWasqlTable($table)){continue;}
		$divid=$table .'_'. sha1($table);
		$expand .= '<tr><td class="nowrap">'.createExpandDiv($table,'','#0d0d7d',0,'/php/admin.php','_menu=sqlprompt&table='.$table).'</td></tr>'.PHP_EOL;;
	}
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	unset($expand);
	return $rtn;
}

//---------- begin function adminMenuIcon ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminMenuIcon($src){
	//exclude:true
	global $ConfigSettings;
	if($ConfigSettings['mainmenu_icons']==0){return '';}
	return '<img src="'.$src.'" class="w_middle" alt="menu" />';
}

//---------- begin function adminUserLoginMenu ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminUserLoginMenu(){
	//exclude:true
	global $ConfigSettings;
	$show_icons=$ConfigSettings['mainmenu_icons'];
	$rtn='';
	$rtn .= '	<div id="adminmenu">'.PHP_EOL;
	$rtn .= '	<ul id="nav" class="dropdown dropdown-horizontal">'.PHP_EOL;
	$rtn .= '		<li><a href="#" onclick="return false;">'.adminMenuIcon('/wfiles/wasql_admin.png').' Admin Login - '.$_SERVER['HTTP_HOST'].'</a></li>'.PHP_EOL;
	//end menu
	$rtn .= '	</ul>'.PHP_EOL;
	$rtn .= '	<br clear="both" />'.PHP_EOL;
	$rtn .= '	</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function tableOptions ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function tableOptions($table='',$params=array()){
	/*
		Params:
			format: li,table,none
			options: array of the following: drop, truncate, indexes, schema, properties,list, grep, add, backup

	*/
	global $wtables;
	if(!isset($params['-format'])){$params['-format']='li';}
	if(!isset($params['-options'])){$params['-options']='drop,rebuild,rebuild_meta,truncate,backup,grep,model,indexes,properties,list,add';}
	if(!is_array($params['-options'])){$params['-options']=preg_split('/[\,\:]+/',$params['-options']);}
	global $PAGE;
	//Bootstrap Colors: default, primary, success, info, warning, danger, black, grey
	$tableoptions=array(
		'drop'		=> array("Delete Table",'icon-erase w_danger w_big'),
		'truncate'	=> array("Truncate Table",'icon-blank w_warning w_big'),
		'indexes'	=> array("Show Indexes",'icon-optimize w_gold w_big'),
		'backup'	=> array("Backup Table",'icon-save w_black w_big'),
		'model'		=> array("Triggers",'icon-toggle-on w_grey w_big'),
//		'schema'	=> array("Schema",'table_truncate'),
		'properties'=> array("Properties",'icon-properties w_grey w_big'),
		'list'		=> array("List Records",'icon-list w_default w_big'),
		//'grep'		=> array("Grep Records",'icon-database-search w_primary w_big'),
		'add'		=> array("Add New Record",'icon-plus w_success w_big')
		);
	if(in_array($table,$wtables) && !in_array($table,array('_pages','_fielddata','_tabledata','_users','_templates'))){
    	$tableoptions['rebuild']=array('Rebuild Table','icon-refresh w_info w_big');
	}
	if(in_array($table,$wtables)){
    	$tableoptions['rebuild_meta']=array('Rebuild Meta','icon-refresh w_warning w_big');
	}
	//check for _models for this table
	$model=getDBTableModel($table);
	//if($table=='states'){echo "HERE".printValue($tableoptions);exit;}
	$rtn='';
	switch(strtolower($params['-format'])){
		case 'li':
			$params['-options']=array_reverse($params['-options']);
			$rtn .= '					<ul>'.PHP_EOL;
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$spanclass=$tableoptions[$option][1];
				$href="/php/admin.php?_menu={$option}&_table_={$table}";
				if($option == 'model'){
                	if(isset($model['_id'])){
                    	$href="/php/admin.php?_menu=edit&_table_=_models&_id={$model['_id']}";
					}
					else{
						$href="/php/admin.php?_menu=add&_table_=_models&name={$table}";
					}
				}
				$rtn .= '						<li><a title="'.$title.'" class="w_link" href="'.$href.'"';
				
				if($option == 'truncate'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Purge all records from '.$table.' table?\\r\\nTHIS IS IRREVERSIBLE!!!\\r\\n\\r\\nClick OK to confirm.\');"';
		            }
		        elseif($option == 'drop'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Permanently Delete '.$table.' table and all records in it?\\r\\n\\r\\nClick OK to confirm.\');"';
		            }
		        elseif($option == 'rebuild'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DROP and REBUILD this table back to WaSQL Defaults!!!\\r\\n\\r\\nARE YOU SURE you want to Rebuild the '.$table.' table?\\r\\n\\r\\nClick OK to confirm.\');"';
		        }
		        elseif($option == 'add'){
					$rtn .= ' accesskey="a"';
				}
				$rtn .= '>';
				$rtn .= '<span alt="'.$title.'" class="'.$spanclass.'"></span> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</a></li>'.PHP_EOL;
		        }
		    //group?
		    if(isset($_SERVER['waSQL_Tablegroups']) && is_array($_SERVER['waSQL_Tablegroups'])){
	        	$recs=$_SERVER['waSQL_Tablegroups'];
			}
		    else{
			    $query="select distinct(tablegroup) from _tabledata";
			    if(isset($params['-group']) && strlen($params['-group'])){$query .= " where tablegroup != '{$params['-group']}'";}
				$query .= " order by tablegroup";
		        $recs=getDBRecords(array('-query'=>$query));
				$_SERVER['waSQL_Tablegroups']=$recs;
		        }
		    if(is_array($recs) && count($recs)){
	        	$menu=isset($_REQUEST['_menu'])?$_REQUEST['_menu']:'';
	        	$rtn .= '						<li class="dir"><a class="w_link" href="#" onclick="return false;"><span class="icon-group w_big w_success"></span> Group with</a>'.PHP_EOL;
	        	$rtn .= '							<ul>'.PHP_EOL;
	        	foreach($recs as $rec){
	            	$rtn .= '								<li><a class="w_link" href="/php/admin.php?_groupwith='.$rec['tablegroup'].'&_menu='.$menu.'&_table_='.$table.'">'.$rec['tablegroup'].'</a></li>'.PHP_EOL;
				}
	        	$rtn .= '							</ul>'.PHP_EOL;
	        	$rtn .= '						</li>'.PHP_EOL;
			}
		    $rtn .= '					</ul>'.PHP_EOL;
		    break;
		case 'table':
			$rtn .= '<table class="actionmenu w_nopad"><tr>'.PHP_EOL;
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$spanclass=$tableoptions[$option][1];
				$class='btn';
				$href="/php/admin.php?_menu={$option}&_table_={$table}";
				if($option == 'model'){
                	if(isset($model['_id'])){
                    	$href="/php/admin.php?_menu=edit&_table_=_models&_id={$model['_id']}";
					}
					else{
						$href="/php/admin.php?_menu=add&_table_=_models&name={$table}";
					}
				}
				if(isset($_REQUEST['_menu']) && $option==$_REQUEST['_menu']){$class.=' active';}
				$rtn .= '	<td><button type="button" style="line-height:1.2;padding:5px; margin-right:3px;" data-tooltip_position="bottom" data-tooltip="'.$title.'" class="'.$class.'"';
				if($option == 'truncate'){
					$rtn .= ' onclick="if(confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Purge all records from '.$table.' table?\\r\\nTHIS IS IRREVERSIBLE!!!\\r\\n\\r\\n Click OK to confirm.\')){window.location=\''.$href.'\';}"';
		            }
		        elseif($option == 'drop'){
					$rtn .= ' onclick="if(confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Permanently Delete '.$table.' table and all records in it?\\r\\n\\r\\nClick OK to confirm.\')){window.location=\''.$href.'\';}"';
		            }
		        elseif($option == 'rebuild'){
					$rtn .= ' onclick="if(confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DROP and REBUILD this table back to WaSQL Defaults!!!\\r\\n\\r\\nARE YOU SURE you want to Rebuild the '.$table.' table?\\r\\n\\r\\nClick OK to confirm.\')){window.location=\''.$href.'\';}"';
		            }
		        elseif($option == 'add'){
					//ajaxAddEditForm(table,id,flds,userparams)
					$onclick="return ajaxGet('/php/admin.php','modal',{_menu:'add',_table_:'{$table}',title:'Add New Record'});";
					$rtn .= ' onclick="'.$onclick.'"';
					$rtn .= ' accesskey="a"';
		            }
		        else{
                	$rtn .= ' onclick="window.location=\''.$href.'\';"';
				}
				$rtn .= '>';
				$rtn .= '<span alt="'.$title.'" class="'.$spanclass.'"></span> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</button></td>'.PHP_EOL;
		        }
		    $rtn .= '</tr></table>'.PHP_EOL;
			break;
		default:
			//none
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$spanclass=$tableoptions[$option][1];
				$class='';
				$href="/php/admin.php?_menu={$option}&_table_={$table}";
				if($option == 'model'){
                	if(isset($model['_id'])){
                    	$href="/php/admin.php?_menu=edit&_table_=_models&_id={$model['_id']}";
					}
					else{
						$href="/php/admin.php?_menu=add&_table_=_models&name={$table}";
					}
				}
				if(isset($_REQUEST['_menu']) && $option==$_REQUEST['_menu']){$class='current';}
				$rtn .= '	<a title="'.$title.'" class="w_link '.$class.'" href="'.$href.'"';
				if($option == 'truncate'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Purge all records from '.$table.' table?\\r\\nTHIS IS IRREVERSIBLE!!!\\r\\n\\r\\n Click OK to confirm.\');"';
		            }
		        elseif($option == 'drop'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DELETE ALL RECORDS IN THIS TABLE!!!\\r\\n\\r\\nARE YOU SURE you want to Permanently Delete '.$table.' table and all records in it?\\r\\n\\r\\nClick OK to confirm.\');"';
		            }
		        elseif($option == 'rebuild'){
					$rtn .= ' onclick="return confirm(\'PLEASE READ THIS CAREFULLY!!!!!!\\r\\nTHIS WILL DROP and REBUILD this table back to WaSQL Defaults!!!\\r\\n\\r\\nARE YOU SURE you want to Rebuild the '.$table.' table?\\r\\n\\r\\nClick OK to confirm.\');"';
		            }
		        elseif($option == 'add'){
					//ajaxAddEditForm(table,id,flds,userparams)
					$onclick="ajaxAddEditForm('{$table}','',null,'_menu=list&_table_={$table}');return false;";
					$rtn .= ' onclick="'.$onclick.'"';
					$rtn .= ' accesskey="a"';
		            }
				$rtn .= '>';
				$rtn .= '<span alt="'.$title.'" class="'.$spanclass.'"></span> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</a> '.PHP_EOL;
		        }
			break;
		}
	unset($tableoptions);
	return $rtn;
	}


/* sync and diff functions used in admin.php */
//---------- begin function syncGetChanges ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function syncGetChanges($stables=array()){
	//exclude:true
	global $SETTINGS;
	$info=array(
		'db_stage'	=> $SETTINGS['wasql_synchronize_master'],
		'db_live'	=> $SETTINGS['wasql_synchronize_slave'],
		'db_tables'	=> $stables,
		'active'	=> setValue(array($SETTINGS['wasql_synchronize'],0))
	);
	if($info['active']!=1){return $info;}
	//get live and stage users
	$info['users_stage']=getDBRecords(array('-query'=>"select _id,username from {$info['db_stage']}._users",'-index'=>'_id'));
	$info['users_live']=getDBRecords(array('-query'=>"select _id,username from {$info['db_live']}._users",'-index'=>'_id'));
	//read in stage_records
	$sync_recs=array();
	$checkfields=array('name','title','description','fieldname','tablename','headline','url');
	foreach($info['db_tables'] as $table){
		$fields=adminGetSynchronizeFields($table);
		if(!is_array($fields)){
			echo '<div class="w_red"><span class="icon-warning w_danger w_bold"></span> '.$table.' has no sync fields defined.</div>'.PHP_EOL;
			continue;
		}
		//determine the field to use as a name field.
		unset($namefield);
		foreach($checkfields as $checkfield){
			if(in_array($checkfield,$fields)){$namefield=$checkfield;break;}
		}
		if(!isset($namefield)){
			echo '<div class="w_red"><span class="icon-warning w_danger w_bold"></span>'.PHP_EOL;
			echo "	No namefield specified in stage for {$table}:".implode(', ',$fields)."<br />\n";
			echo "	One of the following fields must be in the table and marked as syncronize in the meta data:".implode(', ',$checkfields)."<br />\n";
			echo '</div>'.PHP_EOL;
			echo "<div>Synchronize Fields: ".implode(', ',$fields)."</div>\n";
			continue;
			}
		$fparts=array();
		foreach($fields as $field){
			//$fparts[]="sha1({$field}) as {$field}, length({$field}) as _{$field}_length";
			$fparts[]="sha1({$field}) as {$field}";
			$info['fields_stage'][$table][]=$field;
		}
		$fpartstr=implode(", ",$fparts);
		if($table=='_fielddata'){$namefield="CONCAT(tablename,'->',fieldname)";}
		elseif($table=='_tabledata'){$namefield="tablename";}
		$query="select _id,_euser,_edate,_cuser,_cdate,{$namefield} as _name,{$fpartstr} from {$info['db_stage']}.{$table}";
		$info['query_stage'][$table]=$query;
		//echo "{$query}<br>\n";
		$info['recs_stage'][$table]=getDBRecords(array('-query'=>$query,'-index'=>'_id'));
		//echo "<br>after<hr>\n";
		//live
		unset($namefield);
		foreach($checkfields as $checkfield){
			if(in_array($checkfield,$fields)){$namefield=$checkfield;break;}
		}
		if(!isset($namefield)){
			echo '<div class="w_red"><span class="icon-warning w_danger w_bold"></span>'.PHP_EOL;
			echo "	No namefield specified in stage for {$table}:".implode(', ',$fields)."<br />\n";
			echo "	One of the following fields must be in the table and marked as syncronize in the meta data:".implode(', ',$checkfields)."<br />\n";
			echo '</div>'.PHP_EOL;
			continue;
			}
		$fparts=array();
		foreach($fields as $field){
			$fparts[]="sha1({$field}) as {$field}";
			$info['fields_live'][$table][]=$field;
		}
		$fpartstr=implode(", ",$fparts);
		$query="select _id,_euser,_edate,_cuser,_cdate,{$namefield} as _name,{$fpartstr} from {$info['db_live']}.{$table}";
		$info['query_live'][$table]=$query;
		//echo printValue($fields);
		//echo "{$query}<br>\n";
		$info['recs_live'][$table]=getDBRecords(array('-query'=>$query,'-index'=>'_id'));
		//echo "<br>after<hr>\n";
		//echo printValue($info);
		//now determine what records are different from stage and live
		$info['recs_diff'][$table]=array();
		foreach($info['recs_stage'][$table] as $index=>$stagerec){
			if(stringContains($stagerec['_name'],'->_')){continue;}
			if(!isset($info['recs_live'][$table][$index])){
            	//new record on stage
            	$stage_userid=$stagerec['_euser'];
            	if(!isNum($stagerec['_euser'])){$stage_userid=$stagerec['_cuser'];}
				if(isset($info['users_stage'][$stage_userid]['username'])){$stage_user=$info['users_stage'][$stage_userid]['username'];}
            	else{$stage_user='Unknown';}
				$actions=' <a href="#" onclick="return ajaxGet(\'\',\'modal\',\'_menu=synchronize&sync_action=sync&sync_items[]='.$index.'--'.$table.'\');"><span class="icon-sync-push w_big w_warning"></span></a>';
            	$actions.=' <a href="/php/admin.php?_menu=edit&_table_='.$table.'&_id='.$index.'"><span class="icon-edit"></span></a>';
				$for="inp_{$index}_{$table}";
            	$stage_date=setValue(array($stagerec['_edate'],$stagerec['_cdate'],'unknown'));
            	$age=time()-strtotime($stage_date);
				$sync_recs[$table][]=array(
					'_id'			=> '<input onclick="highlightObj(\''.$for.'_row\',this.checked,\'#fbd26c\');" type="checkbox" group="'.$table.'_syncrec" id="'.$for.'" name="sync_items[]" value="'."{$index}--{$table}".'"> <label for="'.$for.'">'.$index.'</label>',
					'tablename'		=> $table,
					'description'	=> $stagerec['_name'],
					'changes'		=> 'new record',
					'user_stage'	=> $stage_user,
					'date_stage'	=> $stage_date,
					'edit_age'		=> verboseTime($age),
					'user_live'		=> 'n/a',
					'date_live'		=> 'n/a',
					'actions'		=> $actions,
					'rowid'			=> $for.'_row'
				);
				continue;
			}
			$changes=array();
			foreach($stagerec as $field=>$sha){
				if(isWasqlField($field)){continue;}
				//if($info['recs_stage'][$table][$index]["_{$field}_length"]==0 && $info['recs_live'][$table][$index]["_{$field}_length"]==0){continue;}
				$sha2=$info['recs_live'][$table][$index][$field];
				if(strlen($sha) && !strlen($sha2)){$changes[]="'{$field}' is new";}
				elseif($info['recs_live'][$table][$index][$field] != $sha){$changes[]="'{$field}' has changed";}
			}
			//if($table=='_pages'){echo "Pages Changes".printValue($changes);}
			if(count($changes)){
            	//changes found
            	$changestr=implode("\n",$changes);
            	$for="inp_{$index}_{$table}";
            	$actions='<label style="cursor:pointer" for="'.$for.'" onclick="ajaxGet(\'\',\'modal\',\'_menu=synchronize&sync_action=diff_table&diff_table='.$table.'&diff_id='.$index.'\');"><span class="icon-sync-diff w_big w_info"></span></label>';
            	$actions.=' <a href="#" onclick="return ajaxGet(\'\',\'modal\',\'_menu=synchronize&sync_action=sync&sync_items[]='.$index.'--'.$table.'\');"><span class="icon-sync-push w_big w_warning"></span></a>';
            	$actions.=' <a href="/php/admin.php?_menu=edit&_table_='.$table.'&_id='.$index.'"><span class="icon-edit"></span></a>';
				//check to see if live is newer - if so place an alert icon
            	$date_live_val=setValue(array($info['recs_live'][$table][$index]['_edate'],$info['recs_live'][$table][$index]['_cdate'],'unknown'));
            	$date_stage_val=setValue(array($stagerec['_edate'],$stagerec['_cdate'],'unknown'));
            	if(!strlen($date_live_val) || strtotime($date_live_val) > strtotime($date_stage_val)){
					$date_live_val .= ' <span class="icon-warning w_danger" title="Warning: Live Record is NEWER!" alt="warning: live record is newer"></span> ';
				}
				$stage_userid=$stagerec['_euser'];
				if(isset($info['users_stage'][$stage_userid]['username'])){$stage_user=$info['users_stage'][$stage_userid]['username'];}
				else{$stage_user='Unknown';}
				$live_userid=$info['recs_live'][$table][$index]['_euser'];
				if(isset($info['users_live'][$live_userid]['username'])){$live_user=$info['users_live'][$live_userid]['username'];}
				else{$live_user='Unknown';}
				$age=time()-strtotime($date_stage_val);
				$sync_recs[$table][]=array(
					'_id'			=> '<input onclick="highlightObj(\''.$for.'_row\',this.checked,\'#fbd26c\');" type="checkbox" group="'.$table.'_syncrec" id="'.$for.'" name="sync_items[]" value="'."{$index}--{$table}".'"> <label for="'.$for.'">'.$index.'</label>',
					'tablename'		=> $table,
					'description'	=> $stagerec['_name'],
					'changes'		=> nl2br($changestr),
					'user_stage'	=> $stage_user,
					'date_stage'	=> $date_stage_val,
					'edit_age'		=> verboseTime($age),
					'user_live'		=> $live_user,
					'date_live'		=> $date_live_val,
					'actions'		=> $actions,
					'rowid'			=> $for.'_row'
				);
			}
		}
	}
	//check for schema changes
	$stage_tables=getDBTables($info['db_stage'],1);
	$live_tables=getDBTables($info['db_live'],1);
	$schema=array();
	foreach($stage_tables as $table){
		$current_schema=trim(getDBSchemaText("{$info['db_stage']}.{$table}",1));
		if(!strlen($current_schema)){continue;}
		$lines=preg_split('/[\r\n]+/', $current_schema);
		sort($lines);
		$linestr=implode("\n",$lines);
		$schema[$table]['stage']=sha1($linestr);
	}
	foreach($live_tables as $table){
		$current_schema=trim(getDBSchemaText("{$info['db_live']}.{$table}",1));
		if(!strlen($current_schema)){continue;}
		$lines=preg_split('/[\r\n]+/', $current_schema);
		sort($lines);
		$linestr=implode("\n",$lines);
		$schema[$table]['live']=sha1($linestr);
	}
	foreach($schema as $table=>$rec){
    	if(!isset($rec['live']) || !strlen($rec['live'])){
			$actions=' <a href="/php/admin.php?_menu=properties&_table_='.$table.'"><span class="icon-properties w_danger" title="Properties"></span></a>';
			//new table in stage
        	$sync_recs['_schema'][]=array(
				'_id'			=> '<input onclick="highlightObj(\''.$table.'_row\',this.checked,\'#fbd26c\');" type="checkbox" group="_schema_'.$table.'_syncrec" name="sync_items[]" value="'."schema--{$table}".'">',
				'tablename'		=> $table,
				'description'	=> "schema",
				'changes'		=> "'{$table}' is new",
				'user_stage'	=> 'n/a',
				'date_stage'	=> 'n/a',
				'edit_age'		=> 'n/a',
				'user_live'		=> 'n/a',
				'date_live'		=> 'n/a',
				'actions'		=> $actions,
				'rowid'			=> $table.'_row'
			);
		}
		elseif($rec['stage'] != $rec['live']){
			//table schema has changed in stage
			$actions='<a href="#" onclick="ajaxGet(\'\',\'modal\',\'_menu=synchronize&sync_action=diff_schema&diff_table='.$table.'\');return false;"><span class="icon-sync-diff w_big w_info"></span></a>';
			$actions .=' <a href="/php/admin.php?_menu=properties&_table_='.$table.'"><span class="icon-properties w_danger" title="properties"></span></a>';
        	$sync_recs['_schema'][]=array(
				'_id'			=> '<input onclick="highlightObj(\''.$table.'_row\',this.checked,\'#fbd26c\');" type="checkbox" group="_schema_'.$table.'_syncrec" name="sync_items[]" value="'."schema--{$table}".'">',
				'tablename'		=> $table,
				'description'	=> "schema",
				'changes'		=> "'{$table}' schema has changed",
				'user_stage'	=> 'n/a',
				'date_stage'	=> 'n/a',
				'edit_age'		=> 'n/a',
				'user_live'		=> 'n/a',
				'date_live'		=> 'n/a',
				'actions'			=> $actions,
				'rowid'			=> $table.'_row'
			);
		}
	}
	return $sync_recs;
}

//---------- begin function adminShowSyncChanges ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminShowSyncChanges($stables=array()){
	//exclude:true
	global $USER;
	//clear the cache
    $databaseCache=array();
    //get changes
    $changes=syncGetChanges($stables);

    if(!is_array($changes) || !count($changes)){
        return 'No Changes Found';
	}
	$rtn='';
	//display change tabs by table using btn-group of bootstrap
	$rtn .= '	<div  class="w_align_left">'.PHP_EOL;
	$rtn .= '	<div class="btn-group">'.PHP_EOL;
	foreach($changes as $table=>$recs){
		//get changes for the current user
		$change_user_count=0;
		foreach($recs as $rec){
			if($rec['user_stage']==$USER['username']){$change_user_count+=1;}
		}
		if($table=='_schema'){$change_user_count='';}
    	$change_count=count($changes[$table]);
        $syncTableDiv='sync'.$table.'div';
        $syncTableTab='sync'.$table.'tab';
		$img='';
		$src=getImageSrc($table);
		if(strlen($src)){$img='<img src="'.$src.'" class="w_bottom" alt="" /> ';}
        $rtn .= '		<button type="button" class="btn" data-group="synctabletabs" data-div="'.$syncTableDiv.'" id="'.$syncTableTab.'" onclick="syncTableClick(this);">'.PHP_EOL;
        $rtn .= '			<sup title="Your change count">'.$change_user_count.'</sup> '.$img.$table.' <sup title="Total count">'.$change_count.'</sup>'.PHP_EOL;
        $rtn .= '		</button>'.PHP_EOL;
	}
	$rtn .= '	</div>'.PHP_EOL;
	//build each table's changes in a hidden div
	$formname='syncform';
	$rtn .=  buildFormBegin('',array('_menu'=>"synchronize",'-name'=>$formname,'-onsubmit'=>"ajaxSubmitForm(this,'modal');return false;"));
	$rtn .= '	<input type="hidden" name="sync_action" value="">'.PHP_EOL;
	$rtn .= '	<div id="syncDiv"></div>'.PHP_EOL;
	unset($first_table);
	$rtn .= '	<div id="syncTableHidden" style="display:none;">'.PHP_EOL;
	foreach($changes as $table=>$recs){
		if(!isset($first_table)){$first_table=$table;}
		$syncTableDiv='sync'.$table.'div';
		$syncTableTab='sync'.$table.'tab';
		$rtn .= '		<div table="'.$table.'" id="'.$syncTableDiv.'">'.PHP_EOL;
		$rtn .=  databaseListRecords(array(
			'-list'			=> $changes[$table],'_id_align'=>"left",
			'-tableclass'	=> "table table-responsive table-bordered table-striped",
			'-tableid'		=> "synctable{$table}",
			'diff_align'	=> "center",
			'synctab'		=> $table,
			'actions_nolink'	=> 1,
			'actions_align'	=> "right",
			'_id_nolink'	=> 1,
			'-rowid'		=> "%rowid%",
			'-listfields'	=> "_id,tablename,description,changes,user_stage,date_stage,edit_age,user_live,date_live,actions",
			'-sync'			=> 1,
			'_id_displayname'=> '<input id="'.$table.'_checkall" type="checkbox" onclick="checkAllElements(\'group\',\''.$table.'_syncrec\', this.checked);"><label for="'.$table.'_checkall"> ID</label>'
			));
		$rtn .= '		</div>'.PHP_EOL;
	}
	$rtn .= '	</div>'.PHP_EOL;
	$rtn .= '	</div>'.PHP_EOL;
	//show the first table or the one they sorted by
	$showtable=isset($_REQUEST['synctab'])?$_REQUEST['synctab']:$first_table;
	$syncTableTab='sync'.$showtable.'tab';
	$rtn .=  buildOnLoad("syncTableClick('{$syncTableTab}');");
	//show sync and cancel buttons
	$rtn .= '<br clear="both" />'.PHP_EOL;
	$rtn .= '<button type="button" class="btn" onclick="document.'.$formname.'.sync_action.value=\'sync\';ajaxSubmitForm(document.'.$formname.',\'modal\');return false;"><span class="icon-sync-push w_big w_warning"></span> Push Changes Live</button>'.PHP_EOL;
	$rtn .= '<button type="button" class="btn red" onclick="if(!confirm(\'Cancel selected changes on stage and restore back to live?\')){return false;}document.'.$formname.'.sync_action.value=\'cancel\';ajaxSubmitForm(document.'.$formname.',\'modal\');return false;"><span class="icon-sync-pull w_big w_danger"></span> Restore from Live</button>'.PHP_EOL;
	$rtn .=  buildFormEnd();
	return $rtn;
}
//------------------------------ editor funcitons ----------------------------
//---------- begin function editorFileEdit ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function editorFileEdit($file){
	switch(strtolower(getFileExtension($file))){
    	case 'js':$behavior='jseditor';break;
    	case 'css':$behavior='csseditor';break;
    	case 'php':$behavior='phpeditor';break;
    	case 'xml':$behavior='txteditor';break;
    	default:$behavior='phpeditor';break;
	}
	$content=getFileContents($file);
	$rtn = '';
	$rtn .= '<div style="position:relative;">'.PHP_EOL;
	$rtn .= buildFormBegin($_SERVER['PHP_SELF'],array('emenu'=>'file','_menu'=>"editor",'file'=>$file,'-onsubmit'=>"ajaxSubmitForm(this,'modal');return false;"));
	$rtn .= '<table class="w_pad"><tr><td>'.PHP_EOL;
	$rtn .= '	<div class="w_bold w_bigger w_dblue">Editing File: '.$file.'</div>'.PHP_EOL;
	$rtn .= '</td><td>'.buildFormSubmit('Save').'</td></tr></table>'.PHP_EOL;
	$rtn .= '<div style="border:1px inset #000;width:800px;">'.PHP_EOL;
	$rtn .= '<textarea name="file_content" data-behavior="'.$behavior.'" style="width:800px;height:400px;">'.PHP_EOL;
	$rtn .= encodeHtml($content);
	$rtn .= '</textarea>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= buildFormSubmit('Save');
	$rtn .= buildFormEnd();
	$rtn .= '</div>'.PHP_EOL;
 	return $rtn;
}

//---------- begin function editorFileAdd ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function editorFileAdd($filetype){
	switch(strtolower($filetype)){
    	case 'js':$behavior='jseditor';break;
    	case 'css':$behavior='csseditor';break;
	}
	$rtn = '';
	$rtn .= '<div style="position:relative;">'.PHP_EOL;
	$rtn .= '<table class="w_pad"><tr><td><div class="w_bold w_bigger w_dblue">New '.strtoupper($filetype).' File</div></td><td><div id="w_editor_status"></div></td></tr></table>'.PHP_EOL;
	$rtn .= buildFormBegin($_SERVER['PHP_SELF'],array('-name'=>'addedit','emenu'=>'file','filetype'=>$filetype,'-onsubmit'=>"ajaxSubmitForm(this,'modal');return false;"));
	$rtn .= '<div style="margin-bottom:5px;">FileName: <input type="text" name="filename" data-required="1" style="width:400px;">.'.$filetype.'</div>'.PHP_EOL;
	$rtn .= '<div style="border:1px inset #000;width:800px;">'.PHP_EOL;
	$rtn .= '<textarea name="file_content" data-behavior="'.$behavior.'" style="width:800px;height:400px;">'.PHP_EOL;
	$rtn .= '</textarea>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= buildFormSubmit('Save');
	$rtn .= buildFormEnd();
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= buildOnLoad("document.addedit.filename.focus();");
 	return $rtn;
}

//---------- begin function contentManager ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function contentManager(){
	$rtn = '<div style="height:600px;overflow:auto;padding-right:25px;">'.PHP_EOL;
	//pages
	$expand='';
	$recs=getDBRecords(array('-table'=>'_pages','page_type'=>1,'-order'=>'name','-index'=>'_id','-fields'=>"_id,name,permalink,_template"));
	$ico='<img src="/wfiles/_pages.gif" class="w_middle" alt="pages" />';
	$title=$ico.' Pages ('.count($recs).')';
	//add new link
	//$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=contentmanager&emenu=add&table=_pages\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" class="w_middle"> add new</a></div>'.PHP_EOL;
	$expand .= buildTableBegin(2,0);
	$expand .= '	<tbody>'.PHP_EOL;
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td><a href="#" onclick="removeTinyMCE(\'txtfield_content\');return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=contentmanager&emenu=edit&table=_pages&id='.$id.'\');" class="w_link w_lblue w_block">'.$rec['name'].'</a></td></tr>'.PHP_EOL;
	}
	$expand .= '	</tbody>'.PHP_EOL;
	$expand .= buildTableEnd();
	return $expand;
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	return $rtn;
}

//---------- begin function editorNavigation ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function editorNavigation(){
	$rtn = '<div style="height:600px;overflow:auto;padding-right:25px;">'.PHP_EOL;
	//files
	if(isset($_SERVER['DOCUMENT_ROOT'])){
		$expand='';
		$files=listFilesEx($_SERVER['DOCUMENT_ROOT']);
		$cnt=0;
		foreach($files as $file){
			if(!isTextFile($file['name'])){continue;}
			$cnt++;
			$ico=getFileIcon($file['name']);
			$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$ico.' '.$file['name'].'</a></div>'.PHP_EOL;
		}
		$ico=getFileIcon("x.log");
		$title=$ico.' Root Files ('.$cnt.')';
		$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	}

	//.htaccess
	if(is_file(realpath("../.htaccess"))){
		echo '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file=../.htaccess\');" class="w_link w_lblue"><span class="icon-gear"></span> .htaccess</a></div>'.PHP_EOL;
	}
	//Configuration
	echo '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file=../config.xml\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/xml.png" class="w_middle" alt="config.xml" /> config.xml</a></div>'.PHP_EOL;
	//templates
	$recs=getDBRecords(array('-table'=>'_templates','-order'=>'name','-index'=>'_id','-fields'=>'_id,name'));
	$ico='<span class="icon-file-docs w_grey"></span>';
	$title=$ico.' Templates ('.count($recs).')';
	$expand='';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&table=_templates\');" class="w_link w_lblue"><span class="icon-plus"></span> add new</a></div>'.PHP_EOL;
	$expand .= buildTableBegin(1,1,1);
	$expand .= buildTableTH(array('ID','Name'),array('thead'=>1));
	$expand .= '	<tbody>'.PHP_EOL;
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td>'.$rec['_id'].'</td><td><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&table=_templates&id='.$id.'\');" class="w_link w_lblue">'.$rec['name'].'</a></td></tr>'.PHP_EOL;
	}
	$expand .= '	</tbody>'.PHP_EOL;
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	//pages
	$expand='';
	$recs=getDBRecords(array('-table'=>'_pages','-order'=>'name','-index'=>'_id','-fields'=>"_id,name,permalink,_template"));
	$ico='<img src="/wfiles/_pages.gif" class="w_middle" alt="pages" />';
	$title=$ico.' Pages ('.count($recs).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&table=_pages\');" class="w_link w_lblue"><span class="icon-plus"></span> add new</a></div>'.PHP_EOL;
	$expand .= buildTableBegin(1,1,1);
	$expand .= buildTableTH(array('ID','Name','TID'),array('thead'=>1));
	$expand .= '	<tbody>'.PHP_EOL;
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td>'.$rec['_id'].'</td><td><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&table=_pages&id='.$id.'\');" class="w_link w_lblue">'.$rec['name'].'</a></td><td>'.$rec['_template'].'</td></tr>'.PHP_EOL;
	}
	$expand .= '	</tbody>'.PHP_EOL;
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);

	//Custom CSS
	$expand='';
	$files=listFilesEx('../wfiles/css/custom',array('ext'=>"css"));
	$ico=getFileIcon("x.css");
	$title=$ico.' Custom CSS Files ('.count($files).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&filetype=css\');" class="w_link w_lblue"><span class="icon-plus"></span> add new</a></div>'.PHP_EOL;
	foreach($files as $file){
    	$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$file['name'].'</a></div>'.PHP_EOL;
	}
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);

	//Custom Js
	$expand='';
	$files=listFilesEx('../wfiles/js/custom',array('ext'=>"js"));
	$ico=getFileIcon("x.js");
	$title=$ico.' Custom Js Files ('.count($files).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&filetype=js\');" class="w_link w_lblue"><span class="icon-plus"></span> add new</a></div>'.PHP_EOL;
	foreach($files as $file){
    	$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$file['name'].'</a></div>'.PHP_EOL;
	}
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);

	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function adminCronBoard ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminCronBoard(){
	$rtn='';
	$rtn .= '<div class="w_round w_dropshadow" style="background:#FFF;border:1px solid #b9b9b9;padding:3px 0 7px 0;margin-top:0px;" align="center">'.PHP_EOL;
	//return printValue($recs);
	$rtn .= buildTableBegin(2,0);
	$rtn .= '	<tr>'.PHP_EOL;
	$rtn .= '		<td><a href="/php/admin.php?_menu=list&_table_=_cron"><span class="icon-cron w_success w_big"></span></a></td>'.PHP_EOL;
	$rtn .= '		<td colspan="5" class="w_bold w_dblue" align="center" style="font-size:1.1em;">Cron Activity Dashboard</td>'.PHP_EOL;
	$rtn .= '		<td align="right"><div title="Update Timer" data-behavior="countdown" class="w_lblue w_smaller" id="cronboard_countdown">31</div></td>'.PHP_EOL;
	$rtn .= '		<td align="right"><a href="/php/admin.php?_menu=list&_table_=_cronlog"><img src="/wfiles/_cronlog.png" class="w_middle" title="goto CronLog" alt="cron log" /></a></td>'.PHP_EOL;
	$rtn .= '	</tr>'.PHP_EOL;
	$rtn .= buildTableTH(array('','Crons','Active','Inactive','Running','MaxRun','Listening','Logs'));
	//base query
	$basequery="select
		count_crons,count_crons_active,count_crons_inactive,
		count_crons_running,count_crons_listening,count_cronlogs
	from _cronlog[W] order by _id desc limit 1";
	$wheres=array(
		'Day'	=> " where year(run_date)=year(now()) and dayofyear(run_date)=dayofyear(now())",
		'Month'	=> " where year(run_date)=year(now()) and month(run_date)=month(now())",
		'All Time'	=> "",
	);
	$pcnt=isWindows()?'n/a':getProcessCount('cron.pl');
	foreach($wheres as $t=>$where){
		$query="select
			count_crons,count_crons_active,count_crons_inactive,
			count_crons_running,count_crons_listening,count_cronlogs
		from _cronlog {$where} order by _id desc limit 1";
		$rec=getDBRecord(array('-query'=>$query));
		$query="select max(count_crons_running) as maxrun from _cronlog {$where}";
		$xrec=getDBRecord(array('-query'=>$query));
		$rec['maxrun']=$xrec['maxrun'];
		if(!isset($rec['maxrun'])){$rec['maxrun']=0;}
		$rtn .= '	<tr>'.PHP_EOL;
		$rtn .= '		<td>'.$t.'</td>'.PHP_EOL;
		//crons
		$bg=$rec['count_crons']==0?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons'].'</td>'.PHP_EOL;
		//active
		$bg=$rec['count_crons_active']==0?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_active'].'</td>'.PHP_EOL;
		//inactive
		$bg='';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_inactive'].'</td>'.PHP_EOL;
		//running
		$bg=$rec['count_crons_running'] > ($rec['count_crons']/2)?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_running'].'</td>'.PHP_EOL;
		//MaxRun
		$bg='';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['maxrun'].'</td>'.PHP_EOL;
		//listening
		$bg=$rec['count_crons_listening'] < ($rec['maxrun']*1.5)?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_listening'].'</td>'.PHP_EOL;
		//Logs
		$bg=$rec['logs'] > 100000?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_cronlogs'].'</td>'.PHP_EOL;
		$rtn .= '	</tr>'.PHP_EOL;
	}
	$rtn .= buildTableEnd();
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function adminGetSynchronizeTables ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminGetSynchronizeTables($dbname=''){
	$getopts=array(
		'-table'		=> "_tabledata",
		'synchronize'	=> 1,
		'-index'		=> "tablename",
		'-fields'		=> "tablename"
	);
	if(strlen($dbname)){$getopts['-table']="{$dbname}._tabledata";}
	$recs=getDBRecords($getopts);
	if(!is_array($recs)){return $recs;}
	$tables=array();
	foreach($recs as $table=>$rec){
    	if(!getDBCount(array('-table'=>$table))){continue;}
    	$tables[]=$table;
	}
	return $tables;
}

//---------- begin function adminGetSynchronizeFields ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminGetSynchronizeFields($table){
	global $SETTINGS;
	$recopts=array(
		'-table'=>"_fielddata",
		'tablename'	=> $table,
		'-index'	=> "fieldname",
		'-fields'	=> "fieldname,synchronize"
	);
	//echo printValue($recopts);exit;
	$recs=getDBRecords($recopts);
	//echo $table.printValue($recs);
	//if(!is_array($recs)){return $recs;}
	$flds=array();
	$fields=getDBFieldInfo($table);
	$skip=array('css_min','js_min');
	foreach($fields as $field=>$info){
    	if(isset($recs[$field]) && $recs[$field]['synchronize'] !=1){continue;}
    	if(isWasqlField($field)){continue;}
    	if(in_array($field,$skip)){continue;}
    	switch(strtolower($info['_dbtype'])){
			case 'text':
			case 'mediumtext':
			case 'largetext':
			case 'blob':
			case 'varchar':
			case 'char':
				$flds[]=$field;
			break;
		}

	}
	return $flds;
}

//---------- begin function adminSynchronizeRecord ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminSynchronizeRecord($table,$id,$stage=1){
	global $SETTINGS;
	if(!isset($SETTINGS['wasql_synchronize']) || $SETTINGS['wasql_synchronize']==0){
		return 0;
	}
	$db_stage=$SETTINGS['wasql_synchronize_master'];
	$db_live=$SETTINGS['wasql_synchronize_slave'];
	if(!strlen($db_stage) || !strlen($db_live)){
    	return 0;
	}
	if(!isNum($id)){return 0;}
	$fields=adminGetSynchronizeFields($table);
	$stage_rec=getDBRecord(array(
		'-table'	=> "{$db_stage}.{$table}",
		'_id'		=> $id,
		'-fields'	=> $fields
	));
	$live_rec=getDBRecord(array(
		'-table'	=> "{$db_live}.{$table}",
		'_id'		=> $id,
		'-fields'	=> $fields
	));
	//echo printValueHidden($fields,"adminSynchronizeRecord-FIELDS");
	if($stage==1){
		//push to live
		$opts=$stage_rec;
		$opts['-table']= "{$db_live}.{$table}";
		if(is_array($live_rec)){
			unset($opts['_id']);
			foreach($live_rec as $key=>$val){
            	if(sha1($val)==sha1($opts[$key])){unset($opts[$key]);}
			}
			$opts['-where']="_id={$id}";
	    	$ok=editDBRecord($opts);
	    	//echo printValueHidden(array('result'=>$ok,'opts'=>$opts),"adminSynchronizeRecord-EDIT");
		}
		else{
	    	$opts['_id']=$id;
	    	foreach($opts as $key=>$val){
            	if(!strlen(trim($val))){unset($opts[$key]);}
			}
	    	$ok=addDBRecord($opts);
	    	//echo printValueHidden(array('result'=>$ok,'opts'=>$opts),"adminSynchronizeRecord-ADD");
		}
	}
	else{
		//revert from live
		$opts=$live_rec;
		$opts['-table']= "{$db_stage}.{$table}";
    	if(is_array($stage_rec)){
			unset($opts['_id']);
			foreach($stage_rec as $key=>$val){
            	if(sha1($val)==sha1($opts[$key])){unset($opts[$key]);}
			}
			$opts['-where']="_id={$id}";
	    	$ok=editDBRecord($opts);
		}
		else{
	    	$opts['_id']=$id;
	    	foreach($opts as $key=>$val){
            	if(!strlen(trim($val))){unset($opts[$key]);}
			}
	    	$ok=addDBRecord($opts);
		}
	}
	if(isNum($ok) || $ok==''){
		return $ok;
	}
	return $ok.printValue($opts).printValue($live_rec);
}
//---------- begin function adminShowSessionLog ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminSetHeaders(){
	@header("Pragma: no-cache");
	@header("Cache-Control: no-cache, no-store, must-revalidate");
	@header("Expires: 0");
	@header('X-Platform: WaSQL');
	@header('X-Frame-Options: SAMEORIGIN');
}
