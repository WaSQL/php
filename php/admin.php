<?php
//Set upload size
//Set Post Max Size
ini_set('POST_MAX_SIZE', '34M');
ini_set('UPLOAD_MAX_FILESIZE', '30M');
ini_set('max_execution_time', 5000);
set_time_limit(5500);
date_default_timezone_set('America/Denver');
/*
	Items Left to impliment:
		Add csv export to listDBRecords
		<label> hover on checkbox and radio - label outside so spaces also work?

	inline functions
	check for json string and use values when adding field and table metadata
	{group:,field gender is available to these groups}

	In tabledata, create a crud_functions field that stores pre and post functions
		$crud=array(
			'pre_create'	=> " function stuff goes here",
			'pre_read'		=> " function stuff goes here",
			'pre_update'	=> " function stuff goes here",
			'pre_delete'	=> " function stuff goes here",
			'post_create'	=> " function stuff goes here",
			'post_read'		=> " function stuff goes here",
			'post_update'	=> " function stuff goes here",
			'post_delete'	=> " function stuff goes here",
		);
	add a $_REQUEST['add_crud']=array(ones that got processed);
	DONE: create a code test area where you can enter code to test and run it without creating a new page record
*/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$progpath=dirname(__FILE__);
include_once("$progpath/common.php");
//load our own session handling routines
include_once("$progpath/sessions.php");
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
if(isset($_REQUEST['phpinfo']) && count($_REQUEST)==1){
	phpinfo();
	exit;
}
elseif(isset($_REQUEST['env']) && count($_REQUEST)==1){
	include_once("$progpath/user.php");
	echo buildHtmlBegin();
	echo '<div class="w_lblue w_bold"><img src="/wfiles/server.png" border="0"> REMOTE Variables</div>'."\n";
	echo buildTableBegin(2,1);
	echo buildTableTH(array('Variable','Value'));
	foreach($_SERVER as $key=>$val){
		if(!stringBeginsWith($key,'remote') && !stringBeginsWith($key,'http')){continue;}
		echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
        }
    echo buildTableEnd();
    echo buildHtmlEnd();
	exit;
}
include_once("$progpath/config.php");
global $CONFIG;
//is SSL required for admin?
if(isset($CONFIG['admin_secure']) && in_array($CONFIG['admin_secure'],array(1,'true')) && !isSecure()){
	header("Location: https://{$_SERVER['HTTP_HOST']}/admin",true,301);
	exit;
}

$_REQUEST['debug']=1;

include_once("$progpath/user.php");
include_once("$progpath/database.php");

include_once("$progpath/wasql.php");
include_once("$progpath/schema.php");
if(isset($_REQUEST['sqlprompt']) && strtolower($_REQUEST['sqlprompt'])=='csv export'){
    //export the query to csv
    $recs=getDBRecords(array('-query'=>stripslashes($_REQUEST['sqlprompt_command'])));
    $data=arrays2CSV($recs);
    pushData($data,'csv','sqlprompt_export.csv');
    exit;
}

global $wtables;
$wtables=getWasqlTables();
foreach($wtables as $wtable){
	if(!isDBTable($wtable)){$ok=createWasqlTable($wtable);}
}

//get_magic_quotes_gpc fix if it is on
wasqlMagicQuotesFix();
//require user
global $USER;
global $PAGE;
global $TEMPLATE;
global $databaseCache;
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
$wasql_version=wasqlVersion();
//process actions
if(isset($_SERVER['CONTENT_TYPE']) && preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
 	if(isset($_REQUEST['processFileUploads']) && $_REQUEST['processFileUploads']=='off'){}
 	else{processFileUploads();}
    }
if(isset($_REQUEST['_action']) && $_REQUEST['_action']=='settings' && $_REQUEST['_setfield']=='_admin_settings_'){
	$PrevConfigSettings=getDBAdminSettings();
	if($PrevConfigSettings['logo'] && !$_REQUEST['logo_remove']){
    	foreach($PrevConfigSettings as $key=>$val){
        	if(preg_match('/^logo/i',$key) && !strlen($_REQUEST[$key])){$_REQUEST[$key]=$val;}
		}
		unset($_REQUEST['logo_error']);
		unset($_REQUEST['logo_prev']);
	}
}
if(isset($_REQUEST['_action'])){
 	$ok=processActions();
   	}
if(isset($_REQUEST['_pushfile'])){
 	$ok=pushFile(decodeBase64($_REQUEST['_pushfile']));
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
    	//echo '<div style="font-size:24px;display:inline;color:#dd8602;"><img width="32" height="32" src="/wfiles/iconsets/32/wasql.png" border="0" style="vertical-align:middle;border-right:1px solid #dd8602;border-bottom:1px solid #dd8602;padding:3px;"><b style="border-bottom:1px solid #dd8602;"> '.ucwords($_SERVER['HTTP_HOST']).' Admin Login</b></div>'."\n";
		//echo '<div style="padding:5px;color:#dd8602;">'.userLoginForm(array('-action'=>'/php/admin.php')).'</div>'."\n";
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
		case 'datasync':
			echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/database_synchronize.png" border="0" class="w_middle"> Synchronize Records in '.$_REQUEST['tablename'].'</div>'."\n";
			echo '<div class="w_centerpop_content">'."\n";
			global $SETTINGS;
			//synchronize must be turned on
			if($SETTINGS['wasql_synchronize'] != 1){
            	echo '	<div class="w_red w_bold">Synchronize must be turned on for this feature. Go to settings to turn on.</div>'."\n";
            	echo '</div>'."\n";
            	exit;
				break;
			}
			$stage_db=$SETTINGS['wasql_synchronize_master'];
	        $live_db=$SETTINGS['wasql_synchronize_slave'];
			if(!strlen($stage_db) || !strlen($live_db)){
				echo '	<div class="w_red w_bold">Synchronize is turned on but databases are not selected. Go to settings to fix this.</div>'."\n";
            	echo '</div>'."\n";
            	exit;
				break;
			}
			if(!strlen($_REQUEST['tablename'])){
            	echo "	Invalid request";
            	echo '</div>'."\n";
            	exit;
            	break;
			}
			$table=$_REQUEST['tablename'];
			$fields=getDBFields($_REQUEST['tablename'],1);
			$fieldstr=join(', ',$fields);
			$queries=array();
			switch(strtolower($_REQUEST['func'])){
            	case 'push':
            		//sync records from stage to live
            		$queries=array(
						"TRUNCATE {$live_db}.{$table}",
						"INSERT INTO {$live_db}.$table ({$fieldstr}) SELECT {$fieldstr} FROM {$stage_db}.{$table}"
					);
            	break;
            	case 'pull':
            		//sync records from live to stage
            		$queries=array(
						"TRUNCATE {$stage_db}.{$table}",
						"INSERT INTO {$stage_db}.$table ({$fieldstr}) SELECT {$fieldstr} FROM {$live_db}.{$table}"
					);
            	break;
            	default:
            		echo "invalid request";
            	break;
			}
			if(count($queries)){
				echo '<div style="width:600px;">'."\n";
            	foreach($queries as $query){
	    			$ok=executeSQL($query);
	    			echo "Result: [{$ok['result']}] {$query} <br />\n";
				}
				echo '<div>Done. Refresh to see changes</div>'."\n";
				echo '</div>'."\n";
			}
			echo '</div>'."\n";
			exit;
			break;
    	case 'admin_settings':
    		echo adminSettings();
    		exit;
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
    		echo '<div class="w_centerpop_title"> Add Record '.$_REQUEST['_table_'].'</div>'."\n";
			echo '<div class="w_centerpop_content">'."\n";
			if($_REQUEST['_table_']=='_reports' && isset($_REQUEST['sqlprompt']) && strlen($_REQUEST['sqlprompt_command'])){
				$_REQUEST['query']=$_REQUEST['sqlprompt_command'];
			}
			if(isset($CONFIG['dbname_stage'])){
                $xtables=adminGetSynchronizeTables($CONFIG['dbname_stage']);
                if(in_array($_REQUEST['_table_'],$xtables)){
					echo '<div class="w_bold">'."\n";
					echo '	<img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle">'."\n";
					echo '	"'.$_REQUEST['_table_'].'" is a synchronize table. New records must be added on the staging site.'."\n";
					echo '</div>'."\n";
					echo '</div>'."\n";
					exit;
				}
				else{
					echo addEditDBForm(array(
						'-action'=>'/php/admin.php',
						'-table'=>$_REQUEST['_table_'],
						'_table_'=>$_REQUEST['_table_'],
						'_menu'=>'list',
						'_sort'=>$_REQUEST['_sort'],
						'_start'=>$_REQUEST['_start']
					));
				}
			}
			else{
	    		echo addEditDBForm(array(
					'-action'=>'/php/admin.php',
					'-table'=>$_REQUEST['_table_'],
					'_table_'=>$_REQUEST['_table_'],
					'_menu'=>'list',
					'_sort'=>$_REQUEST['_sort'],
					'_start'=>$_REQUEST['_start']
				));
			}
			echo '</div>'."\n";
			exit;
    		break;
    	case 'manual':
			//echo '<div class="w_centerpop_title">Documentation</div>'."\n";
			//echo '<div class="w_centerpop_content" style="height:600px;overflow:auto;">'."\n";
    		if(isset($_REQUEST['examples'])){
            	switch(strtolower($_REQUEST['examples'])){
                	case 'js':
                		$jspath=realpath("{$progpath}/../wfiles/js");
                		//echo "jspath:{$jspath}";
                		$html=getFileContents("{$jspath}/examples.html");
                		if(preg_match('/<body>(.+)<\/body>/is',$html,$m)){
                        	$html=$m[1];
						}
                		echo evalPHP($html);
                		echo '</div>'."\n";
                		exit;
                	break;
				}
			}
    		if(!is_file("{$progpath}/temp/manual.json")){
				global $Manual;
				wasqlRebuildManual();
			}
			else{
            	$Manual=json_decode(getFileContents("{$progpath}/temp/manual.json"),true);
			}
            echo wasqlBuildManualList();
			echo '</div>'."\n";
    		exit;
    		break;
    	case 'sqlprompt':
    	case 'tabledetails':
    		if(isset($_REQUEST['table'])){
				//echo '<div style="margin-left:15px;">'."\n";
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
				echo buildTableBegin(2,1);
				echo buildTableTH(array('Field','Type','Len','Null','Recs','Nulls'));
				//echo printValue($finfo);
				foreach($finfo as $info){
					echo '	<tr>'."\n";
					echo '		<td>'.$info['_dbfield'].'</td>'."\n";
					echo '		<td>'.$info['_dbtype'].'</td>'."\n";
					echo '		<td>'.$info['_dblength'].'</td>'."\n";
					echo '		<td>'.$info['_dbnull'].'</td>'."\n";
					echo '		<td align="right">'.$info['rec_cnt'].'</td>'."\n";
					echo '		<td align="right">'.$info['null_cnt'].'</td>'."\n";
					echo '	</tr>'."\n";
				}
				echo buildTableEnd();
				//echo printValue($finfo);
				//echo '</div>'."\n";
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
			    		$dname = '<span class="w_bold w_bigger w_dblue">Content for '.$rec['name'].' Page</span>'."\n";

						echo '<div style="position:relative;">'."\n";
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
							'-onsubmit'=>"this._preview.value='';ajaxSubmitForm(this,'centerpop');return false;",
							'-fields'=>'user_content'
							);
						echo addEditDBForm($opts);
						echo '</div>'."\n";
					}
		    		break;
		    	case 'add':
		    		if(isset($_REQUEST['table'])){
						//table record add
			    		echo '<div class="w_centerpop_title">New Record in '.$_REQUEST['table'].' table.</div>'."\n";
						echo '<div class="w_centerpop_content">'."\n";
						echo addEditDBForm(array(
							'-table'=>'_pages',
							'-action'=>$_SERVER['PHP_SELF'],
							'content_width'=>700,
							'name_width'=>700,
							'permalink_width'=>700,
							'description_width'=>700,
							'emenu'=>'record',
							'_menu'=>'contentmanager',
							'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"
							));
						echo '</div>'."\n";
					}
		    		break;
		    	case 'record':
		    		if(isNum($_REQUEST['edit_id']) || isNum($_REQUEST['add_id'])){
						echo '<b style="color:#0d7d0c;">Saved!</b>';
						unset($_REQUEST['edit_rec']);
						//echo printValue($_REQUEST);
						//update menubar
	            		echo '	<div id="w_editor_nav_update" style="display:none;">'."\n";
						echo contentManager();
						echo '	</div>'."\n";
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
				case 'sandbox':
					$sessionID=session_id();
					echo buildFormBegin('',array('_menu'=>'sandbox','preview'=>1,'-onsubmit'=>"ajaxSubmitForm(this,'sandbox_test');return false;"));
					echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/iconsets/32/php.png" border="0" class="w_middle"> PHP Sandbox '.buildFormSubmit('Test Code (F5)').'</div>'."\n";
					echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table" width="100%">'."\n";
					echo buildTableTH(array('Database Tables','PHP Coding Window','Code Results Window'));
					echo '	<tr valign="top">'."\n";
					echo '		<td nowrap>'."\n";
					echo '			<div style="height:500px;overflow:auto;padding-right:30px;">'."\n";
					echo '				' . expandAjaxTables();
					echo '			</div>'."\n";
					echo '		</td>'."\n";
					echo '		<td>'."\n";
					echo '			<textarea focus="2,2" name="sandbox_code" preview="1" ajaxid="sandbox_test" style="width:500px;height:400px;" data-behavior="phpeditor">'."\n";
					echo encodeHtml('<?'.'php')."\r\n\t\r\n";
					echo encodeHtml('?'.'>')."\r\n";
					echo '			</textarea></td>'."\n";
					echo '		<td width="100%"><div id="sandbox_test" style="height:400px;overflow:auto;"></div></td>'."\n";
					echo '	</tr>'."\n";
					echo buildTableEnd();
					echo buildFormSubmit('Test Code (F5)');
					echo buildFormEnd();
					break;
		    	case 'edit':
		    		if(isset($_REQUEST['table']) && isNum($_REQUEST['id'])){
						//table record edit
			    		echo '<div class="w_centerpop_title">Editing Record #'.$_REQUEST['id'].' in '.$_REQUEST['table'].' table.</div>'."\n";
						echo '<div class="w_centerpop_content">'."\n";
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
							'-onsubmit'=>"this._preview.value='';ajaxSubmitForm(this,'centerpop');return false;"
							);
						if($_REQUEST['table']=='_pages'){
		                	$opts['-preview']=$_REQUEST['id'];
						}
						echo addEditDBForm($opts);
						if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['table'])){
							echo buildOnLoad("document.addedit.name.focus();");
						}
						echo '</div>'."\n";
					}
					elseif(isset($_REQUEST['file'])){
						echo editorFileEdit($_REQUEST['file']);
					}
		    		break;
		    	case 'add':
		    		if(isset($_REQUEST['table'])){
						//table record edit
			    		echo '<div class="w_centerpop_title">New Record in '.$_REQUEST['table'].' table.</div>'."\n";
						echo '<div class="w_centerpop_content">'."\n";
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
							'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"
							));
						if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['table'])){
							echo buildOnLoad("document.addedit.name.focus();");
						}
						echo '</div>'."\n";
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
		            		echo '	<div id="w_editor_nav_update" style="display:none;">'."\n";
							echo editorNavigation();
							echo '	</div>'."\n";
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
		            		echo '	<div id="w_editor_nav_update" style="display:none;">'."\n";
							echo editorNavigation();
							echo '	</div>'."\n";
							echo buildOnLoad("setText('w_editor_nav',getText('w_editor_nav_update'));");
						}
						else{
							echo '<b style="color:#a30001;">Failed to save! '.$ok.'</b>';
							echo $afile;
						}
		            	//redraw menu
		            	echo '<div style="display:none" id="navigation_refresh">'."\n";
		            	echo editorNavigation();
		            	echo '</div>'."\n";
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
	            		echo '	<div id="w_editor_nav_update" style="display:none;">'."\n";
						echo editorNavigation();
						echo '	</div>'."\n";
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
			echo '<div style="width:500px;height:300px;padding-right:25px;overflow:auto;">'."\n";
			echo buildTableBegin(2,1);
			echo buildTableTH(array('Tablename','Status','More Info'));
			foreach($schemas as $table=>$fieldstr){
				echo '	<tr valign="top">'."\n";
				//dropDBTable($table,1);
				unset($databaseCache['isDBTable'][$table]);
            	echo "		<td>{$table}</td>";
            	$ok=adminCreateNewTable($table,$fieldstr);
				if(!isNum($ok)){
					echo '<td class="w_red w_bold">FAILED</td><td>'.$ok.'</td>'."\n";
				}
				else{
                	echo '<td>SUCCESS</td><td>'.nl2br($fieldstr).'</td>'."\n";
				}
				echo '</tr>'."\n";
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
	//echo '<div id="adminmenu">'."\n";
	echo adminUserLoginMenu();
	//echo '<br clear="both" />'."\n";
	//echo '<div style="font-size:24px;display:inline;color:#dd8602;"><img width="32" height="32" src="/wfiles/iconsets/32/wasql.png" border="0" style="vertical-align:middle;padding:3px;"><b> '.ucwords($_SERVER['HTTP_HOST']).' Admin Login</b></div>'."\n";
	//echo '</div>'."\n";
	echo '	<div style="padding:5px;color:'.$ConfigSettings['mainmenu_text_color'].';">'."\n";
	$formopts=array(
		'-action'=>'/php/admin.php',
		'-show_icons'=>$ConfigSettings['mainmenu_icons']
		);
	if(isset($_REQUEST['_menu'])){$formopts['_menu']=$_REQUEST['_menu'];}
	if(isset($_REQUEST['_table_'])){$formopts['_table_']=$_REQUEST['_table_'];}
	echo userLoginForm($formopts);
	echo '</div>'."\n";
	echo buildHtmlEnd();
	exit;
	}
elseif($USER['utype'] != 0){
	echo buildHtmlBegin();
	echo '<div class="w_left w_tip w_pad w_border">'."\n";
	echo '	<img src="/wfiles/iconsets/32/abort.png" border="0" class="w_middle"><b class="w_red w_bigger"> Administration access denied.</b>'."\n";
	echo '	<div class="w_big w_red">You must log in as an administrator to access the administration area.</div>'."\n";
	echo '</div>'."\n";
	$formopts=array(
		'-action'=>'/php/admin.php',
		'-show_icons'=>$ConfigSettings['mainmenu_icons']
		);
	if(isset($_REQUEST['_menu'])){$formopts['_menu']=$_REQUEST['_menu'];}
	if(isset($_REQUEST['_table_'])){$formopts['_table_']=$_REQUEST['_table_'];}
	echo userLoginForm($formopts);
	echo buildHtmlEnd();
	exit;
	}
//
if(isset($_REQUEST['_menu']) && strtolower($_REQUEST['_menu'])=='export' && isset($_REQUEST['export']) && is_array($_REQUEST['export'])){
	$isapp=isset($_REQUEST['isapp']) && $_REQUEST['isapp']==1?true:false;
	//echo $isapp . printValue($_REQUEST);exit;
	$xmldata=xmlHeader();
	global $CONFIG;
	$xmldata.='<export dbname="'.$CONFIG['dbname'].'" timestamp="'.time().'">'."\r\n";
	foreach($_REQUEST['export'] as $table){
		//$xmldata .= '<!-- Export for '.$table.' table -->'."\r\n";
		if(isset($_REQUEST[$table.'_Schema']) && $_REQUEST[$table.'_Schema']==1){
			//export Schema
			$fields=getDBSchema(array($table));
			$xmldata .= '<xmlschema name="'.$table.'">'."\r\n";
			$fields=sortArrayByKey($fields,'field');
			foreach($fields as $field){
				$type=$field['type'];
				if($field['null']=='NO'){$type .= ' NOT NULL';}
				else{$type .= ' NULL';}
				if($field['key']=='PRI'){$type .= ' Primary Key';}
				elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
				if(strlen($field['default'])){$type .= ' Default '.$field['default'];}
				if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
				$type=xmlEncodeCDATA($type);
				$xmldata .= '	<field name="'.$field['field'].'" type="'.$type.'" />'."\r\n";
                }
            $xmldata .= '</xmlschema>'."\r\n";
            }
        if(isset($_REQUEST[$table.'_Meta']) && $_REQUEST[$table.'_Meta']==1){
			//export Meta data from _tabledata and _fielddata tables
			$mtables=array('_tabledata','_fielddata');
			foreach($mtables as $mtable){
				$recs=getDBRecords(array('-table'=>$mtable,'tablename'=>$table));
				if(is_array($recs)){
					$fields=getDBFields($mtable,1);
					foreach($recs as $rec){
						$xmldata .= '<xmlmeta name="'.$mtable.'">'."\r\n";
						foreach($fields as $field){
							if(!strlen($rec[$field])){continue;}
							if($isapp && stringBeginsWith($field,'_')){continue;}
							$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>\r\n";
	                        }
						$xmldata .= '</xmlmeta>'."\r\n";
	                    }
					}
				}
            }
        if(isset($_REQUEST[$table.'_Data']) && $_REQUEST[$table.'_Data']==1){
			//export table record
			$recs=getDBRecords(array('-table'=>$table,'-order'=>'_id'));
			if(is_array($recs)){
				$fields=getDBFields($table,1);
				foreach($recs as $rec){
					$xmldata .= '<xmldata name="'.$table.'">'."\r\n";
					foreach($fields as $field){
						if(!strlen($rec[$field])){continue;}
						if($isapp && stringBeginsWith($field,'_')){continue;}
						$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>\r\n";
		                }
					$xmldata .= '</xmldata>'."\r\n";
		            }
				}
	        }
        $xmldata .= "\r\n\r\n";
        }
    //specific records?
	$tables=array('_pages','_templates');
	foreach($tables as $table){
	    if(!isset($_REQUEST[$table.'_Data']) && is_array($_REQUEST[$table.'_recs'])){
			//export table record
			$ids=implode(',',$_REQUEST[$table.'_recs']);
			$recs=getDBRecords(array('-table'=>$table,'-where'=>"_id in ({$ids})",'-order'=>'_id'));
			if(is_array($recs)){
				$fields=getDBFields($table,1);
				foreach($recs as $rec){
					$xmldata .= '<xmldata name="'.$table.'">'."\r\n";
					foreach($fields as $field){
						if(!strlen($rec[$field])){continue;}
						if($isapp && stringBeginsWith($field,'_')){continue;}
						$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>\r\n";
		                }
					$xmldata .= '</xmldata>'."\r\n";
		            }
				}
	        }
	    }
    $xmldata.='</export>'."\r\n";
    pushData($xmldata,'xml');
    exit;
    }
//Create new table?
if(isset($_REQUEST['_menu']) && $_REQUEST['_menu']=='add' && isset($_REQUEST['_table_']) && isset($_REQUEST['_schema'])){
	$_SESSION['admin_errors']=array();
	$ok=adminCreateNewTable($_REQUEST['_table_'],$_REQUEST['_schema']);
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
		case 'synchronize':
			if(isAjax()){
				$db_stage=$SETTINGS['wasql_synchronize_master'];
	            $db_live=$SETTINGS['wasql_synchronize_slave'];
	            $refresh_changes=0;
	            if($_REQUEST['sync_action']=='sync' && isset($_REQUEST['sync_items']) && is_array($_REQUEST['sync_items']) && count($_REQUEST['sync_items']) && !isset($_REQUEST['sync_verify'])){
					//Verifiy Changes form and add a note - goes to the _synchronize table
					//list changes to be made
					echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/synchronize.png" border"0" width"16" height="16" class="w_middle"> Synchronize Verification</div>'."\n";
					echo '<div class="w_centerpop_content">'."\n";
					echo '	<div><img src="/wfiles/iconsets/16/user.png" border="0" class="w_middle" width="16" height="16" alt="Submitted by"> Submitted by: <b>'.$USER['username'].'</b></div>'."\n";
					echo '	<div>The following changes <u>will be pushed live</u>:</div>'."\n";
					echo '	<ol>'."\n";
					//remove duplicate sync_items
					$sync_items=array();
					foreach($_REQUEST['sync_items'] as $item){$sync_items[$item]+=1;}
					$_REQUEST['sync_items']=array_keys($sync_items);
					$codereview=0;
					foreach($_REQUEST['sync_items'] as $item){
                        list($id,$table)=preg_split('/\-\-/',$item,2);
                        if(isNum($id)){
							//check to see if live is newer
							$rec_stage=getDBRecord(array('-table'=>"{$db_stage}.{$table}",'-fields'=>'_cdate,_edate,_euser,_cuser','_id'=>$id));
							$rec_live=getDBRecord(array('-table'=>"{$db_live}.{$table}",'-fields'=>'_cdate,_edate,_euser,_cuser','_id'=>$id));
							$stage_time=setValue(array($rec_stage['_edate_utime'],$rec_stage['_cdate_utime'],0));
							$live_time=setValue(array($rec_live['_edate_utime'],$rec_live['_cdate_utime'],0));
							if($live_time > $stage_time){
                            	echo '		<li>Record '.$id.' in the \''.$table.'\' table. <b class="w_red"><img src="/wfiles/iconsets/16/warning.png" border="0" class="w_middle"> Live record is newer.</b></li>'."\n";
							}
							else{
                        		echo "		<li>Record {$id} in the '{$table}' table</li>\n";
							}
							//
							if($CONFIG['codereview']==1){
                            	if(isNum($rec_stage['_euser'])){
									if($rec_stage['_euser'] == $USER['_id']){$codereview++;}
								}
                            	elseif(!is_array($rec_live)){
                                	if($rec_stage['_cuser'] == $USER['_id']){$codereview++;}
								}
							}
						}
						else{
                        	echo "		<li>Database Schema for the '{$table}' table</li>\n";
						}
					}
					echo '	</ol>'."\n";
                	unset($_REQUEST['AjaxRequestUniqueId']);
                	unset($_REQUEST['PHPSESSID']);
                	unset($_REQUEST['GUID']);
                	$_REQUEST['-table']="_synchronize";
                	$_REQUEST['-formname']="sync_verify_form";
                	$_REQUEST['sync_verify']=1;
                	$_REQUEST['-action']='/php/admin.php';
                	$_REQUEST['-formfields']="note";
                	$_REQUEST['user_id']=$USER['_id'];
                	$_REQUEST['note_required']=1;
                	$_REQUEST['note_dname']="<b>Explain these changes.</b>";
                	$_REQUEST['note_inputtype']="textarea";
                	$_REQUEST['note_requiredmsg']="Explanation is required before pushing changes live";
                	$_REQUEST['note_width']=300;
                	$_REQUEST['note_height']=100;
                	$_REQUEST['-onsubmit']="ajaxSubmitForm(this,'centerpop');return false;";
                	$_REQUEST['-savebutton'] = '<input style="padding-left:23px;background:url(/wfiles/iconsets/16/synchronize_push.png);background-repeat:no-repeat;background-position: 5px;" type="submit" class="w_bold w_round" onclick="document.sync_verify_form._action.value=\'ADD\';" value="Push Changes Live">'."\n";
					//echo printValue($_REQUEST);
					if($CONFIG['codereview']==1){
						if($codereview > 0){
	                    	$_REQUEST['-formfields'].= "\n".'<div class="w_pad w_tip w_bold w_required"><img src="/wfiles/iconsets/16/users.png" border="" class="w_middle" width="16" height="16" alt="Code Review Required">Reviewed by (required):</div>'."\n";
							$_REQUEST['-formfields'].= "review_user:review_pass";
	                    	$_REQUEST['review_user_required']=1;
	                    	$_REQUEST['review_user_requiredmsg']="A Review is required before submitting changes.";
	                    	$_REQUEST['review_pass_required']=1;
	                    	$_REQUEST['review_pass_requiredmsg']="A Review is required before submitting changes.";
						}
						else{
							$_REQUEST['-formfields'].= "\n".'<div class="w_pad w_lblue"><img src="/wfiles/iconsets/16/checkmark.png" border="" class="w_middle" width="16" height="16" alt="I approve"> I have reviewed this code and approve.</div>'."\n";
							$_REQUEST['crskipcnt']=$codereview;
						}
					}
					echo addEditDBForm($_REQUEST);
					echo buildOnLoad("document.sync_verify_form.note.focus();");
					echo '</div>'."\n";
                	exit;
				}
	            switch(strtolower($_REQUEST['sync_action'])){
                	case 'diff_table':
                		//diff table record
                		$table=$_REQUEST['diff_table'];
                		$id=$_REQUEST['diff_id'];
						if(isNum($id)){
							$rec_stage=getDBRecord(array('-table'=>"{$db_stage}.{$table}",'_id'=>$id));
							$rec_live=getDBRecord(array('-table'=>"{$db_live}.{$table}",'_id'=>$id));
							$namefield='';
							$checkfields=array('name','title','description','fieldname','tablename','_id');
							foreach($checkfields as $checkfield){
								if(isset($rec_stage[$checkfield])){$namefield=$rec_stage[$checkfield];break;}
								elseif(isset($rec_live[$checkfield])){$namefield=$rec_live[$checkfield];break;}
							}
							$link =  '	<div style="float:right;">'."\n";
							$link .= '		<a class="w_link w_smaller w_red" href="#" onclick="return ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=synchronize&sync+action=sync&sync_items[]='.$id.'--'.$table.'\');"><img src="/wfiles/iconsets/16/synchronize_push.png" border="0" width="16" height="10"> Push Change Live</a>'."\n";
							$link .= '		<a class="w_link w_smaller w_red" href="#" onclick="if(!confirm(\'Cancel selected changes on stage and restore back to live?\')){return false;}return ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=synchronize&sync+action=cancel&sync_items[]='.$id.'--'.$table.'\');"><img src="/wfiles/iconsets/16/synchronize_pull.png" border="0" width="16" height="10"> Restore From Live</a>'."\n";

							$link .= '	</div>';
							echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/synchronize_diff.png" border="0" class="w_middle" width="16" height="16"> '.$table.' Record Diff</div>'."\n";
							echo '<div class="w_centerpop_content">'."\n";
							echo "<div class=\"w_tip w_big w_pad\">{$link}<img src=\"/wfiles/iconsets/16/synchronize_diff.png\" border=\"0\" width=\"16\" height=\"16\" class=\"w_middle\"> Diff for <b>{$table}</b> table <b>{$namefield}</b>  record <b>{$id}</b></div>\n";
							$finfo=getDBFieldInfo("{$db_stage}.{$table}",1);
							$diffs=array();
							foreach($rec_stage as $field=>$val){
								if(isWasqlField($field) || preg_match('/\_utime$/i',$field)){continue;}
								if(isset($finfo[$field]['synchronize']) && $finfo[$field]['synchronize']==0){continue;}
								$arr_stage=preg_split('/[\r\n]+/', trim($val));
								$arr_live=preg_split('/[\r\n]+/', trim($rec_live[$field]));
								$diff = diffText($arr_stage,$arr_live, $field,'',300);
								if(!strlen($diff)){continue;}
								if(preg_match('/No differences found/i',$diff)){continue;}
								$diffs[$field]=$diff;
							}
							if(count($diffs)){
								echo '<div id="tab_header">'."\n";
								echo '<ul class="tab">'."\n";
								$onload='';
								foreach($diffs as $field=>$diff){
									$tabid="tab_{$field}";
									$dataid="data_{$field}";
									$class="tab";
									if(!strlen($onload)){
										$onload="setText('tab_data',getText('{$dataid}'));";
										$class.=" current";
										}
									echo '	<li id="'.$tabid.'" class="'.$class.'"><a class="tab" href="#'.$field.'" onclick="setActiveTab(this);setText(\'tab_data\',getText(\''.$dataid.'\'));return false;">'.ucfirst($field).'</a></li>'."\n";
								}
								echo '</ul>'."\n";
								echo '</div><br clear="both" /><div id="tab_data" style="border:1px solid #000;border-top:0px;" align="left"></div>'."\n";
								echo '<div style="display:none">'."\n";
								foreach($diffs as $field=>$diff){
									$dataid="data_{$field}";
									echo '<textarea id="'.$dataid.'">'.encodeHtml($diff).'</textarea>'."\n";
								}
								echo '</div>'."\n";
								echo buildOnLoad($onload);
							}
							echo '</div>'."\n";
						}
                		break;
                	case 'diff_schema':
						//diff table schema
						$table=$_REQUEST['diff_table'];
						$txt_stage=trim(getDBSchemaText("{$db_stage}.{$table}"));
                        $txt_live=trim(getDBSchemaText("{$db_live}.{$table}"));
						$arr_stage=preg_split('/[\r\n]+/', $txt_stage);
						$arr_live=preg_split('/[\r\n]+/', $txt_live);
						$link=' <a class="w_link w_smaller w_red" href="#" onclick="return ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=synchronize&sync+action=sync&sync_items[]=schema--'.$table.'\');"><img src="/wfiles/iconsets/16/synchronize.png" border="0" width="16" height="16"> sync now</a>';
						echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/synchronize_diff.png" border="0" class="w_middle" width="16" height="16"> '.$table.' Schema Diff</div>'."\n";
						echo '<div class="w_centerpop_content">'."\n";
						echo diffText($arr_stage,$arr_live, "<img src=\"/wfiles/iconsets/16/synchronize_diff.png\" border=\"0\" width=\"16\" height=\"16\" class=\"w_middle\"> Schema Diff for {$table} table",$link);
                		echo '</div>'."\n";
						break;
                	case 'sync':
                		//echo printValue($_REQUEST);
                		if(!is_array($_REQUEST['sync_items'])){
							echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        	echo '<div class="w_centerpop_content">'."\n";
							echo "No records selected to sync";
							echo '</div>'."\n";
                        	break;
						}
						//check for codereview setting in CONFIG
						if($CONFIG['codereview']==1 && !isset($_REQUEST['crskipcnt'])){
							if(!strlen(trim($_REQUEST['review_user']))){
								echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        		echo '<div class="w_centerpop_content">'."\n";
                            	echo "A Review is required before submitting changes.";
                            	echo "<br />Missing Code Review username.";
                            	echo '</div>'."\n";
                            	break;
							}
							if(!strlen(trim($_REQUEST['review_pass']))){
								echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        		echo '<div class="w_centerpop_content">'."\n";
                            	echo "A Review is required before submitting changes.";
                            	echo "<br />Missing Code Review password.";
                            	echo '</div>'."\n";
                            	break;
							}
							//is the code review user and pass in the database as an admin
							$review_pass=userEncryptPW($_REQUEST['review_pass']);
							$review_rec=getDBRecord(array('-table'=>'_users','-fields'=>'_id,username,password','utype'=>0,'username'=>$_REQUEST['review_user']));
							if(!is_array($review_rec) || sha1($review_pass) != sha1($review_rec['password'])){
								echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        		echo '<div class="w_centerpop_content">'."\n";
								echo "A Review by another admin is required before submitting changes.";
                            	echo "<br />Invalid Code Reviewer authentication.";
                            	echo '</div>'."\n";
                            	break;
							}
							//check to make sure they are not reviewing their own code
							if(is_array($review_rec) && sha1($review_rec['username']) == sha1($USER['username'])){
								echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        		echo '<div class="w_centerpop_content">'."\n";
                            	echo "A Review by another admin is required before submitting changes.";
                            	echo "<br />You cannot review your own code. [{$_REQUEST['review_user']}]";
                            	echo '</div>'."\n";
                            	break;
							}
							//remove the password from the _synchronize table and add the review_id
							if(isNum($_REQUEST['add_id']) && $_REQUEST['add_table']="_synchronize"){
                            	$ok=editDBRecord(array(
									'-table'=>'_synchronize',
									'-where'=>"_id={$_REQUEST['add_id']}",
									'review_pass'=>'NULL',
									'review_user_id'=>$review_rec['_id']
								));
							}
						}
						echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/synchronize.png" border="0" class="w_middle"> Synchronize</div>'."\n";
                        echo '<div class="w_centerpop_content">'."\n";
                		foreach($_REQUEST['sync_items'] as $item){
                        	list($id,$table)=preg_split('/\-\-/',$item,2);
                        	if(isNum($id)){
								$ok=adminSynchronizeRecord($table,$id,isDBStage());
								if(isNum($ok) || $ok==''){
									echo buildImage('checkmark')." Record {$id} in {$table} table has been synchronized.<br />";
								}
								else{
									echo $ok;
								}
								$refresh_changes=1;
							}
							elseif($id=='schema'){
                            	//sync table schema
                            	$txt_stage=trim(getDBSchemaText("{$db_stage}.{$table}"));
                            	$tables_live=getDBTables($db_live,1);
                            	if(in_array($table,$tables_live)){
									//update table schema
                            		$ok=updateDBSchema("{$db_live}.{$table}",$txt_stage);
									echo buildImage('checkmark')." Schema for {$table} has been modified on live server. <br />";
									$refresh_changes=1;
								}
								else{
									//new table schema
                            		$ok=updateDBSchema("{$db_live}.{$table}",$txt_stage,1);
									echo buildImage('checkmark')." Schema for {$table} has been added on live server. <br />" ;
									$refresh_changes=1;
								}
							}
						}
						echo '</div>'."\n";
                		break;
                	case 'cancel':
                		//echo printValue($_REQUEST);
                		if(!is_array($_REQUEST['sync_items'])){
							echo '<div class="w_centerpop_title"><img src="/wfiles/iconsets/16/alert.png" border="0" alt="errors" class="w_middle"> Error Processing Request:</div>'."\n";
                        	echo '<div class="w_centerpop_content">'."\n";
                        	echo "No records selected to cancel";
                        	echo '</div>'."\n";
                        	break;
						}
                		foreach($_REQUEST['sync_items'] as $item){
                        	list($id,$table)=preg_split('/\-\-/',$item,2);
                        	if(isNum($id)){
                            	//cancel table record
                            	$rec_stage=getDBRecord(array('-table'=>"{$db_stage}.{$table}",'_id'=>$id));
                            	$rec_live=getDBRecord(array('-table'=>"{$db_live}.{$table}",'_id'=>$id));
								if(is_array($rec_live)){
                                	//revert to live
                                	//edit existing record on live
									$editopts=array();
									$finfo=getDBFieldInfo("{$db_live}.{$table}",1);
									foreach($rec_live as $field=>$val){
										if(isWasqlField($field) || preg_match('/\_utime$/i',$field)){continue;}
										if(isset($finfo[$field]['synchronize']) && $finfo[$field]['synchronize']==0){continue;}
										$val_stage=$rec_stage[$field];
										if(sha1($val) != sha1($val_stage)){$editopts[$field]=$val;}
									}
									if(count($editopts)){
	                                	$editopts['-table']="{$db_stage}.{$table}";
										$editopts['-where']="_id={$id}";
										$ok=editDBRecord($editopts);
										echo buildImage('checkmark')." Changes in Record {$id} in {$table} table have been cancelled<br />";
										$refresh_changes=1;
									}
								}
								else{
                                	//delete this record on stage
                                	$ok=delDBRecord(array('-table'=>"{$db_stage}.{$table}",'-where'=>"_id={$id}"));
                                	echo buildImage('checkmark')." Record {$id} in {$table} table on stage has been removed<br />";
									$refresh_changes=1;
								}
							}
							elseif($id=='schema'){
                            	//cancel table schema
                            	$tables_live=getDBTables($db_live,1);
                            	if(in_array($table,$tables_live)){
									//revert table schema back to live
									$txt_live=trim(getDBSchemaText("{$db_live}.{$table}"));
                            		$ok=updateDBSchema("{$db_stage}.{$table}",$txt_live);
									echo buildImage('checkmark')." Schema changes for {$table} has been cancelled. <br />";
									$refresh_changes=1;
								}
								elseif(isWasqlTable($table)){
									echo buildImage('alert')."{$table} table on stage is an internal WaSQL table and cannot be removed. <br />";
								}
								else{
									//drop table schema
									$ok=dropDBTable("{$db_stage}.{$table}");
									echo buildImage('checkmark')." {$table} on stage has been removed. <br />";
									$refresh_changes=1;
								}
							}
						}
                		break;
					}
				//refresh the main changes div if any changes have been made
				if($refresh_changes==1){
                	echo '<div id="synchronize_changes_update" style="display:none">'."\n";
                	$stables=adminGetSynchronizeTables();
                	echo adminShowSyncChanges($stables);
                	echo '</div>'."\n";
                	echo buildOnLoad("setText('synchronize_changes',getText('synchronize_changes_update'));setText('synchronize_changes_update','');");
				}
				//exit since this is an Ajax call
				//echo printValue($_REQUEST);
				exit;
				}
			break;
		case 'drop':
			if(isset($_REQUEST['_table_'])){
			 	$dropResult = dropDBTable($_REQUEST['_table_'],1);
				}
			break;
		case 'postedit_exe':
			pushfile("$progpath/../postedit/postedit.exe");
			break;
		case 'postedit_zip':
			pushfile("$progpath/../postedit/postedit.zip");
			break;
		case 'postedit_xml':
			$xml='';
			$xml .= xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
			$xml .= '<hosts>'."\r\n";
			$xml .= '	<host'."\r\n";
			$xml .= '		name="'.$_SERVER['HTTP_HOST'].'"'."\r\n";
			$xml .= '		group="'.$_SERVER['UNIQUE_HOST'].'"'."\r\n";
			$xml .= '		apikey="'.$USER['apikey'].'"'."\r\n";
			$xml .= '		username="'.$USER['username'].'"'."\r\n";
			$xml .= '	/>'."\r\n";
			$xml .= '</hosts>'."\r\n";
			pushData($xml,"xml","postedit.xml");
			break;
		}
	}
//cache the page for 5 seconds
$expire=gmdate('D, d M Y H:i:s', time()+10);
@header("Pragma: public");
@header("Cache-Control: maxage={$expire}");
@header("Expires: {$expire} GMT");
@header('X-Platform: WaSQL');
$js=<<<ENDOFJSSCRIPT
<script type="text/javascript">
	//---------- begin function syncTableClick ----
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	function syncTableClick(c){
		var c=getObject(c);
		var cname=c.getAttribute('href');
		var ctab='sync'+cname+'tab';
		var sTable=getObject('syncTableMain');
		for (var i = 0, row; row = sTable.rows[i]; i ++) {
			for (var j = 0, col; col = row.cells[j]; j ++) {
				var sname=col.id;
				var stab='sync'+sname+'tab';
				var shref='sync'+sname+'href';
				var sdiv='sync'+sname+'div';
				if(stab==ctab){
					setText('syncDiv',getText(sdiv));
					setClassName(stab,'table_header_reverseback w_roundsmall_topleft w_roundsmall_topright w_arial w_small w_border');
					setClassName(shref,'table_header_reversetext w_link w_bold');
					var sobj=getObject('synctable'+sname);
					sorttable.makeSortable(sobj);
				}
				else{
					setClassName(stab,'table_header_background w_roundsmall_topleft w_roundsmall_topright w_arial w_small w_border');
					setClassName(shref,'table_header_text  w_link w_bold');
				}
			}
		}
		sorttable.init();
		return false;
	}
</script>
ENDOFJSSCRIPT;
$params=array(
	'js'=>$js,
	'title'=>$_SERVER['HTTP_HOST'].' Admin'
	);
echo buildHtmlBegin($params);
echo '<div id="admin_menu">'."\n";
echo adminMenu();
echo '</div>'."\n";
echo '<div style="float:right;font-size:10pt;color:#C0C0C0;" align="right">'."\n";
//if user has switched databases from original - show switch back link
if(isset($_SESSION['dbhost_original'])){
	echo '	<div class="w_pad w_margin w_dblue "><table border="0"><tr align="center"><td rowspan="2"><img src="/wfiles/iconsets/32/database_switch.png" border="0" class="w_middle"></td><td><div class="w_bold w_required w_big">Viewing '.$_SESSION['dbhost'].'</div></td></tr><tr align="center"><td><a class="w_link w_dblue w_block w_big" href="?dbhost=-1&dbauth=-1">Switch Back</a></td></tr></table></div>'."\n";
}
echo '	<div id="updatecheck" class="w_big w_padright w_dblue"><img src="/wfiles/iconsets/16/info.png" border="0" class="w_middle" /> '.$CONFIG['name'].' - <img src="/wfiles/iconsets/16/database.png" border="0" class="w_middle" /> <b class="w_red">'.$CONFIG['dbname'].'</b></div>'."\n";
echo '	<div id="facebook_status" class="w_big w_pad"></div>'."\n";
echo '</div>'."\n";
echo '<br clear="both" />'."\n";
echo '<div style="clear:both;float:left;width:100%;"></div>'."\n";
echo '<div id="admin_body" class="w_pad" style="position:relative;">'."\n";
if(1==2 && !isset($_SESSION['WASQLUpdateCheck']) || $_REQUEST['_menu']=='about'){
	echo buildOnload("setProcessing('updatecheck','Checking for updates...');ajaxGet('{$_SERVER['PHP_SELF']}','updatecheck','_menu=updatecheck');");
}
//process _menu request
if(isset($_REQUEST['_menu'])){
	switch(strtolower($_REQUEST['_menu'])){
		case 'charset':
			global $CONFIG;
			$cmessage='';
			if(isset($_REQUEST['_charset']) && strlen($_REQUEST['_charset'])){
				$cmessage .= '<h3>'.$_REQUEST['_charset'].' conversion results:</h3><hr>'."\n";
				$tables=getDBTables();
				foreach($tables as $table){
					$runsql='ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.$_REQUEST['_charset'];
					$cmessage .= 'Converting Table '.$table.'...'."\n";
					$ck=executeSQL($runsql);
					if(isset($ck['result'])){
						if($ck['result'] != 1){$cmessage .= "FAILED: {$ck['query']}<br>\n";}
						else{$cmessage .= 'SUCCESS<br>'."\n";}
						}
					elseif($ck !=1){$cmessage .= "FAILED: {$ck}<br>\n";}
					else{$cmessage .= 'SUCCESS<br>'."\n";}
	            	}
	            $runsql='ALTER DATABASE '.$CONFIG['dbname'].' CHARACTER SET '.$_REQUEST['_charset'];
				$cmessage .= 'Converting Database '.$CONFIG['dbname'].'...'."\n";
				$ck=executeSQL($runsql);
				if(isset($ck['result'])){
					if($ck['result'] != 1){$cmessage .= "FAILED: {$ck['query']}<br>\n";}
					else{$cmessage .= 'SUCCESS<br>'."\n";}
					}
				elseif($ck !=1){$cmessage .= "FAILED: {$ck}<br>\n";}
				else{$cmessage .= 'SUCCESS<br>'."\n";}
		        }
			$charsets=getDBCharsets();
			$current_charset=getDBCharset();
			//echo '<div class="w_lblue w_bold">Current Character Set: '.$current_charset.'</div>'."\n";
			echo '<div class="w_lblue w_bold"><img src="/wfiles/charset.gif" border="0"> Available Character Sets that your current Database supports</div>'."\n";
			echo '		<form method="POST" name="charset_form" action="/'.$PAGE['name'].'" class="w_form">'."\n";
			echo '			<input type="hidden" name="_menu" value="charset">'."\n";
			echo '			<select name="_charset">'."\n";
			foreach($charsets as $charset=>$desc){
				echo '				<option value="'.$charset.'"';
				if($charset == $current_charset){echo ' selected';}
				echo '>'.$desc.'</option>'."\n";
            	}
			echo '			</select>'."\n";
			echo buildFormSubmit('Convert');
			echo '		</form>'."\n";
			echo $cmessage;
			break;
		case 'editor':
			echo '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="top">'."\n";
			echo '	<td nowrap>'."\n";
			echo '	<div class="w_bold" style="padding-bottom:8px;border-bottom:1px solid #000;"><img src="/wfiles/wasql_admin.png" border="0" class="w_middle"> Inline Editor Menu</div>'."\n";
			echo '	<div id="w_editor_nav">'."\n";
			echo editorNavigation();
			echo '	</div></td>'."\n";
			echo '	<td width="100%"><div id="w_editor_main">'."\n";
			echo '	</div></td>'."\n";
			echo '</tr></table>'."\n";
			break;
		case 'contentmanager':
			echo '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="top">'."\n";
			echo '	<td nowrap>'."\n";
			echo '	<div class="w_bold" style="padding-bottom:8px;border-bottom:1px solid #000;"><img src="/wfiles/iconsets/32/contentmanager.png" border="0" class="w_middle"> Content Manager</div>'."\n";
			echo '	<div id="w_editor_nav">'."\n";
			echo contentManager();
			echo '	</div></td>'."\n";
			echo '	<td width="100%"><div id="w_editor_main">'."\n";
			echo '	</div></td>'."\n";
			echo '</tr></table>'."\n";
			break;
		case 'env':
			//Server Variables
			echo '<div class="w_lblue w_bold"><img src="/wfiles/server.png" border="0"> Server Variables</div>'."\n";
			echo buildTableBegin(2,1);
			echo buildTableTH(array('Variable','Value'));
			foreach($_SERVER as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
            	}
            echo buildTableEnd();
			break;
		case 'iconsets':
			//Server Variables
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/icon.png" border="0"> List Iconsets</div>'."\n";
			echo '<hr size="1">'."\n";
			$iconsets=listIconsets();
			echo buildTableBegin(2,0);
			echo '<tr>';
			$cnt=0;
			foreach($iconsets as $name){
				if(preg_match('/^thumbs$/i',$name)){continue;}
            	echo '<td class="w_pad w_smallest w_lblue" align="center">'."\n";
            	echo '	<div><img src="/wfiles/iconsets/64/'.$name.'.png" width="64" height="64" border="0" class="w_middle" /></div>'."\n";
            	echo '	<div class="w_bold w_dblue w_bigger">'.$name.'</div>'."\n";
				echo '	<div><b>16:</b> /wfiles/iconsets/16/'.$name.'.png</div>'."\n";
            	echo '	<div><b>32:</b> /wfiles/iconsets/32/'.$name.'.png</div>'."\n";
            	echo '	<div><b>64:</b> /wfiles/iconsets/64/'.$name.'.png</div>'."\n";
				echo '</td>'."\n";
				$cnt++;
				if($cnt==4){
                	echo '</tr><tr>'."\n";
                	$cnt=0;
				}
			}
			echo '</tr></table>'."\n";
			//echo printValue($iconsets);
			break;
			echo buildTableBegin(2,1);
			echo buildTableTH(array('Variable','Value'));
			foreach($_SERVER as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				echo buildTableTD(array($key,printValue($val)),array('valign'=>'top'));
            	}
            echo buildTableEnd();
			break;
		case 'system':
			//Server Variables
			echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/32/server.png" border="0"> System Info</div>'."\n";
			$info=getServerInfo();
			//first show all the items that are not arrays
			echo buildTableBegin(2,1);
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
				echo '	<tr><td>'.$name.'</td><td nowrap>'."\n";
				//if(count($val) > 5 && is_array($val[0])){echo '		<div style="height:150px;overflow:auto;padding-right:20px;">'."\n";}
				$fields=array();
				foreach($val as $x=>$subval){
					if(is_array($subval)){
						foreach($subval as $key2=>$val2){$fields[$key2]=1;}
						}
					else{$fields[$x]=1;}
					}
				echo buildTableBegin(2,1);
				echo buildTableTH(array_keys($fields));
				foreach($val as $x=>$subval){
					$svals=array();
					if(is_array($subval)){
						foreach($fields as $field=>$cnt){$svals[]=$subval[$field];}
						echo buildTableTD($svals);
						}
					else{
						foreach($fields as $field=>$cnt){$svals[]=$val[$field];}
						echo buildTableTD($svals);
						break;
                    	}
                	}
				echo buildTableEnd();
				//if(count($val) > 5){echo '		</div>'."\n";}
				echo '	</td></tr>'."\n";
            	}
            echo buildTableEnd();
            //echo printValue($info);
			break;
			//$info=getServerInfo();
			echo printValue($info);
			echo buildTableBegin(2,1);
			//echo buildTableTH(array('Variable','Value'));

            echo buildTableEnd();
			break;
		case 'entities':
			//Server Variables
			echo '<div class="w_lblue w_bold"><img src="/wfiles/entity.gif" border="0"> HTML Entities</div>'."\n";
			echo buildTableBegin(4,0);
			echo '<tr valign="top"><td>'."\n";
			echo buildTableBegin(2,1);

			//entities
			echo '<tr><th colspan="2">HTML Entities</th></tr>'."\n";
			echo buildTableTH(array('Entity','Result'));
			$table = get_html_translation_table(HTML_ENTITIES);
			foreach($table as $x=>$val){
				$code=preg_replace('/^\&/','&amp;',$val);
				echo buildTableTD(array($code,$val));
				}
			echo buildTableEnd();
			echo '</td><td>'."\n";
			echo '	' . buildTableBegin(2,1);
			echo '<tr><th colspan="20">Other ASCII Character Sets  (&amp;#CODE;)</th></tr>'."\n";
			$th=array();
			for($x=0;$x<10;$x++){array_push($th,"Code","Val");}
			echo buildTableTH($th);
			$special_nums = array_merge(range(32,383),array(399,402,416,417,431,432),range(461,476),range(506,511),array(601),range(900,974),range(1025,1116),array(1118,1119),range(1168,1182),range(1197,1211),array(1240,1241,1256,1257),range(1329,1475),array(1757,1758,1769,4342),range(7840,7929),range(8211,8226),array(8362,8482,8531,8532),range(8539,8542),range(8592,8597),array(8706,8710,8719,8721,8730,8734,8745,8776,8800,8801,8804,8805,8962,9650,9658,9660,9668,9674,9675,9679,9688,9689,9786,9787,9788,9792,9794,9824,9827,9829,9830,9834,9835));
			$count=0;
            foreach($special_nums as $x){
				if(in_array($x,array(129,141,143,144,157,907,909,930,1367,1368,1376,1416))){continue;}
				if(in_array($x,range(1418,1455))){continue;}
				//if(($x>745 && $x<900) || ($x>1415 && $x<1475) || ($x>1836 && $x<1920) || ($x>1957 && $x<2309) || ($x>2799 && $x<2949) || ($x>3439 && $x<3585) || ($x>3675 && $x<4304) || ($x>4342 && $x<4978) || ($x>4987 && $x<5870) || ($x>5872 && $x<7680) || ($x>8260 && $x<8352) || ($x>8367 && $x<8448) || ($x>8995 && $x<9216) || ($x>9252 && $x<9312) || ($x>9710 && $x<9786) || ($x>9794 && $x<9824) || ($x>9841 && $x<10001)){continue;}
				$count++;
				if(isFactor($count,2)){echo "<td class=\"w_lgrayback\">{$x}</td><td class=\"w_lgrayback\">&#{$x};</td>";}
				else{echo "<td>{$x}</td><td>&#{$x};</td>";}

				if($count==10){
					$count=0;
					echo "</tr>\n		<tr>\n";
                	}
            	}
            echo buildTableEnd();
            echo '</td></tr>'."\n";
            echo buildTableEnd();
			break;
		case 'rebuild':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/rebuild.png" border="0" class="w_files"> Rebuild waSQL Tables</div>'."\n";
			if(isset($_REQUEST['_table_'])){
            	if(dropDBTable($_REQUEST['_table_'],1)){
					$ok=createWasqlTables($_REQUEST['_table_']);
					echo printValue($ok);
				}
			}
			else{
	    		clearDBCache(array('databaseTables','databaseTables','isDBTable'));
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
			clearDBCache(array('databaseTables','databaseTables','isDBTable'));
			echo '<div>Complete</div>'."\n";
    		break;
		case 'manual':
			global $Manual;
			if(isset($_REQUEST['rebuild']) || !is_file("{$progpath}/temp/manual.json")){
				wasqlRebuildManual();
			}
			else{
            	$Manual=json_decode(getFileContents("{$progpath}/temp/manual.json"),true);
			}
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/help.png" border="0"> WaSQL Documentation</div>'."\n";
			echo '<div class="w_lblue w_small" style="margin-left:50px;"> as of '.date('F j, Y, g:i a',$Manual['timestamp']).' <a href="?_menu=manual&rebuild=1" class="w_link w_lblue w_smallest"><img src="/wfiles/iconsets/16/refresh.png" border="0" width="12" height="12" class="w_middle"> Rebuild</a></div>'."\n";
			echo '		<form method="POST" name="documentation_searchform" action="/'.$PAGE['name'].'" class="w_form" onsubmit="ajaxSubmitForm(this,\'manual_content\');return false;">'."\n";
			echo '			<input type="hidden" name="_menu" value="manual">'."\n";
			echo '			<input type="hidden" name="_type" value="user">'."\n";
			echo '			<input type="text" name="_search" value="'.$_REQUEST['_search'].'" onFocus="this.select();">'."\n";
			echo '			<input type="submit" value="Search">'."\n";
			echo '		</form>'."\n";
			echo buildOnLoad("document.documentation_searchform._search.focus();");
			echo wasqlBuildManualTree();
			break;
		case 'profile':
			//My Profile
			$img=$USER['utype']==0?$rtn .= 'admin.gif':'user.gif';
			echo '<div class="w_lblue w_bold"><img src="/wfiles/icons/users/'.$img.'" border="0"> My Profile <a href="#" onclick="return ajaxAddEditForm(\'_users\','.$USER['_id'].');" class="w_link w_lblue w_bold"><img src="/wfiles/edit.png" border="0"> edit</a></div>'."\n";
			echo buildTableBegin(2,1);
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
            echo buildTableTD(array("<b>SessionID</b>",session_id()),array('valign'=>'top'));
            echo buildTableEnd();
			break;
		case 'settings':
			global $SETTINGS;
			echo '<div style="width:800px;padding:10px;">'."\n";
			echo '<div class="w_lblue w_bold w_biggest"><img src="/wfiles/iconsets/32/settings.png" border="0" style="vertical-align:middle;"> Settings - '.$CONFIG['dbname'].'</a></div>'."\n";
			//update
			echo buildFormBegin('',array('_menu'=>'settings','-name'=>'settingsform','_action'=>'settings'));
			echo buildTableBegin(2,1);
			echo '	<tr><th colspan="3" align="left" class="w_big"><img src="/wfiles/iconsets/16/users.png" border="0" style="vertical-align:middle;"> Global Settings</th></tr>'."\n";
			//Wasql Crons
			$key='wasql_crons';
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>"0\r\n1",'dvals'=>"OFF\r\nON"));
			$help='Turn ON to use WaSQL crons.  WaSQL crons allow you to schedule and manage externals processes - when and how often they run. It also records the result of such processes.';
			echo '	<tr valign="top">'."\n";
			echo '		<td nowrap>'."\n";
			echo '			<img src="/wfiles/iconsets/16/datetime.png" border="0" style="vertical-align:middle;">'."\n";
			echo '			<b class="w_big w_dblue">'.friendlyName($key)."</b>\n";
			if($SETTINGS[$key]==1){
				echo '			<div align="center"><img src="/wfiles/iconsets/32/poweron.png" border="0" alt="On" onclick="document.settingsform.set_global_'.$key.'.value=0;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";
				}
			else{echo '			<div align="center"><img src="/wfiles/iconsets/32/poweroff.png" border="0" alt="Off" onclick="document.settingsform.set_global_'.$key.'.value=1;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";}
			echo '		</td>'."\n";
			echo '		<td>'.$formfield.'</td>'."\n";
			echo '		<td><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> '.$help.'</td>'."\n";
			echo '	</tr>'."\n";
			//Wasql Queries
			$key='wasql_queries';
			$help='Turn Status ON to log all database queries into the _queries table.  This is normally turned OFF.  Turning it on should help you optimize queries and determine if you need change queries, add indexes to tables, or make other adjustments to increase page load speed.';
			$help .= '<br><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> Set Days to the number of days to record.';
			$help .= '<br><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> Set Time to the minimum number of seconds before recording - 0 logs all.';
			$help .= '<br><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> Setting User will limit the queries to only run when that user is logged in.';
			echo '	<tr valign="top">'."\n";
			echo '		<td nowrap>'."\n";
			echo '			<img src="/wfiles/iconsets/16/queries.png" border="0" style="vertical-align:middle;">'."\n";
			echo '			<b class="w_big w_dblue">'.friendlyName($key)."</b>\n";
			if($SETTINGS[$key]==1){echo '			<div align="center"><img src="/wfiles/iconsets/32/poweron.png" border="0" alt="On" onclick="document.settingsform.set_global_'.$key.'.value=0;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";}
			else{echo '			<div align="center"><img src="/wfiles/iconsets/32/poweroff.png" border="0" alt="Off" onclick="document.settingsform.set_global_'.$key.'.value=1;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";}
			echo '		</td>'."\n";			
			echo '		<td>'."\n";
			echo buildTableBegin(2,0);
			//status,days,time,user
			echo '			<tr>'."\n";
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>"0\r\n1",'dvals'=>"OFF\r\nON"));
			echo '				<td>Status<br>'.$formfield.'</td>'."\n";
			$key='wasql_queries_days';
			$SETTINGS[$key]=setValue(array($SETTINGS[$key],10));
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'text','mask'=>'integer','requiredmsg'=>'Enter the number of days to keep the query log','maskmsg'=>'Must be a valid integer','width'=>40));
			echo '				<td>Days<br>'.$formfield.'</td>'."\n";
			$key='wasql_queries_time';
			$SETTINGS[$key]=setValue(array($SETTINGS[$key],.25));
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'text','mask'=>'number','requiredmsg'=>'Enter the minimum run length in seconds to record to the query log','maskmsg'=>'Must be a valid integer','width'=>40));
			echo '				<td>Time<br>'.$formfield.'</td>'."\n";

			echo '			</tr><tr>'."\n";
			$key='wasql_queries_user';
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>'select _id from _users order by firstname,lastname,_id','dvals'=>'select firstname,lastname from _users order by firstname,lastname,_id'));
			echo '				<td colspan="2">Limit to Specific User<br>'.$formfield.'</td>'."\n";
			echo '			</tr>'."\n";
			echo buildTableEnd();
			echo '		</td><td><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> '.$help.'</td></tr>'."\n";
			//Wasql Stats Logs
			$help='Turn Status ON to log all page views into the _access table.  This is normally turned OFF.  Turning it on also populate the _access_summary table.';
			$help .= '<br><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> Turn Search Bots on in log search bot requests. Otherwise, hits from search bots will be ignored.';
			$help .= '<br><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> Set dbname if you are want to write the access logs to a different database.';
			$help .= '<div class="w_bold w_red w_big"><img src="/wfiles/iconsets/16/warning.png" border="0" style="vertical-align:bottom;"> Warning: Turning this feature on may slow down high traffic sites.</div>';
			$key='wasql_access';
			echo '	<tr valign="top">'."\n";
			echo '		<td nowrap>'."\n";
			echo '			<img src="/wfiles/_access.gif" border="0" style="vertical-align:middle;">'."\n";
			echo '			<b class="w_big w_dblue">'.friendlyName($key)."</b>\n";
			if($SETTINGS[$key]==1){echo '			<div align="center"><img src="/wfiles/iconsets/32/poweron.png" border="0" alt="On" onclick="document.settingsform.set_global_'.$key.'.value=0;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";}
			else{echo '			<div align="center"><img src="/wfiles/iconsets/32/poweroff.png" border="0" alt="Off" onclick="document.settingsform.set_global_'.$key.'.value=1;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";}
			echo '		</td>'."\n";			
			echo '		<td>'."\n";
			echo buildTableBegin(2,0);
			//status
			echo '				<tr>'."\n";
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>"0\r\n1",'dvals'=>"OFF\r\nON"));
			echo '					<td>Status<br>'.$formfield.'</td>'."\n";
			$key='wasql_access_bot';
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>"0\r\n1",'dvals'=>"OFF\r\nON"));
			echo '					<td>Search Bots<br>'.$formfield.'</td>'."\n";
			echo '				</tr>'."\n";
			$dbs=getDBRecords(array('-query'=>"show databases"));
			$tvals=array();
			foreach($dbs as $db){
				if(strtolower($CONFIG['dbname']) != strtolower($db['database'])){$tvals[]=$db['database'];}
				}
			sort($tvals);
			$tvalstr=implode("\r\n",$tvals);
			$key='wasql_access_dbname';
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>$tvalstr,'dvals'=>$tvalstr));
			echo '				<tr><td colspan="2">Alt DBName<br>'.$formfield.'</td></tr>'."\n";
			echo '			</tr>'."\n";
			echo buildTableEnd();
			echo '		</td><td><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> '.$help.'</td></tr>'."\n";
			//Wasql synchronize
			$key='wasql_synchronize';
			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'select','width'=>'','tvals'=>"0\r\n1",'dvals'=>"OFF\r\nON",'onchange'=>"if(this.value==1){document.settingsform.set_global_{$key}_master.disabled=0;document.settingsform.set_global_{$key}_slave.disabled=0;document.settingsform.set_global_{$key}_tables.disabled=0;document.settingsform.set_global_{$key}_master.setAttribute('required',1);document.settingsform.set_global_{$key}_slave.setAttribute('required',1);document.settingsform.set_global_{$key}_tables.setAttribute('required',1);}else{document.settingsform.set_global_{$key}_master.disabled=1;document.settingsform.set_global_{$key}_slave.disabled=1;document.settingsform.set_global_{$key}_tables.disabled=1;document.settingsform.set_global_{$key}_master.setAttribute('required',0);document.settingsform.set_global_{$key}_slave.setAttribute('required',0);document.settingsform.set_global_{$key}_tables.setAttribute('required',0);}"));
			$help='Turn ON to enable synchronize manager between two databases.'."\n";
			$help.='Tables that have synchronize selected by default are _pages,_templates,_fielddata,_tabledata,_cron, and _reports.'."\n";
			$help.='The synchronize checkbox is found in the properties window of each table.';
			echo '	<tr valign="top">'."\n";
			echo '		<td nowrap>'."\n";
			echo '			<img src="/wfiles/iconsets/16/synchronize.png" border="0" style="vertical-align:middle;">'."\n";
			echo '			<b class="w_big w_dblue">'.friendlyName($key)."</b>\n";
			if($SETTINGS[$key]==1){
				echo '			<div align="center"><img src="/wfiles/iconsets/32/poweron.png" border="0" alt="On" onclick="document.settingsform.set_global_'.$key.'.value=0;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";
				}
			else{
				echo '			<div align="center"><img src="/wfiles/iconsets/32/poweroff.png" border="0" alt="Off" onclick="document.settingsform.set_global_'.$key.'.value=1;document.settingsform.submit();" style="cursor:pointer;"></div>'."\n";
				}
			echo '		</td>'."\n";
			echo '		<td nowrap>'."\n";
			echo "<div>{$formfield}</div>\n";
			$tvals=array();
			foreach($dbs as $db){
				$tvals[]=$db['database'];
				}
			sort($tvals);
			$tvalstr=implode("\r\n",$tvals);
			$disabled=$SETTINGS[$key]==1?0:1;
			$required=$SETTINGS[$key]==1?1:0;
			//Master
			$subkey=$key.'_master';
			if(!strlen($SETTINGS[$subkey])){$SETTINGS[$subkey]=$CONFIG['dbname'];}
			echo "<div>Staging/Live {$disabled}</div>\n";
			$formfield=getDBFieldTag(array('requiredmsg'=>'Select Staging Database','message'=>'-- Staging DB --','required'=>$required,'disabled'=>$disabled,'-table'=>'_settings','-field'=>'key_value','width'=>'','name'=>"set_global_{$subkey}",'value'=>$SETTINGS[$subkey],'inputtype'=>'select','tvals'=>$tvalstr,'dvals'=>$tvalstr));
			echo '<div style="font-weight:bold;color:#CCC;font-size:14px;"><img src="/wfiles/iconsets/16/database.png" border="0" class="w_middle">S '.$formfield.'</div>'."\n";
			//slave
			$subkey=$key.'_slave';
			$formfield=getDBFieldTag(array('requiredmsg'=>'Select Live Database','message'=>'-- Live DB --','required'=>$required,'disabled'=>$disabled,'-table'=>'_settings','-field'=>'key_value','width'=>'','name'=>"set_global_{$subkey}",'value'=>$SETTINGS[$subkey],'inputtype'=>'select','tvals'=>$tvalstr,'dvals'=>$tvalstr));
			echo '<div style="font-weight:bold;color:#CCC;font-size:14px;"><img src="/wfiles/iconsets/16/database.png" border="0" class="w_middle">L&nbsp;  '.$formfield.'</div>'."\n";
			echo '		</td>'."\n";
			echo '		<td><img src="/wfiles/iconsets/16/info.png" border="0" class="w_middle"> '.$help.'</td>'."\n";
			echo '	</tr>'."\n";
			//dreamhost API
//			$key='dreamhost_api';
//			$formfield=getDBFieldTag(array('-table'=>'_settings','-field'=>'key_value','name'=>"set_global_{$key}",'value'=>$SETTINGS[$key],'inputtype'=>'text','width'=>200,'maxlength'=>255));
//			$help='Enter your Dreamhost API key if you are hosting on dreamhost and want to manage your account from within WaSQL. <a class="w_link w_dblue" href="http://www.dreamhost.com/r.cgi?210166">Click Here to Signup for a DreamHost account.</a>';
//			echo '	<tr valign="top"><td nowrap><img src="/wfiles/dreamhost.png" border="0" style="vertical-align:middle;"> '.friendlyName($key).'</b></td><td>'.$formfield.'</td><td><img src="/wfiles/iconsets/16/info.png" border="0" style="vertical-align:middle;"> '.$help.'</td></tr>'."\n";
			
			echo buildTableEnd();
			echo buildFormSubmit("Save Settings");
			echo buildFormEnd();
			echo '</div>'."\n";
			//echo printValue($_REQUEST);
			break;
		case 'about':
			//show DB Info, Current User, Link to WaSQL, Version
			global $CONFIG;
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/info.png" border="0"> About WaSQL</div>'."\n";
			echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table">'."\n";
			//Database Information
			echo '<tr><th colspan="2">Config.xml Settings for '.$_SERVER['HTTP_HOST'].'</th></tr>'."\n";
			ksort($CONFIG);
			foreach($CONFIG as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				echo "<tr><th align=\"left\">{$key}:</th><td>{$val}</td></tr>\n";
            	}
			//Version Information
			//$cver=curl_version();
			$versions=getAllVersions();
			echo '<tr><th colspan="2">Version Information</th></tr>'."\n";
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
            echo '<tr><th colspan="2">Server Information</th></tr>'."\n";
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
            echo '<tr><th colspan="2">Other Information</th></tr>'."\n";
            foreach($versions as $key=>$version){
				if(!strlen($version)){continue;}
				echo "<tr valign=\"top\"><th align=\"left\">{$key}:</th><td>{$version}</td></tr>\n";
            	}
            echo '</table>'."\n";
			break;
		case 'svn':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/subversion.png" border="0"> WaSQL SVN Update</div>'."\n";
			if(isset($_REQUEST['_update']) && $_REQUEST['_update']==1){
				$wpath=getWasqlPath();
				$cmd="svn update \"{$wpath}\"";
				$res = cmdResults($cmd);
				if(isset($res['stdout'])){
					echo nl2br($res['stdout']);
                	}
            	}
			$info=svnInfo();
			if(!is_array($info) || !isset($info['head']) || !isset($info['local'])){
				echo "<div><img src=\"/wfiles/warn.gif\" border=\"0\"> Unable to determine SVN version. Perhaps you do not have svn installed or in your path.</div>\n";
				}
			elseif($info['local']['repository_uuid']!=$info['head']['repository_uuid']){
				echo "<div><img src=\"/wfiles/warn.gif\" border=\"0\"> Repository Mismatch</div>\n";
            	}
			elseif($info['local']['revision']!=$info['head']['revision']){
				echo '<table><tr><td><img src="/wfiles/iconsets/32/star.png" border="0" style="vertical-align:middle;"></td><td>Updates are available</td><td><a class="w_link w_dblue" href="/'.$PAGE['name'].'?_menu=svn&_update=1"><img src="/wfiles/iconsets/32/down.png" border="0" style="vertical-align:middle;"> Update Now</a></td></tr></table>'."\n";
            	echo '<br clear="both">'."\n";
				echo '<table cellpadding="2" cellspacing="0" border="1" class="w_table">'."\n";
				echo '	<tr><th colspan="2">LOCAL Revision</th></tr>'."\n";
				foreach($info['local'] as $key=>$val){
					echo '	<tr><td align="left"><b>'.ucwords(str_replace('_',' ',$key)).'</b></td><td>'.$val.'</td></tr>'."\n";
                	}
				echo '	<tr><th colspan="2">HEAD Revision</th></tr>'."\n";
				foreach($info['head'] as $key=>$val){
					echo '	<tr><td align="left"><b>'.ucwords(str_replace('_',' ',$key)).'</b></td><td>'.$val.'</td></tr>'."\n";
                	}
                echo '</table>'."\n";
            	}
            else{
				echo '<table><tr><td><img src="/wfiles/iconsets/32/checkmark.png" border="0" style="vertical-align:middle;"></td><td>Up to date</td></tr></table>'."\n";
            	echo '<br clear="both">'."\n";
				echo '<table cellpadding="2" cellspacing="0" border="1" class="w_table">'."\n";
				echo '	<tr><th colspan="2">Revision Info</th></tr>'."\n";
				foreach($info['local'] as $key=>$val){
					echo '	<tr><td align="left"><b>'.ucwords(str_replace('_',' ',$key)).'</b></td><td>'.$val.'</td></tr>'."\n";
                	}
                echo '</table>'."\n";
				}
			echo '<p>'."\n";
            //echo printValue($info);
			break;
		case 'stats':
			//Site Stats from the _access table
			if(!isDBTable('_access')){$ok=createWasqlTable('_access');}
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/stats.gif" border="0"> Site Stats</div>'."\n";
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
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/table.gif" border="0">Tables</div>'."\n";
			echo buildFormBegin('',array('_menu'=>'tables','update'=>1));
			echo '<table cellspacing="2" cellpadding="2" border="1" class="w_table sortable">'."\n";
			echo '<thead>'."\n";
			echo '	<tr>'."\n";
			echo '		<th>Action</th>'."\n";
			echo '		<th>Tablename</th>'."\n";
			echo '		<th>Records</th>'."\n";
			echo '		<th>Fields</th>'."\n";
			echo '		<th>Group</th>'."\n";
			echo '		<th>Description</th>'."\n";
			echo '	</tr>'."\n";
			echo '</thead>'."\n";
			echo '<tbody>'."\n";
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
					'fields'=>count($fields),
					'group'=>$tablegroup[$table]['tablegroup'],
					'desc'=>$tablegroup[$table]['tabledesc']
					);
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
				if(strlen($src)){$img='<img src="'.$src.'" border="0" class="w_bottom">';}
				if(preg_match('/^\_/',$table)){
					//wasql table - do not allow people to change group
                	echo buildTableTD(array(
                		tableOptions($table,array('-format'=>'none','-options'=>'list,properties','-notext'=>1)),
						'<a style="display:block;" class="w_link w_lblue" href="/'.$PAGE['name'].'?_menu=list&_table_='.$rec['name'].'">'.$img.' '.$rec['name'].'</a>',
						$rec['records'],
						$rec['fields'],
						$rec['group'],
						$rec['desc'],
						));
				}
				else{
                	echo buildTableTD(array(
                		tableOptions($table,array('-format'=>'none','-options'=>'list,properties','-notext'=>1)),
						'<a style="display:block;" class="w_link w_lblue" href="/'.$PAGE['name'].'?_menu=list&_table_='.$rec['name'].'">'.$img.' '.$rec['name'].'</a>',
						$rec['records'],
						$rec['fields'],
						'<input type="text" name="g_'.$rec['name'].'" style="width:120px;" maxlength="50" value="'.$rec['group'].'">',
						'<input type="text" name="d_'.$rec['name'].'" style="width:300px;" maxlength="255" value="'.$rec['desc'].'">',
						));
				}
            	}
            echo '</tbody>'."\n";
			echo buildTableEnd();
			echo buildFormSubmit('Update');
			echo buildFormEnd();
			echo buildOnLoad("sorttable.init();");
			break;
		case 'summary':
			//Table Summary
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/summary.gif" border="0">Table Status</div>'."\n";
			echo listDBRecords(array(
				'-query'				=>	"show table status",
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
					if(is_array($_SESSION['admin_errors']) && count($_SESSION['admin_errors'])){
						echo '<div class="w_padding w_left">'."\n";
						echo '	<div class="w_bold"><img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle"> Error Adding Table:</div>'."\n";
						foreach($_SESSION['admin_errors'] as $adderror){
							echo "	<div class=\"w_marginleft w_red w_bold\"> - {$adderror}</div>\n";
                    	}
                    	echo '</div>'."\n";
                    	echo '<br clear="both">'."\n";
						$error=1;
						$_SESSION['admin_errors']=array();
					}
					//echo printValue($_REQUEST);
					echo buildTableBegin(2,0);
					echo '<tr valign="top"><td>'."\n";
					echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/table_add.png" border="0"> Add New table.</div>'."\n";
					echo '		<form method="POST" name="new_table" action="/'.$PAGE['name'].'" class="w_form" onSubmit="return submitForm(this);">'."\n";
					echo '			<input type="hidden" name="_menu" value="add">'."\n";
					$value=$error==1?$_REQUEST['_table_']:'';
					echo '			<b>Table Name:</b> <input type="text"  _required="1" requiredmsg="Enter a table name" style="width:330px;" maxlength="150" name="_table_" value="'.$value.'" onFocus="this.select();"><br />'."\n";
					//echo '			<img src="/wfiles/iconsets/16/group.png" border="0"> Table Group: <input type="text"  style="width:310px;" maxlength="150" name="tablegroup" value="'.$value.'" onFocus="this.select();"><br />'."\n";
					//echo '			<img src="/wfiles/iconsets/16/info.png" border="0"> Table Desc: <input type="text" style="width:315px;" maxlength="150" name="tabledesc" value="'.$value.'" onFocus="this.select();"><br />'."\n";
					echo '			<div class="w_smallest">Enter fields below (i.e. firstname varchar(255) NOT NULL)</div>'."\n";
					$value=$error==1?$_REQUEST['_schema']:'';
					echo '			<textarea _required="1" data-behavior="sqleditor" requiredmsg="Enter table fields" name="_schema" style="width:450px;height:400px;">'.$value.'</textarea>'."\n";
					echo '			<div><input type="submit" value="Create"></div>'."\n";
					echo '		</form>'."\n";
					echo buildOnLoad('document.new_table._table_.focus();');
					echo '</td><td>'."\n";
					//reference: http://www.htmlite.com/mysql003.php
					//Text Types
					echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/16/info.png" border="0" width="16" height="16" class="w_middle" style="cursor:pointer;" onclick="centerpopDiv(\'info_texttypes\');"> Text Types</div>'."\n";
					echo '<div id="info_texttypes" style="display:none;"><div style="width:500px;">'."\n";
					echo '<b class="w_dblue w_bigger">Database Text Types</b><br />CHAR and VARCHAR are the most widely used types. CHAR is a fixed length string and is mainly used when the data is not going to vary much in it\'s length. VARCHAR is a variable length string and is mainly used when the data may vary in length.</p>'."\n";
					echo '<p>CHAR may be faster for the database to process considering the fields stay the same length down the column. VARCHAR may be a bit slower as it calculates each field down the column, but it saves on memory space. Which one to ultimatly use is up to you.</p>'."\n";
					echo '<p>Using both a CHAR and VARCHAR option in the same table, MySQL will automatically change the CHAR into VARCHAR for compatability reasons.</p>'."\n";
					//echo '<p>BLOB stands for Binary Large OBject. Both TEXT and BLOB are variable length types that store large amounts of data. They are similar to a larger version of VARCHAR. These types can store a large piece of data information, but they are also processed much slower.</p>'."\n";
					echo '</div></div>'."\n";
					echo '<div style="margin-left:25px;">'."\n";
					echo 'CHAR( )	A fixed section from 0 to 255 characters long.<br />'."\n";
					echo 'VARCHAR( )	A variable section from 0 to 255 characters long.<br />'."\n";
					echo 'TINYTEXT	A string with a maximum length of 255 characters.<br />'."\n";
					echo 'TEXT	A string with a maximum length of 65535 characters.<br />'."\n";
					//echo 'BLOB	A string with a maximum length of 65535 characters.<br />'."\n";
					echo 'MEDIUMTEXT	A string with a maximum length of 16777215 characters.<br />'."\n";
					//echo 'MEDIUMBLOB	A string with a maximum length of 16777215 characters.<br />'."\n";
					echo 'LONGTEXT	A string with a maximum length of 4294967295 characters.<br />'."\n";
					//echo 'LONGBLOB	A string with a maximum length of 4294967295 characters.<br />'."\n";
					echo '</div>'."\n";
					//Number Types
					echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/16/info.png" border="0" width="16" height="16" class="w_middle" style="cursor:pointer;" onclick="centerpopDiv(\'info_numbertypes\');"> Number Types</div>'."\n";
					echo '<div id="info_numbertypes" style="display:none;"><div style="width:500px;">'."\n";
					echo '<b class="w_dblue w_bigger">Database Number Types</b><br />The integer types have an extra option called UNSIGNED. Normally, the integer goes from an negative to positive value. Using an UNSIGNED command will move that range up so it starts at zero instead of a negative number.</p>'."\n";
					echo '</div></div>'."\n";
					echo '<div style="margin-left:25px;">'."\n";
					echo 'TINYINT( )	-128 to 127 normal or 0 to 255 UNSIGNED.<br />'."\n";
					echo 'SMALLINT( )	-32768 to 32767 normal or 0 to 65535 UNSIGNED.<br />'."\n";
					echo 'MEDIUMINT( )	-8388608 to 8388607 normal or 0 to 16777215 UNSIGNED.<br />'."\n";
					echo 'INT( )	-2147483648 to 2147483647 normal or 0 to 4294967295 UNSIGNED.<br />'."\n";
					echo 'BIGINT( )	-9223372036854775808 to 9223372036854775807 normal or 0 to 18446744073709551615 UNSIGNED.<br />'."\n";
					echo 'FLOAT	A small number with a floating decimal point.<br />'."\n";
					echo 'DOUBLE( , )	A large number with a floating decimal point.<br />'."\n";
					echo 'DECIMAL( , )	A DOUBLE stored as a string , allowing for a fixed decimal point.<br />'."\n";
					echo '</div>'."\n";
					//Date Types
					echo '<div class="w_lblue w_bold">Date Types</div>'."\n";
					echo '<div style="margin-left:25px;">'."\n";
					echo 'DATE	YYYY-MM-DD.<br />'."\n";
					echo 'DATETIME	YYYY-MM-DD HH:MM:SS.<br />'."\n";
					echo 'TIMESTAMP	YYYYMMDDHHMMSS.<br />'."\n";
					echo 'TIME	HH:MM:SS.<br />'."\n";
					echo '</div>'."\n";
					echo '</td></tr></table>'."\n";
                }
				else{
					if(isset($CONFIG['dbname_stage'])){
                    	$xtables=adminGetSynchronizeTables($CONFIG['dbname_stage']);
                    	if(in_array($_REQUEST['_table_'],$xtables)){
							echo '<div class="w_bold">'."\n";
							echo '	<img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle">'."\n";
							echo '	"'.$_REQUEST['_table_'].'" is a synchronize table. New records must be added on the staging site.'."\n";
							echo '</div>'."\n";
						}
						else{
							echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
							echo '<div class="w_lblue w_bold w_bigger">Add New Record to '.$_REQUEST['_table_'].' table.</div>'."\n";
							echo addEditDBForm(array('-action'=>'/php/admin.php','-table'=>$_REQUEST['_table_'],'_table_'=>$_REQUEST['_table_'],'_menu'=>"list",'_sort'=>$_REQUEST['_sort'],'_start'=>$_REQUEST['_start']));
						}
					}
					else{
						echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
						echo '<div class="w_lblue w_bold w_bigger">Add New Record to '.$_REQUEST['_table_'].' table.</div>'."\n";
						echo addEditDBForm(array('-action'=>'/php/admin.php','-table'=>$_REQUEST['_table_'],'_table_'=>$_REQUEST['_table_'],'_menu'=>"list",'_sort'=>$_REQUEST['_sort'],'_start'=>$_REQUEST['_start']));
					}
				}
            }
			break;
		case 'addmultiple':
			echo buildTableBegin(2,0);
			echo '<tr valign="top"><td>'."\n";
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/table_add.png" border="0"> Add Multiple Tables.</div>'."\n";
			echo '	<form method="POST" name="mform" action="/'.$PAGE['name'].'" class="w_form" onSubmit="ajaxSubmitForm(this,\'centerpop\');return false;">'."\n";
			echo '		<input type="hidden" name="_menu" value="addmultiple">'."\n";
			echo '		<div class="w_smallest">Enter tablename followed by fields for that table tabbed in. See example on right.</div>'."\n";
			echo '		<textarea data-behavior="sqleditor" _required="1" name="_schema" style="width:450px;height:400px;">'.$_REQUEST['_schema'].'</textarea>'."\n";
			echo '		<div><input type="submit" value="Create"></div>'."\n";
			echo '	</form>'."\n";
			echo '</td><td>'."\n";
			//reference: http://www.htmlite.com/mysql003.php
			//Text Types
			echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/16/info.png" border="0" width="16" height="16" class="w_middle"> Sample Entry</div>'."\n";
			echo '<div style="margin-left:25px;"><pre>'."\n";
			echo 'employees'."\n";
			echo '	name varchar(55)'."\n";
			echo '	age int'."\n";
			echo '	hiredate datetime'."\n";
			echo 'companies'."\n";
			echo '	name varchar(100)'."\n";
			echo '	url varchar(255)'."\n";
			echo '	phone varchar(15)'."\n";
			echo '	email varchar(255)'."\n";
			echo '</pre></div>'."\n";
			echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/16/info.png" border="0" width="16" height="16" class="w_middle" style="cursor:pointer;" onclick="centerpopDiv(\'info_texttypes\');"> Text Types</div>'."\n";
			echo '<div id="info_texttypes" style="display:none;"><div style="width:500px;">'."\n";
			echo '<b class="w_dblue w_bigger">Database Text Types</b><br />CHAR and VARCHAR are the most widely used types. CHAR is a fixed length string and is mainly used when the data is not going to vary much in it\'s length. VARCHAR is a variable length string and is mainly used when the data may vary in length.</p>'."\n";
			echo '<p>CHAR may be faster for the database to process considering the fields stay the same length down the column. VARCHAR may be a bit slower as it calculates each field down the column, but it saves on memory space. Which one to ultimatly use is up to you.</p>'."\n";
			echo '<p>Using both a CHAR and VARCHAR option in the same table, MySQL will automatically change the CHAR into VARCHAR for compatability reasons.</p>'."\n";
			//echo '<p>BLOB stands for Binary Large OBject. Both TEXT and BLOB are variable length types that store large amounts of data. They are similar to a larger version of VARCHAR. These types can store a large piece of data information, but they are also processed much slower.</p>'."\n";
			echo '</div></div>'."\n";
			echo '<div style="margin-left:25px;">'."\n";
			echo 'CHAR( )	A fixed section from 0 to 255 characters long.<br />'."\n";
			echo 'VARCHAR( )	A variable section from 0 to 255 characters long.<br />'."\n";
			echo 'TINYTEXT	A string with a maximum length of 255 characters.<br />'."\n";
			echo 'TEXT	A string with a maximum length of 65535 characters.<br />'."\n";
			//echo 'BLOB	A string with a maximum length of 65535 characters.<br />'."\n";
			echo 'MEDIUMTEXT	A string with a maximum length of 16777215 characters.<br />'."\n";
			//echo 'MEDIUMBLOB	A string with a maximum length of 16777215 characters.<br />'."\n";
			echo 'LONGTEXT	A string with a maximum length of 4294967295 characters.<br />'."\n";
			//echo 'LONGBLOB	A string with a maximum length of 4294967295 characters.<br />'."\n";
			echo '</div>'."\n";
			//Number Types
			echo '<div class="w_lblue w_bold"><img src="/wfiles/iconsets/16/info.png" border="0" width="16" height="16" class="w_middle" style="cursor:pointer;" onclick="centerpopDiv(\'info_numbertypes\');"> Number Types</div>'."\n";
			echo '<div id="info_numbertypes" style="display:none;"><div style="width:500px;">'."\n";
			echo '<b class="w_dblue w_bigger">Database Number Types</b><br />The integer types have an extra option called UNSIGNED. Normally, the integer goes from an negative to positive value. Using an UNSIGNED command will move that range up so it starts at zero instead of a negative number.</p>'."\n";
			echo '</div></div>'."\n";
			echo '<div style="margin-left:25px;">'."\n";
			echo 'TINYINT( )	-128 to 127 normal or 0 to 255 UNSIGNED.<br />'."\n";
			echo 'SMALLINT( )	-32768 to 32767 normal or 0 to 65535 UNSIGNED.<br />'."\n";
			echo 'MEDIUMINT( )	-8388608 to 8388607 normal or 0 to 16777215 UNSIGNED.<br />'."\n";
			echo 'INT( )	-2147483648 to 2147483647 normal or 0 to 4294967295 UNSIGNED.<br />'."\n";
			echo 'BIGINT( )	-9223372036854775808 to 9223372036854775807 normal or 0 to 18446744073709551615 UNSIGNED.<br />'."\n";
			echo 'FLOAT	A small number with a floating decimal point.<br />'."\n";
			echo 'DOUBLE( , )	A large number with a floating decimal point.<br />'."\n";
			echo 'DECIMAL( , )	A DOUBLE stored as a string , allowing for a fixed decimal point.<br />'."\n";
			echo '</div>'."\n";
			//Date Types
			echo '<div class="w_lblue w_bold">Date Types</div>'."\n";
			echo '<div style="margin-left:25px;">'."\n";
			echo 'DATE	YYYY-MM-DD.<br />'."\n";
			echo 'DATETIME	YYYY-MM-DD HH:MM:SS.<br />'."\n";
			echo 'TIMESTAMP	YYYYMMDDHHMMSS.<br />'."\n";
			echo 'TIME	HH:MM:SS.<br />'."\n";
			echo '</div>'."\n";
			echo '</td></tr></table>'."\n";
			break;
		case 'drop':
			echo $dropResult;
			break;
		case 'edit':
			if(isset($_REQUEST['_table_']) && isNum($_REQUEST['_id'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">Edit Record #'.$_REQUEST['_id'].' in '.$_REQUEST['_table_'].' table.</div>'."\n";
				$rec=getDBRecord(array(
					'-table'=>$_REQUEST['_table_'],
					'_id'=>$_REQUEST['_id'],
					'-relate'=>array('_euser'=>'_users','_cuser'=>'_users'),
					'-fields'=>'_cdate,_cuser,_edate,_euser'
				));
				echo '<div class="w_lblue w_smaller" style="margin-left:20px;">'."\n";
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
				echo '</div>'."\n";
				$menu=isset($_REQUEST['_menu2'])?$_REQUEST['_menu2']:'list';
				echo addEditDBForm(array(
					'-action'=>'/php/admin.php',
					'-table'=>$_REQUEST['_table_'],
					'_table_'=>$_REQUEST['_table_'],
					'_menu'=>$menu,
					'_id'=>$_REQUEST['_id'],
					'_sort'=>$_REQUEST['_sort'],
					'_start'=>$_REQUEST['_start']
				));
            }
			break;
		case 'sandbox':
			$sessionID=session_id();
			echo buildFormBegin('',array('_menu'=>'sandbox','preview'=>1,'-onsubmit'=>"ajaxSubmitForm(this,'sandbox_test');return false;"));
			echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/iconsets/32/php.png" border="0" class="w_middle"> PHP Sandbox '.buildFormSubmit('Test Code (F5)').'</div>'."\n";
			echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table" width="100%">'."\n";
			echo buildTableTH(array('Database Tables','PHP Coding Window','Code Results Window'));
			echo '	<tr valign="top">'."\n";
			echo '		<td nowrap>'."\n";
			echo '			<div style="height:500px;overflow:auto;padding-right:30px;">'."\n";
			echo '				' . expandAjaxTables();
			echo '			</div>'."\n";
			echo '		</td>'."\n";
			echo '		<td>'."\n";
			echo '			<textarea focus="2,2" name="sandbox_code" preview="1" ajaxid="sandbox_test" style="width:500px;height:400px;" data-behavior="phpeditor">'."\n";
			echo encodeHtml('<?'.'php')."\r\n\t\r\n";
			echo encodeHtml('?'.'>')."\r\n";
			echo '			</textarea></td>'."\n";
			echo '		<td width="100%"><div id="sandbox_test" style="height:400px;overflow:auto;"></div></td>'."\n";
			echo '	</tr>'."\n";
			echo buildTableEnd();
			echo buildFormSubmit('Test Code (F5)');
			echo buildFormEnd();
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
				echo 'pdo_mysql is loaded<br>'."\n";
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
		case 'reports':
			$rec=getDBRecord(array('-table'=>'_reports','_id'=>$_REQUEST['_id']));
			echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/iconsets/32/reports.png" border="0" class="w_middle"> Report: '.$rec['name'].'</div>'."\n";
			$opts=array('-hidesearch'=>1,'-query'=>evalPHP($rec['query']));
			if(strlen($rec['list_options'])){
            	$json=json_decode(trim($rec['list_options']),true);
            	//echo "json".printValue($json);
            	if(is_array($json)){
                	foreach($json as $key=>$val){$opts[$key]=$val;}
				}
			}
			echo listDBRecords($opts);
			break;
		case 'list':
			if(isset($_REQUEST['_table_'])){
				//params
                $parts=array();
			    foreach($_REQUEST as $key=>$val){
					if(!preg_match('/^\_(sort|table_|start|search)$/i',$key)){continue;}
					$parts[$key]=$val;
			    	}
			    $parts['_menu']='edit';
			    $idurl=buildUrl($parts) . '&_id=%_id%';
				$recopts=array('_menu'=>$_REQUEST['_menu'],'_table_'=>$_REQUEST['_table_'],'-table'=>$_REQUEST['_table_'],'-action'=>'/php/admin.php','_id_href'=>'/php/admin.php?'.$idurl);
				if($_REQUEST['_table_']=='_users'){$recopts['-icons']=true;}
				if($_REQUEST['_table_']=='_access_summary'){$recopts['accessdate_dateformat']="m/Y";}
				//table Options header
                echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">List Records in ';
				$img=getImageSrc(strtolower($_REQUEST['_table_']));
				if(strlen($img)){
					echo  '<img src="'.$img.'" border="0" class="w_bottom"> ';
		        }
				echo $_REQUEST['_table_'].' table.</div>'."\n";
				//special options for some tables
				switch(strtolower($_REQUEST['_table_'])){
                	case '_cron':
                		//format the run_date
						$recopts['run_date_dateage']=1;
						$recopts['run_date_displayname']="Last Run";
						//format the frequency
						$recopts['run_length_eval']="\$t='%run_length%';return verboseTime(round(\$t,0));";
						$recopts['run_length_align']="right";
						//format active
						$recopts['active_checkmark']=1;
						$recopts['active_align']="center";
						$recopts['-fields']="_id,name,active,running,run_log,frequency,run_date,run_length,run_cmd";
                	break;
                	case '_pages':
                		$recopts['_template_relate']="id,name";
                		$recopts['-relate']=array('_template'=>'_templates');
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
							array('<a href="#" onclick="return ajaxGet(\'/php/index.php\',\'centerpopIDX\',\'ajaxid=centerpopIDX&_queryid_=%_id%&explain=1\');"><img src="/wfiles/iconsets/16/table_index.png" border="0" data-tooltip="Explain Query" class="w_middle" /></a>','function','getDBRecords'),
							array('<a href="#" onclick="return ajaxGet(\'/php/index.php\',\'centerpopIDX\',\'ajaxid=centerpopIDX&_queryid_=%_id%&view=1\');"><img src="/wfiles/iconsets/16/sql.png" border="0" data-tooltip="View This Query" class="w_middle" /></a>')
						);
						echo '<div class="w_small w_lblue" style="margin-left:20px;"><a class="w_lblue w_link" href="?_menu=settings">Query Settings:</a> Days: '.$SETTINGS['wasql_queries_days'].', Time:'.$SETTINGS['wasql_queries_time'].' seconds</div>'."\n";
                	break;
				}
				if(isset($_REQUEST['add_result']['error'])){
					echo '<div class="w_tip w_pad w_border"><img src="/wfiles/iconsets/32/abort.png" border="0" class="w_middle"><b class="w_red"> Add Failed:</b> '.printValue($_REQUEST['add_result']).'</div>'."\n";
                	}
                elseif(isset($_REQUEST['edit_result']['error'])){
					echo '<div class="w_tip w_pad w_border"><img src="/wfiles/iconsets/32/abort.png" border="0" class="w_middle"><b class="w_red"> Edit Failed:</b>: '.printValue($_REQUEST['edit_result']).'</div>'."\n";
                	}
                
				echo listDBRecords($recopts);
            	}
			break;
		case 'synchronize':
			if(!isset($SETTINGS['wasql_synchronize']) || $SETTINGS['wasql_synchronize']==0){
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/iconsets/32/synchronize.png" border="0" class="w_middle"> Synchronize Manager</div>'."\n";
				//currently turned off
				echo 'Synchronize Manager is currently off. Use the Settings options under the WaSQL menu to turn it on.'."\n";
				echo $infobox;
				break;
            	}
            if(!strlen($SETTINGS['wasql_synchronize_master']) || !strlen($SETTINGS['wasql_synchronize_slave'])){
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/iconsets/32/synchronize.png" border="0" class="w_middle"> Synchronize Manager</div>'."\n";
				//currently turned off
				echo 'Synchronize Manager is on but the live and stage databases are not selected. Use the Settings options under the WaSQL menu to set these.'."\n";
				echo $infobox;
				break;
            	}
            $db_stage=$SETTINGS['wasql_synchronize_master'];
	        $db_live=$SETTINGS['wasql_synchronize_slave'];
	        $stables=adminGetSynchronizeTables();
            echo buildTableBegin(0,0);
            echo '	<tr valign="bottom">'."\n";
            echo '		<td valign="top"><img src="/wfiles/iconsets/32/synchronize.png" border="0" class="w_middle"></td>'."\n";
            echo '		<td valign="top">'."\n";
            echo '			<div class="w_lblue w_bigger"> Synchronize Manager</div>'."\n";
            echo '			<idv class="w_lblue w_smaller w_padleft"><b>Staging Database</b> = '.$db_stage.', <b>Live Database</b> = '.$db_live.'</div>'."\n";
            echo '			<div class="w_lblue w_smaller w_padleft"><b>Tables to sync:</b> '.implode(', ',$stables).'</div>'."\n";
            echo '		</td>'."\n";
            echo '		<td style="padding-left:25px;"><a class="w_link w_lblue w_bold" href="/php/admin.php?_menu=settings"><img src="/wfiles/iconsets/16/settings.png" border="0"> Settings</a></td>'."\n";
            echo '	</tr>'."\n";
            echo buildTableEnd();
            echo '<hr size="1">'."\n";
			echo '<div id="synchronize_changes">'."\n";
			echo adminShowSyncChanges($stables);
			echo '</div>'."\n";
			break;
		case 'schema':
			if(isset($_REQUEST['_table_'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/schema.gif" border="0"> Schema for '.$_REQUEST['_table_'].'</div>'."\n";
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
							$cfields['_aip']="char(15) NULL";
							$cfields['_env']="text NULL";
							$cfields['_sid']="varchar(150) NULL";
							break;
						case '_templates':
							$cfields['_adate']=databaseDataType('datetime')." NULL";
							break;
						case '_pages':
							$cfields['_adate']=databaseDataType('datetime')." NULL";
							$cfields['_aip']="char(15) NULL";
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
				echo '<table cellspacing="0" cellpadding="4" border="0">'."\n";
				echo '<tr valign="top"><td>'."\n";
				echo listDBRecords(array('_menu'=>$_REQUEST['_menu'],'_table_'=>$_REQUEST['_table_'],'-list'=>$list));
				echo '</td><td>'."\n";
				echo buildFormBegin('',array('_menu'=>"schema",'_table_'=>$_REQUEST['_table_']));
				echo '<textarea name="_schema" style="width:300px;height:400px;">'."\n";
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
				echo '</textarea><br />'."\n";
				echo buildFormSubmit("Modify");
				echo buildFormEnd();
				echo '</td></tr></table>'."\n";
				}
			else{
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/schema.gif" border="0"> Schema for All Tables</div>'."\n";
				$list=getDBSchema();
				echo listDBRecords(array('_menu'=>$_REQUEST['_menu'],'_table_'=>$_REQUEST['_table_'],'-list'=>$list));
				}

			break;
		case 'truncate':
			if(isset($_REQUEST['_table_'])){
				echo tableOptions($_REQUEST['_table_'],array('-format'=>'table','-notext'=>1));
				echo '<div class="w_lblue w_bold w_bigger">Truncate '.$_REQUEST['_table_'].' table.</div>'."\n";
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
                    	echo '<div class="w_tip w_pad w_border">'.$ok.'</div>'."\n";
					}
                }
                elseif(isset($_REQUEST['_index_drop']) && strlen($_REQUEST['_index_drop'])){
					$ok=dropDBIndex(array(
						'-table'	=> $_REQUEST['_table_'],
						'-name' 	=> $_REQUEST['_index_drop'],
					));
					if(stringContains($ok,'error')){
                    	echo '<div class="w_tip w_pad w_border">'.$ok.'</div>'."\n";
					}
                }
                //show indexes
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/indexes.gif" border="0"> Indexes for ';
				$img=getImageSrc(strtolower($_REQUEST['_table_']));
				if(strlen($img)){
					echo  '<img src="'.$img.'" border="0" class="w_bottom"> ';
		        }
				echo $_REQUEST['_table_'].'</div>'."\n";
				$list=getDBIndexes(array($_REQUEST['_table_']));
				$cnt=count($list);
				for($i=0;$i<$cnt;$i++){
					if(preg_match('/Primary/i',$list[$i]['key_name'])){continue;}
					$list[$i]['_id']='<a title="Drop index" onclick="return confirm(\'Drop index for column \\\''.$list[$i]['key_name'].'\\\'?\');" href="/'.$PAGE['name'].'?_menu=indexes&_table_='.$_REQUEST['_table_'].'&_index_drop='.$list[$i]['key_name'].'"><img src="/wfiles/drop.gif" border="0"></a>';
                	}
				//echo printValue($list);
				echo buildFormBegin('',array('_menu'=>"indexes",'_table_'=>$_REQUEST['_table_']));
				$used=array();
				$tfields=getDBFields($_REQUEST['_table_'],1);
				foreach($list as $index){$used[$index['column_name']]=1;}
				foreach($tfields as $tfield){
					if(!isset($used[$tfield])){$fields[]=$tfield;}
					}
				echo '<div><b>Select fields to index</b></div>'."\n";
				echo '<div style="margin-left:30px;">'."\n";
				echo getDBFieldTag(array('-table'=>'_tabledata','-field'=>'fieldname','-all'=>0,'name'=>'_indexfields_','inputtype'=>'checkbox','width'=>10,'tvals'=>join("\r\n",$fields),'dvals'=>join("\r\n",$fields)));
				echo '</div>'."\n";
				echo '<input type="checkbox" name="fulltext" value="1" id="fulltextid"><label for="fulltextid">FullText</label>'."\n";
				echo '<input type="checkbox" name="unique" value="1" id="uniqueid"><label for="uniqueid">Unique</label>'."\n";
				echo buildFormSubmit("Create Index");
				echo buildFormEnd();
				//echo printValue($_REQUEST);
				}
			else{
				echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/indexes.gif" border="0"> Indexes for All Tables</div>'."\n";
				$list=getDBIndexes();
				}
			echo listDBRecords(array('_menu'=>$_REQUEST['_menu'],'_table_'=>$_REQUEST['_table_'],'-list'=>$list));
			//echo printValue($list);
			break;
		case 'postedit':
			$psize=filesize("$progpath/../postedit/postedit.exe");
			echo '<div class="w_bigger w_lblue w_bold"><img src="/wfiles/postedit.gif" border="0"> PostEdit Manager</div>'."\n";
			echo '<div style="width:800px;">'."\n";
			echo '<p><b>PostEdit Manager</b> is a windows application that downloads your pages and templates into a <b>PostEdit</b> folder on your local hard drive.'."\n";
			echo 'This allows you to use any editor you wish to update your pages and templates.'."\n";
			echo 'When the <b>PostEdit Manager</b> detects a file changed it checks for syntax and commits your changes to the website database.'."\n";
			echo '</p><p>'."\n";
			echo '<b>PostEdit Manager</b> requires a configuration file called <b>postedit.xml</b>.'."\n";
			echo 'This file contains authentication information for each domain/website you want to connect to.'."\n";
			echo 'This following entry could be added to postedit.xml to authenticate to this domain as the current user:'."\n";
			echo '<pre><xmp>'."\n";
			echo '<host'."\n";
			echo '	name="'.$_SERVER['HTTP_HOST'].'"'."\n";
			echo '	group="'.$_SERVER['UNIQUE_HOST'].'"'."\n";
			echo '	apikey="'.$USER['apikey'].'"'."\n";
			echo '	username="'.$USER['username'].'"'."\n";
			echo '/>'."\n";
			echo '</xmp></pre>'."\n";
			echo 'The xml download below contains the above entry. Make sure postedit.xml and postedit.exe are in the same folder.'."\n";
			echo 'Possible host attributes and their explanations are as follows (red attributes are required):'."\n";
			echo '<ul>'."\n";
			echo '	<li><b class="w_red">name</b> - this is the hostname you want to connect to. It should correlate to the host name in yourr config.xml file</li>'."\n";
			echo '	<li><b class="w_red">username</b> - the username to authenticate as. This must be a valid username for this domain.</li>'."\n";
			echo '	<li><b class="w_red">apikey</b> - the apikey for the authenticating user. This is found in the user profile menu after logging in.'."\n";
			echo '		<ul style="list-style-image:url(/wfiles/post.gif);">'."\n";
			echo '			<li> Changing your username or password will change your apikey.'."\n";
			echo '		</ul>'."\n";
			echo '	</li>'."\n";
			echo '	<li><b>checks</b> - default checks to perform when a file change is detected before uploading change to website.'."\n";
			echo '		<ul style="list-style-image:url(/wfiles/post.gif);">'."\n";
			echo '			<li> You must have PHP installed locally and in your PATH to check PHP syntax.'."\n";
			echo '			<li> You must have Perl installed and in your PATH to check Perl syntax.'."\n";
			echo '		</ul>'."\n";
			echo '	</li>'."\n";
			echo '	<li><b>tables</b> - tables to download locally so you can modify them. This defaults to "_pages,_templates".</li>'."\n";
			echo '</ul>'."\n";
			echo '</p><p>'."\n";
			echo '<a class="w_link" href="/'.$PAGE['name'].'?_menu=postedit_zip"><img src="/wfiles/dropdown.gif" border="0">Download PostEdit Program</a>'."\n";
			echo ' <span style="font-size:9pt;">('.verboseSize($psize).')</span><br>';
			echo '<a class="w_link" href="/'.$PAGE['name'].'?_menu=postedit_xml"><img src="/wfiles/dropdown.gif" border="0">Download sample PostEdit XML file</b></a>'."\n";
			echo '</p>'."\n";
			echo '</div>'."\n";
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
                $fields=array('synchronize','tablegroup','tabledesc','listfields','sortfields','formfields','listfields_mod','sortfields_mod','formfields_mod');
                $editopts=array();
                foreach($fields as $field){
					if($_REQUEST[$field] && array2String($_REQUEST[$field]) != array2String($tinfo[$field])){
						$editopts[$field]=$_REQUEST[$field];
						}
					elseif((!$_REQUEST[$field] || !strlen(trim($_REQUEST[$field]))) && $tinfo[$field]){
						$editopts[$field]=$field=='synchronize'?0:'NULL';
						}
					}
                if(count($editopts)){
					$editopts['-table']='_tabledata';
					$editopts['-where']="tablename = '{$currentTable}'";
					if(getDBCount($editopts)){$ok=editDBRecord($editopts);}
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
            echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/properties.png" border="0"> Table Properties for ';
			$img=getImageSrc(strtolower($currentTable));
				if(strlen($img)){
					echo  '<img src="'.$img.'" border="0" class="w_bottom"> ';
		        }
			echo $currentTable.' Table</div>'."\n";
			echo '<table border="0"><tr valign="top"><td>'."\n";
			echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table" style="border:1px solid #020381;">'."\n";
			echo '<tr><th colspan="7"><img src="/wfiles/iconsets/16/database.png" border="0" style="vertical-align:middle"> Database Properties</th><th colspan="8"><img src="/wfiles/icons/files/html.gif" border="0" style="vertical-align:middle"> META Properties</th></tr>'."\n";
			echo '	<tr>'."\n";
			echo '		<th>Name</th>'."\n";
			echo '		<th>Type</th>'."\n";
			echo '		<th>Len</th>'."\n";
			echo '		<th>Null</th>'."\n";
			echo '		<th>Key</th>'."\n";
			echo '		<th>Value</th>'."\n";
			echo '		<th>Extra</th>'."\n";
			echo '		<th>Name</th>'."\n";
			echo '		<th>Type</th>'."\n";
			echo '		<th>Width</th>'."\n";
			echo '		<th>Height</th>'."\n";
			echo '		<th>Max</th>'."\n";
			echo '		<th>Req</th>'."\n";
			echo '		<th>Mask</th>'."\n";
			echo '		<th>List</th>'."\n";
			echo '	</tr>'."\n";
			$row=0;
			foreach($fields as $field){
				$row++;
				$frec=getDBRecord(array('-table'=>"_fielddata",'tablename'=>$currentTable,'fieldname'=>$field));
				$id=is_array($frec)?$frec['_id']:0;
				$onclick='return ajaxAddEditForm(\'_fielddata\','.$id.',\'\',\'_menu=properties&fieldname='.$field.'&tablename='.$currentTable.'&_table_='.$currentTable.'\');';
				if(isEven($row)){echo '	<tr class="w_even">'."\n";}
				else{echo '	<tr>'."\n";}
				$val="{$tinfo['fieldinfo'][$field]['_dbtype']} ({$tinfo['fieldinfo'][$field]['_dblength']})";
				if(preg_match('/not_null/i',$tinfo['fieldinfo'][$field]['_dbflags'])){$val .= ' NOT NULL';}
				else{$val .= ' NULL';}
				//echo printValue($tinfo['fieldinfo'][$field]['_dbflags']);
				if(preg_match('/unique_key/i',$tinfo['fieldinfo'][$field]['_dbflags'])){$val .= ' Unique';}
				if(preg_match('/primary_key/i',$tinfo['fieldinfo'][$field]['_dbflags'])){$val .= ' Primary Key';}
				if(preg_match('/auto_increment/i',$tinfo['fieldinfo'][$field]['_dbflags'])){$val .= ' auto_increment';}
				if(preg_match('/^\_/',$field)){
					echo '		<td><a class="w_gray w_link" href="#'.$id.'" onclick="'.$onclick.'" class="w_link">'.$field.'</a></td>'."\n";
					}
				else{
					echo '		<td><a class="w_lblue w_link w_bold" href="#'.$id.'" onclick="'.$onclick.'" class="w_link">'.$field.'</a></td>'."\n";
					}
				echo '		<td class="w_gray">'.$tinfo['fieldinfo'][$field]['_dbtype'].'</td>'."\n";
				echo '		<td class="w_gray" align="right">'.$tinfo['fieldinfo'][$field]['_dblength'].'</td>'."\n";
				echo '		<td class="w_gray">'.$tinfo['fieldinfo'][$field]['_dbnull'].'</td>'."\n";
				echo '		<td class="w_gray">'.$tinfo['fieldinfo'][$field]['_dbkey'].'</td>'."\n";
				echo '		<td class="w_gray" align="right">'.$tinfo['fieldinfo'][$field]['_dbdefault'].'</td>'."\n";
				echo '		<td class="w_gray">'.$tinfo['fieldinfo'][$field]['_dbextra'].'</td>'."\n";
				echo '		<td class="w_gray" style="font-size:9pt;color:#848484;" nowrap>'.$tinfo['fieldinfo'][$field]['displayname'].'</td>'."\n";
				if(strlen($tinfo['fieldinfo'][$field]['inputtype'])){
					echo '		<td class="w_gray" style="font-size:9pt;color:#848484;" nowrap><img style="vertical-align:middle" src="/wfiles/icons/form/'.$tinfo['fieldinfo'][$field]['inputtype'].'.png" border="0" alt="'.$tinfo['fieldinfo'][$field]['inputtype'].'" title="'.$tinfo['fieldinfo'][$field]['inputtype'].'" width="16" height="16"> '.$tinfo['fieldinfo'][$field]['inputtype'].'</td>'."\n";
				}
				else{
                	echo '		<td class="w_gray" style="font-size:9pt;color:#848484;" nowrap>'.$tinfo['fieldinfo'][$field]['inputtype'].'</td>'."\n";
				}
				foreach($formfields as $formfield){
					$val=$tinfo['fieldinfo'][$field][$formfield];
					if(isNum($val)){
						if($val==1){
							if($formfield=='required'){$val='<b class="w_red">*</b>';}
							elseif($formfield=='editlist'){$val='<img src="/wfiles/iconsets/16/list.png" border="0">';}
							}
						elseif($val==0){$val='';}
						echo '		<td align="right">'.$val.'</td>'."\n";
                    	}
					else{echo '		<td>'.$val.'</td>'."\n";}
					}
				$recopts=array('-table'=>"_fielddata",'-where'=>"tablename = '{$currentTable}' and fieldname = '{$field}'");
				$rec=getDBRecord($recopts);
				//echo printValue($rec) . printValue($recopts) . "<hr>";
				$id=is_array($rec)?$rec['_id']:"''";
				echo '	</tr>'."\n";
            	}
            echo '</table>';
            echo '</td><td>'."\n";
            $list=getDBSchema(array($currentTable));
			echo buildFormBegin('',array('_menu'=>"properties",'_table_'=>$currentTable));
			echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table" style="border:1px solid #020381;">'."\n";
            echo '	<tr><th><img src="/wfiles/iconsets/16/database.png" border="0" style="vertical-align:middle"> Table Schema Editor</th></tr>'."\n";
            echo '	<tr valign="top"><td>'."\n";
            $height=300;
            if(count($list) > 15){
            	$height=round((count($list)*10),0);
            	if($height > 700){$height=700;}
            	if($height < 300){$height=300;}
			}
			echo '		<textarea name="_schema" data-behavior="sqleditor" wrap="off" style="font-size:9pt;width:400px;height:'.$height.'px;">'."\n";
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
			echo '		</textarea><br clear="both" />'."\n";
			echo '<div align="right">'.buildFormSubmit("Save Schema Changes").'</div>'."\n";
			echo buildFormEnd();
			echo '	</td></tr>'."\n";
			echo '</table>'."\n";
            echo '</td></tr>'."\n";
            echo '<tr valign="top"><td colspan="2">'."\n";
            echo buildFormBegin('',array('_menu'=>"properties",'_table_'=>$currentTable));
            echo buildFormSubmit("Save Changes","do");
            echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table" style="border:1px solid #4A79B3;">'."\n";
            //General Table Settings
            echo '	<tr valign="top">'."\n";
			echo '		<th colspan="2" align="left"><img src="/wfiles/iconsets/16/generic.png" border="0"> General Table Settings</th>'."\n";
			echo '	</tr>'."\n";
			//synchronize
			$_REQUEST['synchronize']=$tinfo['synchronize'];
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue">'."\n";
			echo buildTableRow(array(
				'<img src="/wfiles/iconsets/16/synchronize.png" border="0">',
				buildFormField('_tabledata','synchronize'),
				'Synchronize'
			));
			//echo '			<input type="checkbox" name="synchronize" value="1"'.$checked.' />'."\n";
			//echo '			 Synchronize'."\n";
			echo '		</td>'."\n";
			echo '		<td>'."\n";
			echo '			<div class="w_dblue">Synchronize this table is synchronize is turned on</div>'."\n";
			echo '		</td>'."\n";
			echo '	</tr>'."\n";
			//table group and description
			$_REQUEST['tablegroup']=$tinfo['tablegroup'];
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue">'."\n";
			echo '			<div style="width:150px">'."\n";
			echo '				<div><img src="/wfiles/iconsets/16/group.png" border="0"> Table Group</div>'."\n";
			echo '					'.buildFormField('_tabledata','tablegroup')."\n";
			//echo '				<input type="text" name="tablegroup" style="width:145px;" min="5" max="50" value="'.$tinfo['tablegroup'].'">'."\n";
			echo '				<div>Allows grouping in admin table menu.</div>'."\n";
			echo '			</div>'."\n";
			echo '		</td>'."\n";
			$_REQUEST['tabledesc']=array2String($tinfo['tabledesc']);
			echo '		<td>'."\n";
			echo '			<div class="w_dblue"><img src="/wfiles/iconsets/16/info.png" border="0"> Table Description:</div>'."\n";
			echo '					'.buildFormField('_tabledata','tabledesc')."\n";
			//echo '			<textarea style="width:550px;height:50px;" onfocus="autoGrow(this)" onblur="this.style.height=\'50px\';" onKeypress="autoGrow(this)" name="tabledesc">'.$val.'</textarea>'."\n";
			echo '		</td>'."\n";
			echo '	</tr>'."\n";
			//Table Admin List fields
            echo '	<tr valign="top">'."\n";
			echo '		<th colspan="2" align="left"><img src="/wfiles/icons/users/admin.gif" border="0"> Administrator Settings</th>'."\n";
			echo '	</tr>'."\n";
            echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/iconsets/16/list.png" border="0"> List Fields - fields to display when listing records</div></td>'."\n";
			$_REQUEST['listfields']=array2String($tinfo['listfields']);
			echo '		<td>'.buildFormField('_tabledata','listfields').'</td>'."\n";
			echo '	</tr>'."\n";
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/down.gif" border="0"> Sort Fields -  default sorting order</div></td>'."\n";
			$_REQUEST['sortfields']=array2String($tinfo['sortfields']);
			echo '		<td>'.buildFormField('_tabledata','sortfields').'</td>'."\n";
			//echo '		<td><textarea style="width:550px;height:30px;" onfocus="autoGrow(this)" onblur="this.style.height=\'30px\';" onKeypress="autoGrow(this)" name="sortfields">'.$val.'</textarea></td>'."\n";
			echo '	</tr>'."\n";
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/icons/files/html.gif" border="0"> Form Fields - order of fields to display when showing a form</div></td>'."\n";
			$_REQUEST['formfields']=array2String($tinfo['formfields']);
			echo '		<td>'.buildFormField('_tabledata','formfields').'</td>'."\n";
			//echo '		<td><textarea style="width:550px;height:100px;" onfocus="autoGrow(this)" onblur="this.style.height=\'100px\';" onKeypress="autoGrow(this)" name="formfields">'.$val.'</textarea></td>'."\n";
			echo '	</tr>'."\n";
			//Non Admin Settings
			echo '	<tr valign="top">'."\n";
			echo '		<th colspan="2" align="left"><img src="/wfiles/icons/users/user.gif" border="0"> Non-Administrator Settings</th>'."\n";
			echo '	</tr>'."\n";
            echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/iconsets/16/list.png" border="0"> List Fields - fields to display when listing records</div></td>'."\n";
			$_REQUEST['listfields_mod']=array2String($tinfo['listfields_mod']);
			echo '		<td>'.buildFormField('_tabledata','listfields_mod').'</td>'."\n";
			//echo '		<td><textarea style="width:550px;height:50px;" onfocus="autoGrow(this)" onblur="this.style.height=\'50px\';" onKeypress="autoGrow(this)" name="listfields_mod">'.$val.'</textarea></td>'."\n";
			echo '	</tr>'."\n";
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/down.gif" border="0"> Sort Fields -  default sorting order</div></td>'."\n";
			$_REQUEST['sortfields_mod']=array2String($tinfo['sortfields_mod']);
			echo '		<td>'.buildFormField('_tabledata','sortfields_mod').'</td>'."\n";
			//echo '		<td><textarea style="width:550px;height:30px;" onfocus="autoGrow(this)" onblur="this.style.height=\'30px\';" onKeypress="autoGrow(this)" name="sortfields_mod">'.$val.'</textarea></td>'."\n";
			echo '	</tr>'."\n";
			echo '	<tr valign="top">'."\n";
			echo '		<td class="w_dblue"><div style="width:150px"><img src="/wfiles/icons/files/html.gif" border="0"> Form Fields - order of fields to display when showing a form when not logged in as administrator</div></td>'."\n";
			$_REQUEST['formfields_mod']=array2String($tinfo['formfields_mod']);
			echo '		<td>'.buildFormField('_tabledata','formfields_mod').'</td>'."\n";
			//echo '		<td><textarea style="width:550px;height:100px;" onfocus="autoGrow(this)" onblur="this.style.height=\'100px\';" onKeypress="autoGrow(this)" name="formfields_mod">'.$val.'</textarea></td>'."\n";
			echo '	</tr>'."\n";
			echo '</table>'."\n";
			echo buildFormSubmit("Save Changes","do");
			echo buildFormEnd();
			echo '</td></tr>'."\n";
			echo '</table>'."\n";
			break;
		case 'sqlprompt':
			//echo '<div class="w_lblue w_bold w_bigger"> SQL Prompt</div>'."\n";
			if(isNum($_REQUEST['_qid'])){
            	$rec=getDBRecord(array('-table'=>"_queries",'_id'=>$_REQUEST['_qid']));
            	if(is_array($rec)){$_REQUEST['sqlprompt_command']="EXPLAIN\r\n".$rec['query'];}
			}
			echo sqlPrompt();
			//echo printValue($_REQUEST);
			break;
		case 'optimize':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_optimize.png" border="0" class="w_middle"> Optimize Tables</div>'."\n";
			$rtn=optimizeDB();
			echo "<div>Command: {$rtn['command']}</div>\n";
			echo nl2br($rtn['result']);
			break;
		case 'backup':
			$_REQUEST['func']="backup";
		case 'backups':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_backup.png" border="0"> Backups</div>'."\n";
			$backupdir=getWasqlPath('sh/backups');
			if(isset($_REQUEST['func'])){
            	switch(strtolower($_REQUEST['func'])){
                	case 'backup':
                		$dump=dumpDB(requestValue('_table_'));
                		if(!isset($dump['success'])){
							echo '<img src="/wfiles/x_red.gif" border="0"> <b>Backup Command Failed</b><br>'."\n";
							echo '<div style="margin-left:50px;">'."\n";
							echo '	<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'."\n";
							echo '	<div><b>Error:</b> '.$dump['error'].'</div>'."\n";
							echo '</div>'."\n";
						}
						else{
							echo '<img src="/wfiles/check.png" border="0"> <b>Backup Successful</b><br>'."\n";
							echo '<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'."\n";
			            }
                		break;
					case 'backup now':
                		$dump=dumpDB();
                		if(!isset($dump['success'])){
							echo '<img src="/wfiles/x_red.gif" border="0"> <b>Backup Command Failed</b><br>'."\n";
							echo '<div style="margin-left:50px;">'."\n";
							echo '	<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'."\n";
							echo '	<div><b>Error:</b> '.$dump['error'].'</div>'."\n";
							echo '</div>'."\n";
						}
						else{
							echo '<img src="/wfiles/check.png" border="0"> <b>Backup Successful</b><br>'."\n";
							echo '<div class="w_small"><b>Command:</b> '.$dump['command'].'</div>'."\n";
			            }
                		break;
                	case 'delete':
                		if(!is_array($_REQUEST['name']) || !count($_REQUEST['name'])){
                        	echo '<div>No Files Selected to Delete</div>'."\n";
						}
						else{
                        	foreach($_REQUEST['name'] as $name){
								unlink("{$backupdir}/{$name}");
							}
						}
                		break;
				}
			}
			echo '<div>Backup Directory: '.$backupdir.'</div>'."\n";
			echo '<div>DBName: '.$CONFIG['dbname'].'</div>'."\n";
			$files=listFilesEx($backupdir,array('name'=>$CONFIG['dbname'].'__'));

			echo buildFormBegin('',array('_menu'=>'backups'));
			echo buildFormSubmit('Backup Now','func');
			if(is_array($files) && count($files)){
				$list=array();
				$filecnt=count($files);
				for($x=0;$x<$filecnt;$x++){
					if(!stringBeginsWith($files[$x]['name'],$CONFIG['dbname'].'__')){
                    	continue;
					}
					$rec=$files[$x];
	            	$rec['download']='<a class="w_link w_block" style="padding:0 3px 0 3px" href="/php/admin.php?_pushfile='.encodeBase64($rec['afile']).'" data-tooltip="Click to Download" data-tooltip_position="right"><img src="/wfiles/iconsets/16/download.png" border="0" class="w_middle" /></a>';
					$list[]=$rec;
				}
				echo listDBRecords(array(
					'-list'=>$list,
					'-fields'		=> "name,download,size_verbose,_cdate,_cdate_age_verbose",
					'download_displayname'	=> '<img src="/wfiles/iconsets/16/download.png" border="0" class="w_middle" />',
					'size_verbose_displayname'	=> 'Size',
					'_cdate_displayname'	=> 'Date Created',
					'type_align'=>'center',
					'_cdate_age_verbose_displayname'	=> 'Age',
					'_cdate_age_verbose_align'	=> 'right',
					'name_checkbox'=>1
					));
				echo buildFormSubmit('Delete','func',"return confirm('Delete selected backup files?');");
			}
			echo buildFormEnd();
			//echo printValue($_REQUEST);
			break;
		case 'email':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/email.png" style="vertical-align:middle;" border="0"> Email</div>'."\n";
			echo buildFormBegin('/php/admin.php',array('-multipart'=>true,'_menu'=>"email",'-name'=>"emailform"));
			echo '<table cellspacing="0" cellpadding="2" border="0">'."\n";
			echo '	<tr valign="top" align="left">'."\n";
			$tables=getDBTables();
			echo '		<th>Table<br><select onchange="document.emailform.submit();" name="_table_"><option value=""></option>'."\n";
			foreach($tables as $table){
				echo '				<option value="'.$table.'"';
				if(isset($_REQUEST['_table_']) && $_REQUEST['_table_']==$table){echo ' selected';}
				echo '>'.$table.'</option>'."\n";
            	}
			echo '			</select>'."</th>\n";
			if(isset($_REQUEST['_table_'])){
				//show fields for this table
				$fields=getDBFields($_REQUEST['_table_']);
				echo '		<th>Email Field<br><select onchange="document.emailform.submit();" name="_field_"><option value=""></option>'."\n";
				foreach($fields as $field){
					echo '				<option value="'.$field.'"';
					if(isset($_REQUEST['_field_']) && $_REQUEST['_field_']==$field){echo ' selected';}
					echo '>'.$field.'</option>'."\n";
	            	}
				echo '			</select>'."</th>\n";
            	}
			echo '	</tr>'."\n";
			unset($recs);
			if(isset($_REQUEST['_table_']) && isset($_REQUEST['_field_'])){
				$field=$_REQUEST['_field_'];
				echo '		<tr align="left"><th colspan="2">Where: <input type="text" style="width:300px;" name="_search_" value="'.$_REQUEST['_search_'].'"></td></tr>'."\n";
				$recopts=array('-query'=>"select {$field} from {$_REQUEST['_table_']} where not({$field} is null) and not({$field}='')");
				if(isset($_REQUEST['_search_']) && strlen($_REQUEST['_search_'])){
					$recopts['-query'] .= " and ({$_REQUEST['_search_']})";
                	}
				$recs=getDBRecords($recopts);
				echo '		<tr align="left"><th colspan="2">'.count($recs).' email addresses found.</td></tr>'."\n";
				if(!isset($_REQUEST['_from_']) && isEmail($USER['email'])){$_REQUEST['_from_']=$USER['email'];}
				echo '		<tr align="left"><th colspan="2">From: <input type="text" style="width:300px;" name="_from_" mask="email" maskmsg="From must be a valid email address" _required="1" requiredmsg="From is required" value="'.$_REQUEST['_from_'].'"></td></tr>'."\n";
				echo '		<tr align="left"><th colspan="2">Subject: <input type="text" style="width:285px;" name="_subject_" _required="1" requiredmsg="Subject is required" value="'.$_REQUEST['_subject_'].'"></td></tr>'."\n";
				echo '		<tr align="left"><th colspan="2">Message<br><textarea name="message" style="width:350px;height:100px;">'.$_REQUEST['message'].'</textarea></td></tr>'."\n";

				}
			echo '</table>'."\n";
			echo '<input type="submit" name="do" value="Refresh">'."\n";
			if(is_array($recs)){
				if(strlen($_REQUEST['message'])){
					echo '<input type="submit" name="do" value="Send Email" onclick="return confirm(\'Send Email Now to recipients shown?\');">'."\n";
					}
				echo '<p><b>Recipeints List:</b></p>'."\n";
				echo '<div style="margin-left:25px;height:200px;overflow:auto;padding-right:25px;">'."\n";
				foreach($recs as $rec){
					$email=$rec[$_REQUEST['_field_']];
					echo '<div class="w_small">'.$email;
					if(isset($_REQUEST['do']) && strtolower($_REQUEST['do'])=='send email' && strlen($_REQUEST['message']) && isEmail($email)){
						$ok=wasqlMail(array('to'=>$email,'from'=>$_REQUEST['_from_'],'subject'=>$_REQUEST['_subject_'],'message'=>$_REQUEST['message']));
						echo " ... ";
						if(is_array($ok)){echo printValue($ok);}
						else{echo '<img src="/wfiles/iconsets/16/checkmark.png" border="0" style="vertical-align:middle;">'."\n";}
                    	}
					echo '</div>'."\n";
					}
				echo '</div>'."\n";
            	}
            echo buildFormEnd();
			break;
		case 'user_report':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/user.png" border="0"><img src="/wfiles/iconsets/32/charts.png" border="0"> User Report</div>'."\n";
			$recs=getDBRecords(array(
				'-table'=>'_users',
				'-order'=>"utype,lastname"
			));
			foreach($recs as $i=>$rec){
				$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
				$pwscore=passwordScore($pw);
				if($recs[$i]['utype']==0){
					//admins must have at 10/10 to get green
					if($pwscore == 10){$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/checkmark.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
					elseif($pwscore >= 8){$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
					else{$recs[$i]['pw_score']=$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/x.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
				}
				else{
					if($pwscore >= 8){$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/checkmark.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
					elseif($pwscore >= 4){$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
					else{$recs[$i]['pw_score']=$recs[$i]['pw_score']='<img src="/wfiles/iconsets/16/x.png" border="0" class="w_middle" /> '.$pwscore.'/10';}
				}
				$ctime=time();
				$recs[$i]['created']=verboseTime($ctime-$recs[$i]['_cdate_utime']);
				if(isNum($recs[$i]['_cuser']) && $recs[$i]['_cuser'] > 0){
					$crec=getDBUserById($recs[$i]['_cuser'],array('username'));
                	$recs[$i]['created'] .= " by {$crec['username']}" ;
				}
				$recs[$i]['edited']=verboseTime($ctime-$recs[$i]['_edate_utime']);
				if(isNum($recs[$i]['_euser']) && $recs[$i]['_euser'] > 0){
					$erec=getDBUserById($recs[$i]['_euser'],array('username'));
                	$recs[$i]['edited'] .= " by {$erec['username']}" ;
				}
				$recs[$i]['accessed']=verboseTime($ctime-$recs[$i]['_adate_utime']);
				$recs[$i]['type']=$recs[$i]['utype']==0?'<img src="/wfiles/iconsets/16/user_admin.png" border="0">':'<img src="/wfiles/iconsets/16/user.png" border="0">';

			}
			echo listDBRecords(array(
				'-list'=>$recs,
				'-fields'=> "_id,active,firstname,lastname,username,type,pw_score,created,edited,accessed",
				'strong_pw_align'=>'center',
				'username_href'	=> "/php/admin.php?_menu=edit&_id=%_id%&_table_=_users&_menu2=user_report",
				'_id_href'	=> "/php/admin.php?_menu=edit&_id=%_id%&_table_=_users&_menu2=user_report",
				'type_align'=>'center',
				'pw_score_align'=>'center'
				));
			//echo printValue($recs);
			//$pw=userIsEncryptedPW($ruser['password'])?userDecryptPW($ruser['password']):$ruser['password'];
			break;
		case 'grep':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_search.png" border="0"> Database Search</div>'."\n";
			echo buildFormBegin('/php/admin.php',array('-multipart'=>true,'_menu'=>"grep",'-name'=>"grepform"));
			echo '<table cellspacing="0" cellpadding="4" border="0">'."\n";
			echo '	<tr valign="top" align="center"><th>Filters:</th>'."\n";
			echo '		<th>Schema<br><input type="checkbox" name="_grep_schema" value="1">'."</th>\n";
			echo '		<th>Records<br><input type="checkbox" name="_grep_records" value="1" checked>'."</th>\n";
			$tables=getDBTables();
			echo '		<th>Table<br><select name="_table_"><option value=""></option>'."\n";
			foreach($tables as $table){
				echo '				<option value="'.$table.'"';
				if(isset($_REQUEST['_table_']) && $_REQUEST['_table_']==$table){echo ' selected';}
				echo '>'.$table.'</option>'."\n";
            	}
			echo '			</select>'."</th>\n";
			echo '	</tr><tr><th>Text:</th>'."\n";
			echo '		<td colspan="3"><input type="text" name="_grep_string" value="'.encodeHtml(requestValue('_grep_string')).'" style="width:250px;" maxlength="255"> '."</td>\n";
			echo '	</tr></table>'."\n";
			echo buildFormSubmit('Grep Database');
			echo buildFormEnd();
			echo buildOnLoad("document.grepform._grep_string.focus();");
			if(isset($_REQUEST['_grep_string']) && strlen($_REQUEST['_grep_string'])){
				$grep=array('schema'=>0,'records'=>0,'table'=>'');
				$grep['string']=$_REQUEST['_grep_string'];
				if(isset($_REQUEST['_grep_schema']) && $_REQUEST['_grep_schema']==1){$grep['schema']=1;}
				if(isset($_REQUEST['_grep_records']) && $_REQUEST['_grep_records']==1){$grep['records']=1;}
				if(isset($_REQUEST['_table_']) && strlen($_REQUEST['_table_'])){$grep['table']=$_REQUEST['_table_'];}
				//echo printValue($grep);
				//grep Schema?
				if($grep['schema']==1){
					echo '<div class="w_bold w_big w_dblue">Schema Results</div>'."\n";
					echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table">'."\n";
					echo '	<tr><th>Table</th><th>Fields</th></tr>'."\n";
					foreach($tables as $table){
						if(strlen($grep['table']) && $table != $grep['table']){continue;}
						$info=getDBFieldInfo($table);
						$vals=array();
						foreach($info as $field=>$finfo){
							if(stringContains($field,$grep['string'])){$vals[]=$field;}
                    		}
                    	if(count($vals)){echo '	<tr valign="top"><td>'.$table.'</td><td>'.implode(', ',$vals).'</td></tr>'."\n";}
                    	}
                    echo '</table>'."\n";
                	}
                //grep records?
                if($grep['records']==1){
                	echo '<div class="w_bold w_big w_dblue">Record Results</div>'."\n";
					echo '<table cellspacing="0" cellpadding="2" border="1" class="w_table">'."\n";
                	echo '	<tr><th>Table</th><th>Record</th><th>Fields</th></tr>'."\n";
					foreach($tables as $table){
						if(strlen($grep['table']) && $table != $grep['table']){continue;}
						$info=getDBFieldInfo($table);
						//echo printValue($info);
						$wheres=array();
						$fields=array();
						foreach($info as $field=>$finfo){
							switch($info[$field]['_dbtype']){
								case 'int':
									if(isNum($grep['string'])){
										$wheres[]="{$field}={$grep['string']}";
										$fields[]=$field;
										}
									break;
								case 'char':
								case 'string':
								case 'blob':
								case 'text':
									$wheres[]="{$field} like '%{$grep['string']}%'";
									$fields[]=$field;
									break;
                            	}
							}
						if(!count($wheres)){continue;}
						if(!in_array('_id',$fields)){array_unshift($fields,'_id');}
						$where=implode(' or ',$wheres);
						$recopts=array('-table'=>$table,'-where'=>$where,'-fields'=>$fields);
						//echo printValue($recopts);
						$recs=getDBRecords($recopts);
						if(is_array($recs)){
							$cnt=count($recs);
							//echo "<b>{$cnt} records in {$table}</b><br>\n";
							foreach($recs as $rec){
								$vals=array();
								foreach($rec as $key=>$val){
									if(stringContains($val,$grep['string'])){$vals[]=$key;}
                                	}
								if(count($vals)){
									echo '	<tr valign="top"><td>'.$table.'</td><td align="right"><a class="w_link" style="display:block" href="/php/admin.php?'."_table_={$table}&_menu=edit&_id={$rec['_id']}\">{$rec['_id']}</a></td><td>".implode(', ',$vals).'</td></tr>'."\n";
									}
                            	}
                        	}
                    	}
                    echo '</table>'."\n";
                	}
            	}
			break;
		case 'import':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_import.png" border="0" style="vertical-align: middle;"> Import from file</div>'."\n";
			$importmsg='';
			global $progpath;
			if(isset($_SERVER['CONTENT_TYPE']) && preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
				echo '<div>Processing Uploaded File</div>'."\n";
	 			if(isset($_REQUEST['file_abspath']) && file_exists($_REQUEST['file_abspath'])){
					if(preg_match('/XML/is',$_REQUEST['do'])){
						echo '<div class="w_lblue w_bold w_big">XML Import</div>'."\n";
						echo "<div>Reading File: {$_REQUEST['file_abspath']}</div>\n";
						$items=exportFile2Array($_REQUEST['file_abspath']);
						unlink($_REQUEST['file_abspath']);
						echo "<div>Importing Items </div>\n";
						flush();
	    				ob_flush();
						$importmsg .= importXmlData($items,$_REQUEST);
						}
					elseif(preg_match('/CSV/is',$_REQUEST['do'])){
						echo '<div class="w_lblue w_bold w_big">CSV Import</div>'."\n";
						if(!strlen($_REQUEST['_table_'])){
							echo '<div class="w_red w_bold">You must select a table for CSV imports</div>'."\n";
                        	}
                        elseif($_REQUEST['_table_']=='Create NEW Table' && !strlen($_REQUEST['_tablename_'])){
							echo '<div class="w_red w_bold">You must choose a name for the new table</div>'."\n";
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
				echo '<div class="w_red"><img src="/wfiles/warning.gif" border="0"> '.$_REQUEST['file_error'].'</div>'."\n";
            	}
			echo buildFormBegin('/php/admin.php',array('-multipart'=>true,'_menu'=>"import"));
			if(!isset($_REQUEST['_types'])){$_REQUEST['_types']=array('xmlschema','xmlmeta','xmldata');}
			if(!isset($_REQUEST['_options'])){$_REQUEST['_options']=array('drop','ids');}
			echo '	<input type="file" _required="1" name="file" size="80" acceptmsg="Only valid xml and csv files are allowed" accept="xml,csv" /><br />'."\n";
			echo '<div style="width:600px;"'."\n";
			echo '<table cellspacing="0" cellpadding="4" border="0"><tr valign="top">'."\n";
			//XML File Options
			echo '<td nowrap>'."\n";
			echo '<div class="w_lblue w_bold w_big">XML File Options</div>'."\n";
			$checked=(isset($_REQUEST['_types']) && is_array($_REQUEST['_types']) && in_array('xmlschema',$_REQUEST['_types']))?' checked':'';
			echo '	<input type="checkbox" name="_types[]" value="xmlschema" _required="1" requiredmsg="At least one import type must be selected"'.$checked.'> Schema'."\n";
			$checked=(isset($_REQUEST['_types']) && is_array($_REQUEST['_types']) && in_array('xmlmeta',$_REQUEST['_types']))?' checked':'';
			echo '	<input type="checkbox" name="_types[]" value="xmlmeta"'.$checked.'> Meta'."\n";
			$checked=(isset($_REQUEST['_types']) && is_array($_REQUEST['_types']) && in_array('xmlschema',$_REQUEST['_types']))?' checked':'';
			echo '	<input type="checkbox" name="_types[]" value="xmldata"'.$checked.'> Data'."<br />\n";
			$checked=(isset($_REQUEST['_options']) && is_array($_REQUEST['_options']) && in_array('drop',$_REQUEST['_options']))?' checked':'';
			echo '	<input type="checkbox" name="_options[]" value="drop"'.$checked.'> Drop Existing Tables with same names'."<br />\n";
			$checked=(isset($_REQUEST['_options']) && is_array($_REQUEST['_options']) && in_array('truncate',$_REQUEST['_options']))?' checked':'';
			echo '	<input type="checkbox" name="_options[]" value="truncate"'.$checked.'> Truncate Existing Records'."<br />\n";
			$checked=(isset($_REQUEST['_options']) && is_array($_REQUEST['_options']) && in_array('ids',$_REQUEST['_options']))?' checked':'';
			echo '	<input type="checkbox" name="_options[]" value="ids"'.$checked.'> Import IDs'."<br />\n";
			echo '	Merge Fields:<br /> <textarea name="_merge" style="width:300px;height:50px;" class="w_small"></textarea>'."<br />\n";
			echo '</td><td>'."\n";
			echo '<div class="w_lblue w_bold w_big">CSV File Options</div>'."\n";
			$tables=getDBTables();
			array_unshift($tables,'Create NEW Table');
			echo '<div>Table '."\n";
			echo getDBFieldTag(array('-table'=>'_tabledata','onchange'=>"if(this.value=='Create NEW Table'){showId('newtable');hideId('picktable');}else{hideId('newtable');showId('picktable');}",'-field'=>'fieldname','name'=>"_table_",'message'=>"--Select Table--",'inputtype'=>'select','tvals'=>join("\r\n",$tables)));
			//echo "TEST:" . strtotime("yesterday");
			echo '</div>'."\n";
			echo '<div style="width:300px;margin-top:5px;position:relative;height:75px;">'."\n";
			echo '	<div id="newtable" style="display:none;position:absolute;">New Tablename '."\n";
			echo getDBFieldTag(array('-table'=>'_tabledata','-field'=>'fieldname','name'=>"_tablename_",'inputtype'=>'text','width'=>150,'maxlength'=>150));
			echo '	<br>This will create a table with fields based on the data in the csv file you upload.'."\n";
			echo '	</div>'."\n";
			echo '	<div id="picktable">'."\n";
			echo '		Select the table you want to import into. Make sure the first line of the csv file matches the field names of the table.'."\n";
			echo '	</div>'."\n";
			echo '</div>'."\n";
			echo '</td></tr>'."\n";
			echo '<tr><td style="padding-top:25px;">'.buildFormSubmit('Import XML File','do').'</td><td>'.buildFormSubmit('Import CSV File','do').'</td></tr>'."\n";
			echo '</table>'."\n";
			echo '</div>'."\n";
			echo buildFormEnd();
			if(strlen($importmsg)){
				echo '<div class="w_lblue w_bold"> Import results:</div>'."\n";
				echo $importmsg;
				}
			//echo printValue($_REQUEST);
			//echo printValue($_FILES);
			break;
		case 'export':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_export.png" border="0" style="vertical-align: middle;"> Export to xml</div>'."\n";
			echo buildFormBegin('/php/admin.php',array('_menu'=>"export"));
			echo '<input id="isapp" type="checkbox" name="isapp" value="1"> <label for="isapp"><img src="/wfiles/iconsets/16/applications.png" width="16" height="16" border="0" class="w_middle" /> Export as an application</label><br />'."\n";
			$tables=getDBTables();
			echo buildTableBegin(2,1);
			$cols=array('Table','Schema','Meta','Data');
			$th=array();
			foreach($cols as $col){
				array_push($th,'<input type="checkbox" onclick="return checkAllElements(\'id\',\'ck_'.$col.'\',this.checked)">'." {$col}");
            	}
			echo buildTableTH($th);
			foreach($tables as $table){
				$td=array();
				foreach($cols as $col){
					$val='<input ck_table="'.$table.'"  id="ck_'.$col.'" type="checkbox"';
					if($col=='Table'){
						$val .= ' name="export[]" value="'.$table.'" onclick="return checkAllElements(\'ck_table\',\''.$table.'\',this.checked)"';
						if(isset($_REQUEST['export']) && is_array($_REQUEST['export']) && in_array($table,$_REQUEST['export'])){
							$val .= ' checked';
                    		}
						}
					else{
						$val .= ' name="'.$table.'_'.$col.'" value="1"';
						if(isset($_REQUEST[$table.'_'.$col]) &&  $_REQUEST[$table.'_'.$col]==1){
							$val .= ' checked';
                    		}
						}

					$val .= '>';
					if($col=='Table'){$val .= " {$table}";}
					array_push($td,$val);
					}
				echo buildTableTD($td,array('class'=>"w_smaller"));
            	}

			//export specific pages option if _pages is not checked
			$recs=getDBRecords(array('-table'=>'_pages','-order'=>'name'));
			echo '	<tr><th colspan="4"><input type="checkbox" onclick="return checkAllElements(\'id\',\'pages\',this.checked)"> Only Export specific pages (Data)</th></tr>'."\n";
			echo '	<tr><td colspan="4"><div style="height:100px;overflow:auto;">'."\n";
			foreach($recs as $rec){
				echo '	<input type="checkbox" id="pages" name="_pages_recs[]" value="'.$rec['_id'].'"> '.$rec['name']."<br>\n";
        		}
        	echo '	</div></td></tr>'."\n";
        	//export specific templatees option if _pages is not checked
			$recs=getDBRecords(array('-table'=>'_templates','-order'=>'name'));
			echo '	<tr><th colspan="4"><input type="checkbox" onclick="return checkAllElements(\'id\',\'templates\',this.checked)"> Only Export specific templates (Data)</th></tr>'."\n";
			echo '	<tr><td colspan="4"><div style="height:60px;overflow:auto;">'."\n";
			foreach($recs as $rec){
				echo '	<input type="checkbox" id="templates" name="_templates_recs[]" value="'.$rec['_id'].'"> '.$rec['name']."<br>\n";
        		}
        	echo '	</div></td></tr>'."\n";
			echo buildTableEnd();

			echo buildFormSubmit('Export');
			echo buildFormEnd();
			//echo printValue($_REQUEST);
			break;
		case 'datasync':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/database_synchronize.png" border="0" class="w_middle"> Synchronize Records</div>'."\n";
			echo '<div class="w_lblue w_smaller">Tool to transfer data between matching tables on stage and live.</div>'."\n";
			global $SETTINGS;
			//synchronize must be turned on
			if($SETTINGS['wasql_synchronize'] != 1){
            	echo '<div class="w_red w_bold">Synchronize must be turned on for this feature. To to settings to turn on.</div>'."\n";
            	break;
			}
			$stage_db=$SETTINGS['wasql_synchronize_master'];
			$live_db=$SETTINGS['wasql_synchronize_slave'];
			if(!strlen($stage_db) || !strlen($live_db)){
				echo '	<div class="w_red w_bold">Synchronize is turned on but databases are not selected. Go to settings to fix this.</div>'."\n";
            	echo '</div>'."\n";
				break;
			}
			$query="SELECT
  				table_name tablename,
  				table_rows cnt,
  				table_schema as dbname
				FROM information_schema.TABLES
				WHERE table_schema in ('{$stage_db}','{$live_db}')
				order by tablename
			";
			//echo $query;break;
			$info=array();
			$recs=getDBRecords(array('-query'=>$query));
			if(!is_array($recs) && strlen($recs)){
				echo $recs;
				break;
			}
			foreach($recs as $rec){
				$dbname=$rec['dbname'];
            	$tablename=$rec['tablename'];
            	$info[$tablename][$dbname]['cnt']=$rec['cnt'];
			}
			$query="SELECT
				table_name tablename,
				column_name field,
				column_type type,
				table_schema as dbname
				FROM information_schema.columns
				WHERE table_schema in ('{$stage_db}','{$live_db}')
				order by field
			";
			$recs=getDBRecords(array('-query'=>$query));
			if(!is_array($recs) && strlen($recs)){
				echo $recs;
				break;
			}
			foreach($recs as $rec){
				$dbname=$rec['dbname'];
            	$tablename=$rec['tablename'];
            	$field=$rec['field'];
				$info[$tablename][$dbname]['fields'][$field]=$rec['type'];
			}
			//Table Name, Rec Cnt (Stage|Live), Action (stage to live, live to stage)
			echo buildTableBegin(2,1);
			echo '<tr>'."\n";
			echo '	<th rowspan="2">Table Name</th>'."\n";
			echo '	<th colspan="2">Record Counts</th>'."\n";
			echo '	<th colspan="2">Field Counts</th>'."\n";
			echo '	<th rowspan="2">Actions</th>'."\n";
			echo '</tr>'."\n";
			echo '<tr>'."\n";
			echo '	<th>Stage</th>'."\n";
			echo '	<th>Live</th>'."\n";
			echo '	<th>Stage</th>'."\n";
			echo '	<th>Live</th>'."\n";
			echo '</tr>'."\n";
			foreach($info as $tablename=>$rec){
				$diffs=array();
				$stage_field_count=count($info[$tablename][$stage_db]['fields']);
				$live_field_count=count($info[$tablename][$live_db]['fields']);
				if(!is_array($info[$tablename][$stage_db]['fields'])){
                	$diffs[]="\"{$tablename}\" table is missing on STAGE";
                	$info[$tablename][$stage_db]['cnt']='n/a';
                	$stage_field_count='n/a';
				}
				elseif(!is_array($info[$tablename][$live_db]['fields'])){
                	$diffs[]="\"{$tablename}\" table is missing on LIVE";
                	$info[$tablename][$live_db]['cnt']='n/a';
                	$live_field_count='n/a';
				}
				elseif($stage_field_count==0 && $live_field_count==0){
                	$diffs[]="No fields";
				}
				elseif($info[$tablename][$stage_db]['cnt']==0 && $info[$tablename][$live_db]['cnt']==0){
                	$diffs[]="";
				}
				else{
					foreach($info[$tablename][$stage_db]['fields'] as $field=>$type){
						if(!in_array($field,array_keys($info[$tablename][$live_db]['fields']))){
	                    	$diffs[]="\"{$field}\" field is missing on live";
						}
	                	elseif(sha1($info[$tablename][$live_db]['fields'][$field]) != sha1($type)){
	                    	$diffs[]="\"{$field}\" field if different: \"{$type}\" != \"{$info[$tablename][$live_db]['fields'][$field]}\"";
						}
					}
					foreach($info[$tablename][$live_db]['fields'] as $field=>$type){
						if(!in_array($field,array_keys($info[$tablename][$stage_db]['fields']))){
	                   		$diffs[]="\"{$field}\" field is missing on stage";
						}
					}
				}
				$actions='';
            	echo '<tr>'."\n";
            	echo '	<td>'.$tablename.'</td>'."\n";
            	echo '	<td align="right">'.$info[$tablename][$stage_db]['cnt'].'</td>'."\n";
            	echo '	<td align="right">'.$info[$tablename][$live_db]['cnt'].'</td>'."\n";
            	echo '	<td align="right">'.$stage_field_count.'</td>'."\n";
            	echo '	<td align="right">'.$live_field_count.'</td>'."\n";
            	echo '	<td>'."\n";
            	if(count($diffs)){
					foreach($diffs as $diff){
						echo "<div>{$diff}</div>\n";
                    }
				}
				else{
                	//same fields - allow actions
                	echo '<a onclick="if(confirm(\'Table: '.$tablename.'\\r\\nAction: sync to live\\r\\nDescription: Drop all records on LIVE and move all records on STAGE to LIVE.\\r\\n\\r\\nAre you sure? This CANNOT be undone!\')){ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=datasync&func=push&tablename='.$tablename.'\');}return false;" href="#push" class="w_link w_lblue" style="margin-left:15px;"><img src="/wfiles/iconsets/16/synchronize_push.png" border="0" class="w_middle" />sync to live</a>'."\n";
                	echo '<a onclick="if(confirm(\'Table: '.$tablename.'\\r\\nAction: sync from live\\r\\nDescription: Drop all records on STAGE and move all records on LIVE to STAGE.\\r\\n\\r\\nAre you sure? This CANNOT be undone!\')){ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=datasync&func=pull&tablename='.$tablename.'\');}return false;" href="#pull" class="w_link w_lblue" style="margin-left:15px;"><img src="/wfiles/iconsets/16/synchronize_pull.png" border="0" class="w_middle" />sync from live</a>'."\n";
				}
				echo '	</td>'."\n";
            	echo '</tr>'."\n";
			}
			echo buildTableEnd();
			//echo printValue($info);
			break;
		case 'searchreplace':
			echo '<div class="w_lblue w_bold w_bigger">Search & Replace</div>'."\n";
			break;
		case 'files':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/file.gif" border="0"> File Manager</div>'."\n";
			echo fileManager();
			break;
		case 'explore':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/iconsets/32/files.png" border="0" class="w_middle"> File Explorer</div>'."\n";
			echo fileExplorer();
			break;
		case 'domain':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/website.gif" border="0"> Domain Search</div>'."\n";
			echo '<div>Enter one domain per line without the .com/net/org extension</div>'."\n";
			echo buildFormBegin('',array('-name'=>"domainsearch",'_menu'=>"domain"));
			echo '<textarea name="_domain_name" style="width:250px;height:100px;font-size:9pt;">'.$_REQUEST['_domain_name'].'</textarea><br />'."\n";
			echo buildFormSubmit("Check Domain Name Availability");
			echo buildFormEnd();
			echo buildOnLoad("document.domainsearch._domain_name.focus();");
			if(isset($_REQUEST['_domain_name']) && strlen($_REQUEST['_domain_name'])){
				$lines=preg_split('/[\r\n]+/',trim($_REQUEST['_domain_name']));
				foreach($lines as $line){
					$domain=str_replace(' ','',trim($line));
					$results = wsDomainSearch($domain);
					if(is_array($results)){
						$name=$results['name'];
						echo '<div class="w_bold w_big w_dblue">'.$results['name'].'</div>'."\n";
						echo '<div style="margin-left:25px;">'."\n";
						unset($results['name']);
						$regurl='http://www.dreamhost.com/r.cgi?210166/domreg.cgi?domain=';
						foreach($results as $key=>$val){
							if(preg_match('/available/i',$val)){
								$val .= ' <a class="w_link" href="'.$regurl.$name.'.'.$key.'"><img src="/wfiles/iconsets/16/cart.png" border="0"> Buy now at dreamhost.com</a>';
								}
							echo '<div><b>'.$key.':</b> '.$val.'</div>'."\n";
	                    	}
	                    echo '</div>'."\n";
						}
					}
            	}
			break;
		case 'dictionary':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/dictionary.gif" border="0"> Dictionary</div>'."\n";
			echo buildFormBegin('',array('-name'=>"domainsearch",'_menu'=>"dictionary"));
			echo '<input type="text" name="_search" style="width:150px;font-size:9pt;">'."\n";
			echo buildFormSubmit("Lookup");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				echo " Results for \"<b>{$_REQUEST['_search']}</b>\"";
            	}
			echo buildFormEnd();
			echo buildOnLoad("document.domainsearch._search.focus();");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				$results = wsDictionary($_REQUEST['_search']);
				if(is_array($results)){
					foreach($results as $result){
						echo '<div class="w_bold">'.$result['title'].'</div>'."\n";
						echo '<div class="w_small w_padleft">'.$result['answer'].'</div>'."\n";
                    	}
					}
				else{
					echo '<div class="w_red">'.$results.'</div>'."\n";
                	}
            	}
			break;
		case 'thesaurus':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/thesaurus.gif" border="0"> Thesaurus</div>'."\n";
			echo buildFormBegin('',array('-name'=>"domainsearch",'_menu'=>"thesaurus"));
			echo '<input type="text" name="_search" style="width:150px;font-size:9pt;">'."\n";
			echo buildFormSubmit("Lookup");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				echo " Results for \"<b>{$_REQUEST['_search']}</b>\"";
            	}
			echo buildFormEnd();
			echo buildOnLoad("document.domainsearch._search.focus();");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				$results = wsThesaurus($_REQUEST['_search']);
				if(is_array($results)){
					foreach($results as $result){
						echo '<div class="w_bold">'.$result['title'].'</div>'."\n";
						echo '<div class="w_small w_padleft">'.$result['answer'].'</div>'."\n";
                    	}
					}
				else{
					echo '<div class="w_red">'.$results.'</div>'."\n";
                	}
            	}
			break;
		case 'php':
			echo '<div class="w_lblue w_bold w_bigger"><img src="/wfiles/php.gif" border="0"> PHP Functions</div>'."\n";
			echo buildFormBegin('',array('-name'=>"domainsearch",'_menu'=>'php'));
			echo '<input type="text" name="_search" style="width:150px;font-size:9pt;">'."\n";
			echo buildFormSubmit("Lookup");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				echo " Results for \"<b>{$_REQUEST['_search']}</b>\"";
            	}
			echo buildFormEnd();
			echo buildOnLoad("document.domainsearch._search.focus();");
			if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
				$results = wsPHPDocumentation($_REQUEST['_search'],1);
				if(is_array($results)){
					if(isset($results['name']) && is_array($results['syntax'])){
						echo '<div class="w_bold">'.$results['name'].'</div>'."\n";
						foreach($results['syntax'] as $syntax){
							echo '<div class="w_small w_padleft">'.$syntax.'</div>'."\n";
	                    	}
                    	}
                    elseif(is_array($results['matches'])){
						foreach($results['matches'] as $match){
							echo '<div class="w_small"><a class="w_link" href="/'.$PAGE['name'].'?_menu=php&_search='.$match.'">'.$match.'</a></div>'."\n";
                        	}
						//echo printValue($results);
						}
					}
				else{
					echo $results;
                	}
            	}
			break;
    	}
   	}
echo '</div>'."\n";
//echo '<div style="display:none" id="null">'."\n";
//echo "ConfigSettings" . printValue($ConfigSettings);
//echo '</div>'."\n";
//$elapsed=round((microtime(true)-$_SERVER['_start_']),2);
//echo buildOnLoad("setText('admin_elapsed','{$elapsed} seconds');");
echo showWasqlErrors();
echo "</body>\n</html>";
exit;

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
	$rtn .= '	<div class="w_bold" style="border-bottom:1px solid #000;padding:10px;">Written ' . $files[0]['_edate_age_verbose'] . ' ago  <a href="#" class="w_link w_bold w_required" onclick="return ajaxGet(\'/php/admin.php\',\'session_errors\',\'_menu=clear_session_errors&t=10\');"><img src="/wfiles/iconsets/16/erase.png" border="0" width="16" height="16" class="w_middle"> Clear Error Log</a></div>'."\n";
	$rtn .= getFileContents($errfile);
	return $rtn;
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

//---------- begin function sqlPrompt ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function sqlPrompt(){
	$cmd=stripslashes($_REQUEST['sqlprompt_command']);
	$rtn='';
	$rtn .= '<table cellspacing="0" cellpadding="0" border="0" class="w_table" width="100%">'."\n";
	$rtn .= '<tr valign="top">'."\n";
	$rtn .= '<td nowrap>'."\n";
	$rtn .= '<div class="w_bold w_big" style="border-bottom:1px solid #000;padding-bottom:5px;"><img src="/wfiles/iconsets/16/table.png" border="0" class="w_middle"> Tables</div>'."\n";
	$rtn .= '<div style="height:500px;overflow:auto;padding-right:30px;">'."\n";
	$rtn .= expandAjaxTables();
	$rtn .= '</div>'."\n";
	$rtn .= '</td><td width="100%">'."\n";
	$rtn .= '<div class="w_bold w_big" style="border-bottom:1px solid #000;padding-bottom:5px;"><img src="/wfiles/iconsets/16/database_prompt.png" border="0" class="w_middle"> SQL Command Window: <span style="font-size:.8em;color:#7d7d7d;">(Using SQL Code editor. For help press F1)</span></div>'."\n";
	$rtn .= '<form method="POST" name="sqlprompt_form" action="/php/admin.php" class="w_form" onsubmit="ajaxSubmitForm(this,\'sqlprompt_results\');return false;">'."\n";
	$rtn .= '	<input type="hidden" name="_menu" value="sqlprompt">'."\n";
	$rtn .= '	<input type="hidden" name="_table_" value="_reports">'."\n";
	$rtn .= '<table cellspacing="0" cellpadding="0" border="0" class="w_table" width="100%">'."\n";
	$rtn .= '	<tr valign="top">'."\n";
	$rtn .= '		<td align="left" nowrap>'."\n";
	$rtn .= '			<textarea data-gutter="true" preview="Run SQL and View Results" ajaxid="sqlprompt_results" name="sqlprompt_command" id="sqlprompt_command" style="width:100%;height:250px;" data-behavior="sqleditor" focus="1">'.$cmd.'</textarea><br>'."\n";
	$rtn .= '		</td>'."\n";
	$rtn .= '	</tr>'."\n";
	$rtn .= '	<tr valign="top" id="adminmenu">'."\n";
	$rtn .= '		<td align="left" nowrap>'."\n";
	$rtn .= '			<input type="submit" name="sqlprompt" value="Run SQL (F5)" onclick="document.sqlprompt_form._menu.value=\'sqlprompt\';">'."\n";
	//next line inspired by Brady Barten
	$rtn .= '			<input type="submit" name="sqlprompt" value="Create Report" onclick="document.sqlprompt_form._menu.value=\'add\';">'."\n";
	$rtn .= '		</td>'."\n";
	$rtn .= '	</tr>'."\n";
	$rtn .= '</table>'."\n";
	$rtn .= '</form>'."\n";
	$rtn .= '<form method="POST" name="sqlprompt_form2" target="_export" action="/php/admin.php" class="w_form" style="position:relative;top:-32px;left:300px;" onsubmit="setText(this.sqlprompt_command,getText(\'sqlprompt_command\'));return submitForm(this);">'."\n";
	$rtn .= '	<input type="hidden" name="_menu" value="sqlprompt">'."\n";
	$rtn .= '	<textarea name="sqlprompt_command" style="width:0px;height:0px;"></textarea>'."\n";
	$rtn .= '	<input type="submit" name="sqlprompt" value="CSV Export">'."\n";
	$rtn .= '</form>'."\n";
	$rtn .= '<table cellspacing="0" cellpadding="0" border="0" class="w_table" width="100%">'."\n";
	//results window
	$rtn .= '	<tr valign="top">'."\n";
	$rtn .= '		<td align="left" nowrap>'."\n";
	$rtn .= '			<div style="padding:3px;font-size:10pt;">'."\n";
	$rtn .= '				<div id="sqlprompt_results">'."\n";
	$rtn .= '				</div>'."\n";
	$rtn .= '			</div>'."\n";
	$rtn .= '		</td>'."\n";
	$rtn .= '	</tr>'."\n";
	$rtn .= '</table>'."\n";
	$rtn .= '		'.buildOnLoad("document.sqlprompt_form.sqlprompt_command.focus();")."\n";
	$rtn .= '</td></tr></table>'."\n";
	return $rtn;
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
	$title='<img src="/wfiles/wasql_admin.png" border="0" class="w_middle"> Tables';
	$expand = '<table cellspacing="0" cellpadding="0" border="0">'."\n";
	foreach($tables as $table){
		if(!isWasqlTable($table)){continue;}
		$divid=$table .'_'. sha1($table);
		$expand .= '<tr><td nowrap>'.createExpandDiv($table,'','#0d0d7d',0,'/php/admin.php','_menu=tabledetails&table='.$table).'</td></tr>'."\n";;
	}
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);

	//build a section for non-wasql tables using createExpandDiv($title,$expand,'#0d0d7d',0);
	$title='<img src="/wfiles/iconsets/16/table.png" border="0" class="w_middle"> User Tables';
	$expand = '<table cellspacing="0" cellpadding="0" border="0">'."\n";
	foreach($tables as $table){
		if(isWasqlTable($table)){continue;}
		$divid=$table .'_'. sha1($table);
		$expand .= '<tr><td nowrap>'.createExpandDiv($table,'','#0d0d7d',0,'/php/admin.php','_menu=sqlprompt&table='.$table).'</td></tr>'."\n";;
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
	return '<img src="'.$src.'" border="0" class="w_middle">';
}

//---------- begin function adminMenu ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminMenu(){
	//exclude:true
	global $PAGE;
	global $USER;
	global $SETTINGS;
	//check to see if text and icons configurations are set
	global $ConfigSettings;
	$show_icons=$ConfigSettings['mainmenu_icons'];
	//echo printValueHidden($ConfigSettings);
	//echo "HERE{$show_icons},{$ConfigSettings['actionmenu_toggle']}<br>\n";
	$rtn='';
	//get wfiles path
	$progpath=dirname(__FILE__);
	$wfiles=preg_replace('/php$/i','',$progpath) . "wfiles";
	$wfiles=str_replace("\\","/",$wfiles);
	$tables=getDBTables();
	//Build Wasql Tables
 	$wtables='';
	$wtables .= '				<li class="dir"><a href="#" class="w_bold">';
	if($show_icons==1){$wtables .= '<img src="/wfiles/wasql_admin.png" border="0" class="w_middle">';}
	$wtables .= ' Tables';
	$wtables .= '</a>'."\n";
    $wtables .= '					<ul>'."\n";
	foreach($tables as $table){
		if(!preg_match('/^\_/',$table)){continue;}
		$lname=strtolower($table);
		$wtables .= '						<li class="dir"><a href="/php/admin.php?_menu=list&_table_='.$table.'">';
		if($show_icons==1){
			$img=getImageSrc($lname);
			if(strlen($img)){
				$wtables .= '<img src="'.$img.'" border="0" class="w_bottom"> ';
        	}
		}
		$wtables .= ' '.$table.'</a>'."\n";
		$wtables .= tableOptions($table,array('-format'=>"li"));
		$wtables .= '						</li>'."\n";
    	}
    $wtables .= '					</ul></li>'."\n";
    //admin info
    global $CONFIG;
    $rtn .= '<div id="admin_info" style="display:none">'."\n";
	$rtn .= '	<div style="width:400px;">'."\n";
	$rtn .= '		<div class="w_lblue w_bold w_big">Information Snapshot</div>'."\n";
	$rtn .= buildTableBegin(2,1);
	$info=array(
		'Server Host'	=> $_SERVER['HTTP_HOST'],
		'Database Host'	=> $CONFIG['dbhost'],
		'Database Name'	=> $CONFIG['dbname'],
		'waSQL Path'	=> getWasqlPath(),
		'Username'	=> $USER['username'],
		'PHP User'	=> get_current_user(),
		'Server'	=> php_uname()
	);
	foreach($info as $key=>$val){
    	$rtn .= '	<tr><th align="left" nowrap>'.$key.'</th><td>'.$val.'</td></tr>'."\n";
	}
	$rtn .= buildTableEnd();
	$rtn .= '	</div>'."\n";
	$rtn .= '</div>'."\n";
	//search on right
	$rtn .= '	<div style="float:right;padding:2px 10px 0 10px;">'."\n";
	$rtn .= '     		<div style="display:table-cell;padding-right:10px;">'.buildFormBegin('/php/admin.php',array('-name'=>'reference','_menu'=>'manual','_type'=>'user','-onsubmit'=>"return submitForm(this);"))."\n";
	$rtn .= '     			<input type="text" placeholder="help" style="margin:1px;padding:1px;font-size:12px;width:75px;" name="_search" _required="1" value="'.$_REQUEST['_search'].'" onFocus="this.select();">'."\n";
	$rtn .= '     			<input type="image" src="/wfiles/iconsets/16/help.png" value="Search" class="w_submit w_middle" />'."\n";
	$rtn .= '     		'.buildFormEnd()."</div>\n";
	//show wpass in menu?
	if($CONFIG['wpass']){$rtn .= wpassModule();}
	$rtn .= '	</div>'."\n";
	$rtn .= '	<div id="adminmenu" style="padding:6px 0 0 10px;">'."\n";
	$rtn .= '	<ul id="nav" class="dropdown dropdown-horizontal">'."\n";
	//logo
	$rtn .= '<li style="position:relative;padding-left:56px;">'."\n";
	$rtn .= '	<img data-tooltip="id:admin_info" src="/wfiles/wasql_admin.png" border="0" width="51" height="21" style="position:absolute;top:0px;left:0px;">';
	$rtn .= '</li>'."\n";
	//Database
	$rtn .= '		<li class="dir"><a href="#" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/database.png').' Database</a>'."\n";
	$rtn .= '			<ul>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=sqlprompt">'.adminMenuIcon('/wfiles/iconsets/16/database_prompt.png').' SQL Prompt</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=grep">'.adminMenuIcon('/wfiles/iconsets/16/database_search.png').' Search</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=backup" onclick="return confirm(\'This will backup the database. Click OK to continue?\');">'.adminMenuIcon('/wfiles/iconsets/16/database_backup.png').' Backup</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=backups">'.adminMenuIcon('/wfiles/iconsets/16/database_backup.png').' Backups</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=schema"><img src="/wfiles/schema.gif" border="0"> Schema</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=indexes"><img src="/wfiles/indexes.gif" border="0"> Indexes</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=optimize" onclick="return confirm(\'This will run mysqlcheck -o -v on the database to optimize the tables. Click OK to continue?\');">'.adminMenuIcon('/wfiles/iconsets/16/database_optimize.png').' Optimize</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=import">'.adminMenuIcon('/wfiles/iconsets/16/database_import.png').' Import</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=export">'.adminMenuIcon('/wfiles/iconsets/16/database_export.png').' Export</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=datasync">'.adminMenuIcon('/wfiles/iconsets/16/database_synchronize.png').' Synchronize Records</a></li>'."\n";
	$rtn .= '     		<li><a href="/php/admin.php?_menu=summary"><img src="/wfiles/summary.gif" border="0"> Table Status</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=charset">'.adminMenuIcon('/wfiles/charset.gif').' Character Sets</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=searchreplace" title="Search and Replace text in multiple records of a table"> Search&Replace</a></li>'."\n";
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
 //Tables
	/*
		look for tablegroup values in _tabledata  - group tables by group
	*/
	//get tableinfo from _tabledata
	$tableinfo=getDBRecords(array('-table'=>'_tabledata','-fields'=>'tablename,tablegroup,tabledesc','-index'=>"tablename"));
	$group_tables=array();
	$non_group_tables=array();
	$wasql_tables=array();
	$after=array();
	foreach($tables as $table){
		if(isWasqlTable($table)){$wasql_tables[]=$table;}
		elseif(isset($tableinfo[$table]) && strlen(trim($tableinfo[$table]['tablegroup']))){
			$group=trim($tableinfo[$table]['tablegroup']);
        	$group_tables[$group][]=$table;
		}
		else{$non_group_tables[]=$table;}
	}
	ksort($group_tables);
	sort($non_group_tables);
	$list_table_count=count($group_tables) + count($non_group_tables);
	$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=tables" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/table.png').' Tables</a>'."\n";
	$rtn .= '			<ul>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=tables">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Tables</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_new_">'.adminMenuIcon('/wfiles/iconsets/16/table_add.png').' Add New Table</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=addmultiple">'.adminMenuIcon('/wfiles/iconsets/16/table_add.png').' Add Multiple Tables</a><hr size="1"></li>'."\n";
	//show wasql tables here also
	$rtn .= $wtables;
	//now show groups
	foreach($group_tables as $group=>$gtables){
		sort($gtables);
		$rtn .= '				<li class="dir"><a href="#" onclick="return false;">'.adminMenuIcon('/wfiles/iconsets/16/group.png').' '.$group.' Tables</a><ul>'."\n";
		foreach($gtables as $table){
			if(preg_match('/^\_/',$table)){continue;}
			$lname=strtolower($table);
			unset($img);
			$rtn .= '				<li class="dir"><a href="/php/admin.php?_menu=list&_table_='.$table.'">';
			if($show_icons==1){
				$img=getImageSrc($lname);
				if(strlen($img)){
					$rtn .= '<img src="'.$img.'" border="0" class="w_bottom"> ';
		        }
			}
			$rtn .= ' '.$table.'</a>'."\n";
			$rtn .= tableOptions($table,array('-format'=>"li",'-group'=>$group));
			$rtn .= '				</li>'."\n";
	    }
	    $rtn .= '				</ul></li>'."\n";
	}
	//show non-group tables
	if(count($non_group_tables) < 100){
		foreach($non_group_tables as $table){
			if(preg_match('/^\_/',$table)){continue;}
			$lname=strtolower($table);
			unset($img);
			$rtn .= '				<li class="dir"><a href="/php/admin.php?_menu=list&_table_='.$table.'">';
			if($show_icons==1){
				$img=getImageSrc($lname);
				if(strlen($img)){
					$rtn .= '<img src="'.$img.'" border="0" class="w_bottom"> ';
		        }
			}
			$rtn .= ' '.$table.'</a>'."\n";
			$rtn .= tableOptions($table,array('-format'=>'li'));
			$rtn .= '				</li>'."\n";
	    }
	}
    $rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	unset($tables);
 	//Pages
	if(!isDBTable('_pages')){$ok=createWasqlTable('_pages');}
	$pages=getDBRecords(array('-table'=>'_pages','-limit'=>15,'-order'=>'_edate desc,_cdate desc'));
	$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=list&_table_=_pages" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/pages.png').' Pages</a>'."\n";
	$rtn .= '			<ul >'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_pages">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Pages</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=properties&_table_=_pages">'.adminMenuIcon('/wfiles/iconsets/16/properties.png').' Properties</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_pages">'.adminMenuIcon('/wfiles/add.gif').' Add New</a><hr size="1"></li>'."\n";
	foreach($pages as $page){
		$rtn .= '				<li><a href="/php/admin.php?_menu=edit&_table_=_pages&_id='.$page['_id'].'">';
		$lname=strtolower($page['name']);
		if($show_icons==1){
			$img=getImageSrc($lname);
			if(strlen($img)){
				$rtn .= '<img src="'.$img.'" border="0" class="w_bottom"> ';
        	}
		}
		$rtn .= ' '.$page['_id'].'. '.$page['name'].'</a></li>'."\n";
    	}
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	unset($pages);
	//Templates
	if(!isDBTable('_templates')){$ok=createWasqlTable('_templates');}
	$templates=getDBRecords(array('-table'=>'_templates','-limit'=>15,'-order'=>"_edate desc,_cdate desc"));
	$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=list&_table_=_templates" class="w_topmenu">'.adminMenuIcon('/wfiles/_templates.gif').' Templates</a>'."\n";
	$rtn .= '			<ul>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_templates">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Templates</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=properties&_table_=_templates">'.adminMenuIcon('/wfiles/iconsets/16/properties.png').' Properties</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_templates">'.adminMenuIcon('/wfiles/add.gif').' Add New</a><hr size="1"></li>'."\n";
	if(is_array($templates)){
		foreach($templates as $template){
			$rtn .= '				<li><a href="/php/admin.php?_menu=edit&_table_=_templates&_id='.$template['_id'].'">';
			$lname=strtolower($template['name']);
			if($show_icons==1){
				$img=getImageSrc($lname);

				if(strlen($img)){
					$rtn .= '<img src="'.$img.'" border="0" class="w_bottom"> ';
	        	}
			}
			$rtn .= ' '.$template['_id'].'. '.$template['name'].'</a></li>'."\n";
	    	}
		}
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	unset($templates);
	//Reports
	if(!isDBTable('_reports')){$ok=createWasqlTable('_reports');}
	$reports=getDBRecords(array('-table'=>'_reports','active'=>1,'menu'=>'_reports','-limit'=>15,'-order'=>'name'));
	$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=list&_table_=_reports" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/reports.png').' Reports</a>'."\n";
	$rtn .= '			<ul >'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_reports">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Reports</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=properties&_table_=_reports">'.adminMenuIcon('/wfiles/iconsets/16/properties.png').' Properties</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_reports">'.adminMenuIcon('/wfiles/add.gif').' Add New</a><hr size="1"></li>'."\n";
	if(is_array($reports)){
		foreach($reports as $report){
			$rtn .= '				<li><a href="/php/admin.php?_menu=reports&_id='.$report['_id'].'">';
			$rtn .= ' '.$report['_id'].'. '.$report['name'].'</a></li>'."\n";
	    }
	}
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	unset($reports);
	//Users
	if(!isDBTable('_users')){$ok=createWasqlTable('_users');}
	$users=getDBRecords(array('-table'=>'_users','-limit'=>15,'-where'=>'_adate is not null','-order'=>"utype,_adate desc"));
	$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=list&_table_=_users" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/users.png').' Users</a>'."\n";
	$rtn .= '			<ul>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_users">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Users</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=properties&_table_=_users">'.adminMenuIcon('/wfiles/iconsets/16/properties.png').' Properties</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=user_report">'.adminMenuIcon('/wfiles/iconsets/16/charts.png').' User Report</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_users">'.adminMenuIcon('/wfiles/iconsets/16/user_add.png').' Add New</a><hr size="1"></li>'."\n";
	foreach($users as $cuser){
		if(isAdmin() && $USER['_id'] != $cuser['_id']){$rtn .= '				<li class="dir">';}
		else{$rtn .= '				<li>';}
		$rtn .= '<a href="/php/admin.php?_menu=edit&_id='.$cuser['_id'].'&_table_=_users">';
		//determine what image
		$info=getUserInfo($cuser);
		if($show_icons==1){$rtn .= '<img src="'.$info['icon'].'" title="'.$info['status'].'" border="0"> ';}
		$rtn .= ' '.$cuser['_id'].'. '.$cuser['username'].'</a>';
		if(isAdmin() && $USER['_id'] != $cuser['_id']){$rtn .= '				<ul><li><a href="/php/admin.php?_menu=list&_table_=_users&_su_='.$cuser['_id'].'">Switch User (SU)</a></li></ul>';}
		$rtn .= '</li>'."\n";
    	}
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	unset($users);
	//Cron Jobs
	if(isset($SETTINGS['wasql_crons']) && $SETTINGS['wasql_crons']==1){
		if(!isDBTable('_cron')){$ok=createWasqlTable('_cron');}
		$crons=getDBRecords(array('-table'=>"_cron",'-limit'=>10,'-order'=>"run_date desc"));
		$rtn .= '		<li class="dir"><a href="/php/admin.php?_menu=list&_table_=_cron" class="w_topmenu">'.adminMenuIcon('/wfiles/_cron.png').' Crons</a>'."\n";
		$rtn .= '			<ul>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_cron">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' List Crons</a></li>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_cronlog">'.adminMenuIcon('/wfiles/_cron.png').' List Logs</a></li>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=add&_table_=_cron">'.adminMenuIcon('/wfiles/add.gif').' Add New</a></li>'."\n";
		if(is_array($crons)){
			if(count($crons) > 15){
				$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_cron">'.adminMenuIcon('/wfiles/iconsets/16/list.png').' Show All</a></li>'."\n";
				}
			foreach($crons as $cron){
				$rtn .= '				<li><a href="/php/admin.php?_menu=edit&_table_=_cron&_id='.$cron['_id'].'">';
				$lname=strtolower($cron['name']);
				$rtn .= ' '.$cron['_id'].'. '.$cron['name'].'</a></li>'."\n";
		    	}
			}
		$rtn .= '			</ul>'."\n";
		$rtn .= '		</li>'."\n";
		unset($crons);
		}
	//Queries
	if(isset($SETTINGS['wasql_queries']) && $SETTINGS['wasql_queries']==1){
		if(!isDBTable('_queries')){$ok=createWasqlTable('_queries');}
		$rtn .= '		<li><a href="/php/admin.php?_menu=list&_table_=_queries" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/queries.png').' Queries</a></li>'."\n";
		}
	//Access
	if(isset($SETTINGS['wasql_access']) && $SETTINGS['wasql_access']==1){
		if(!isDBTable('_queries')){$ok=createWasqlTable('_queries');}
		$rtn .= '		<li class="dir"><a href="#access" onclick="return false;" class="w_topmenu">'.adminMenuIcon('/wfiles/_access.gif').' Access</a>'."\n";
		$rtn .= '			<ul>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_access_summary" class="w_topmenu">'.adminMenuIcon('/wfiles/_access.gif').' Summary</a></li>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_access" class="w_topmenu">'.adminMenuIcon('/wfiles/_access_summary.gif').' Details</a></li>'."\n";
		$rtn .= '			</ul>'."\n";
		$rtn .= '		</li>'."\n";
		}
	//synchronize
	if(isset($SETTINGS['wasql_synchronize']) && $SETTINGS['wasql_synchronize']==1){
		$rtn .= '		<li>'."\n";
		$rtn .= '			<a href="/php/admin.php?_menu=synchronize" class="w_topmenu">'.adminMenuIcon('/wfiles/iconsets/16/synchronize.png').' Synchronize</a>'."\n";
		$rtn .= '			<ul>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=_synchronize">'.adminMenuIcon('/wfiles/iconsets/16/synchronize.png').' Pending Changes</a></li>'."\n";
		$rtn .= '				<li><a href="/php/admin.php?_menu=list&_table_=_synchronize">'.adminMenuIcon('/wfiles/iconsets/16/synchronize.png').' Sync History</a></li>'."\n";
		$rtn .= '			</ul>'."\n";
		$rtn .= '		</li>'."\n";
		}
	$rtn .= '	</ul>'."\n";
	$rtn .= '	<ul id="nav" class="dropdown dropdown-horizontal rightside" style="float:right;">'."\n";
	//My Profile
	$rtn .= '		<li class="dir"><a href="#access" onclick="return false;" class="w_topmenu">'.adminMenuIcon('/wfiles/_profile.gif').' '.$USER['username'].'</a>'."\n";
	$rtn .= '			<ul style="width:110px;">'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=profile" class="w_topmenu">'.adminMenuIcon('/wfiles/_profile.gif').' My Profile</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_logout=1">'.adminMenuIcon('/wfiles/logoff.gif').' Log Off</a></li>'."\n";
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	//WaSQL
	$rtn .= '		<li class="dir"><a href="#">'.adminMenuIcon('/wfiles/wasql_admin.png').'</a>'."\n";
	$rtn .= '        	<ul>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=settings">'.adminMenuIcon('/wfiles/iconsets/16/settings.png').' Settings</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=manual">'.adminMenuIcon('/wfiles/iconsets/16/help.png').' Documentation</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=about">'.adminMenuIcon('/wfiles/iconsets/16/info.png').' About</a><hr size="1"></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=postedit">'.adminMenuIcon('/wfiles/postedit.gif').' PostEdit Manager</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=contentmanager">'.adminMenuIcon('/wfiles/iconsets/16/contentmanager.png').' Content Manager</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=files">'.adminMenuIcon('/wfiles/file.gif').' File Manager</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=sandbox">'.adminMenuIcon('/wfiles/iconsets/16/php.png').' PHP Sandbox</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=editor" class="w_bold">'.adminMenuIcon('/wfiles/wasql_admin.png').' Inline Editor</a><hr size="1"></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=rebuild">'.adminMenuIcon('/wfiles/rebuild.png').' Rebuild waSQL Tables</a></li><li></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=stats">'.adminMenuIcon('/wfiles/stats.gif').' Usage Stats</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=email"><img src="'.getImageSrc('email').'" border="0"> Send Email</a></li>'."\n";
    $rtn .= '     			<li><a href="/php/admin.php?_menu=iconsets">'.adminMenuIcon('/wfiles/iconsets/16/icon.png').' List IconSets</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=env">'.adminMenuIcon('/wfiles/server.png').' Server Vars</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=system">'.adminMenuIcon('/wfiles/iconsets/16/server.png').' System Info</a></li>'."\n";
	$rtn .= '     			<li><a href="/php/admin.php?_menu=entities">'.adminMenuIcon('/wfiles/entity.gif').' HTML Entities</a><hr size="1"></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_menu=errors">'.adminMenuIcon('/wfiles/iconsets/16/warning.png').' Session Errors</a></li>'."\n";
	$rtn .= '				<li><a href="/php/admin.php?_menu=svn">'.adminMenuIcon('/wfiles/iconsets/16/subversion.png').' WaSQL Update</a></li>'."\n";
	$rtn .= '     			<li><a href="http://www.wasql.com">'.adminMenuIcon('/wfiles/website.gif').' Goto WaSQL.com</a></li>'."\n";
	$rtn .= '     			<li><a href="#" onclick="return iframePopup(\'http://www.wasql.com/bugs?email='.encodeURL($USER['email']).'&php='.getPHPVersion().'&wasql='.wasqlVersion().'&dbtype='.databaseType().'&http_host='.$_SERVER['HTTP_HOST'].'\',{title:\'<img src=/wfiles/iconsets/16/bug.png border=0 class=w_middle /> WaSQL Bug Form\',id:\'bugsform\',drag:1,center:1,iwidth:550,iheight:400});">'.adminMenuIcon('/wfiles/iconsets/16/bug.png').' Report a Bug</a></li>'."\n";
	//$rtn .= '				<li><a href="/php/admin.php?_logout=1"><img src="/wfiles/logoff.gif" border="0"> Log Off</a></li>'."\n";
	$rtn .= '			</ul>'."\n";
	$rtn .= '		</li>'."\n";
	//Settings Link
	//$rtn .= '     	<li style="margin-left:15px;"><a href="/php/admin.php?_menu=admin_settings" onclick="return ajaxGet(\'/php/admin.php\',\'centerpop\',\'_menu=admin_settings\');"><img title="settings" src="/wfiles/iconsets/16/settings.png" border="0"></a></li>'."\n";

	//end menu
	$rtn .= '	</ul>'."\n";
	$rtn .= '	<br clear="both" />'."\n";
	$rtn .= '	</div>'."\n";
	return $rtn;
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
	$rtn .= '	<div id="adminmenu">'."\n";
	$rtn .= '	<ul id="nav" class="dropdown dropdown-horizontal">'."\n";
	$rtn .= '		<li><a href="#" onclick="return false;">'.adminMenuIcon('/wfiles/wasql_admin.png').' Admin Login - '.$_SERVER['HTTP_HOST'].'</a></li>'."\n";
	//end menu
	$rtn .= '	</ul>'."\n";
	$rtn .= '	<br clear="both" />'."\n";
	$rtn .= '	</div>'."\n";
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
	if(!isset($params['-options'])){$params['-options']='drop,rebuild,truncate,backup,grep,model,indexes,properties,list,add';}
	if(!is_array($params['-options'])){$params['-options']=preg_split('/[\,\:]+/',$params['-options']);}
	global $PAGE;
	$tableoptions=array(
		'drop'		=> array("Delete Table",'x'),
		'truncate'	=> array("Truncate Table",'erase'),
		'indexes'	=> array("Show Indexes",'table_index'),
		'backup'	=> array("Backup Table",'backup'),
		'model'		=> array("Model",'models'),
//		'schema'	=> array("Schema",'table_truncate'),
		'properties'=> array("Properties",'properties'),
		'list'		=> array("List Records",'list'),
		'grep'		=> array("Search Records",'grep'),
		'add'		=> array("Add New Record",'add')
		);
	if(in_array($table,$wtables) && !in_array($table,array('_pages','_fielddata','_tabledata','_users','_templates'))){
    	$tableoptions['rebuild']=array('Rebuild','rebuild');
	}
	if(isWasqlTable($table)){
    	unset($tableoptions['drop']);
	}
	//check for _models for this table
	$model=getDBRecord(array('-table'=>"_models",'name'=>$table));
	//if($table=='states'){echo "HERE".printValue($tableoptions);exit;}
	$rtn='';
	switch(strtolower($params['-format'])){
		case 'li':
			$params['-options']=array_reverse($params['-options']);
			$rtn .= '					<ul>'."\n";
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$img=$tableoptions[$option][1];
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
				$rtn .= '>';
				$rtn .= '<img alt="'.$title.'" src="/wfiles/iconsets/16/'.$img.'.png" border="0" class="w_middle" style="margin:0 3px 0 3px;"> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</a></li>'."\n";
		        }
		    //group?
		    if(isset($_SERVER['waSQL_Tablegroups']) && is_array($_SERVER['waSQL_Tablegroups'])){
	        	$recs=$_SERVER['waSQL_Tablegroups'];
			}
		    else{
			    $query="select distinct(tablegroup) from _tabledata";
			    if(strlen($params['-group'])){$query .= " where tablegroup != '{$params['-group']}'";}
				$query .= " order by tablegroup";
		        $recs=getDBRecords(array('-query'=>$query));
				$_SERVER['waSQL_Tablegroups']=$recs;
		        }
		    if(is_array($recs) && count($recs)){
	        	$menu=$_REQUEST['_menu'];
	        	$rtn .= '						<li class="dir"><a class="w_link" href="#" onclick="return false;">'.adminMenuIcon('/wfiles/iconsets/16/group.png').' Group with</a>'."\n";
	        	$rtn .= '							<ul>'."\n";
	        	foreach($recs as $rec){
	            	$rtn .= '								<li><a class="w_link" href="/php/admin.php?_groupwith='.$rec['tablegroup'].'&_menu='.$menu.'&_table_='.$table.'">'.$rec['tablegroup'].'</a></li>'."\n";
				}
	        	$rtn .= '							</ul>'."\n";
	        	$rtn .= '						</li>'."\n";
			}
		    $rtn .= '					</ul>'."\n";
		    break;
		case 'table':
			$rtn .= '<table class="actionmenu" cellspacing="0" cellpadding="0" border="0"><tr>'."\n";
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$img=$tableoptions[$option][1];
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
				$rtn .= '	<td><a data-tooltip_position="bottom" data-tooltip="'.$title.'" class="w_link '.$class.'" href="'.$href.'"';
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
					$onclick="return ajaxGet('/php/admin.php','centerpop','_menu=add&_table_={$table}');";
					$rtn .= ' onclick="'.$onclick.'"';
		            }
				$rtn .= '>';
				$rtn .= '<img alt="'.$title.'" src="/wfiles/iconsets/16/'.$img.'.png" border="0" class="w_middle" style="margin:0 3px 0 3px;"> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</a></td>'."\n";
		        }
		    $rtn .= '</tr></table>'."\n";
			break;
		default:
			//none
			foreach($params['-options'] as $option){
				if(!isset($tableoptions[$option])){continue;}
				$title=$tableoptions[$option][0];
				$img=$tableoptions[$option][1];
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
		            }
				$rtn .= '>';
				$rtn .= '<img alt="'.$title.'" src="/wfiles/iconsets/16/'.$img.'.png" border="0" class="w_middle" style="margin:0 3px 0 3px;"> ';
				if(!isset($params['-notext'])){
					$rtn .= $title;
				}
				$rtn .= '</a> '."\n";
		        }
			break;
		}
	unset($tableoptions);
	return $rtn;
	}


//---------- begin function adminSettings ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminSettings(){
	//exclude:true
	/*
	Main Menu Settings
		toggle icons, toggle text, toggle organize tables by first letter
		top fade color, bottom fade color, bottom border color
		text color, hover background color, hover text color
		fixed width, Full Width
		logo, logo url
		reset default menu settings
	Action Menu Settings
		Toggle icons, toggle text
		reset default menu settings
	*/
	global $ConfigSettings;
	$old_request=$_REQUEST;
	$_REQUEST=$ConfigSettings;
	$setfield='_admin_settings_';
	$rtn='';
	$rtn .= buildFormBegin('',array('-name'=>"adminSettingsForm",'_action'=>"settings",'_menu'=>"admin_settings",'_userid'=>"-1",'_setfield'=>$setfield,'-multipart'=>1,'-onsubmit'=>'return submitForm(this);'));
	$rtn .= '<div class="w_bold w_bigger w_lblue w_pad"><img src="/wfiles/iconsets/16/settings.png" border="0"> Admin Settings</div>'."\n";
	$rtn .= '<div id="adminsettings" style="height:450px;width:450px;overflow:scroll;padding-right:20px;">'."\n";
	$rtn .= '	<div id="adminmenu" class="w_bold"><img src="/wfiles/iconsets/16/settings.png" border="0"> Main Menu</div>'."\n";
	//$rtn .= '	<div class="mainmenu_fade_color_bot mainmenu_text_color w_big" style="padding-left:3px;">Icons and Text Settings</div>'."\n";
	//icons, text, tablesbyletter
	$tvals="ICO";
	$dvals="Show Icons";
	$rtn .= '	<div>'. buildFormField('_pages','title',array('name'=>"mainmenu_toggle",'inputtype'=>'checkbox','tvals'=>$tvals,'dvals'=>$dvals)).'</div>'."\n";
	$rtn .= '		<table cellspacing="0" cellpadding="2" border="0" width="100%">'."\n";
	//top fade color, bottom fade color, bottom border color
	$rtn .= '			<tr valign="bottom"><td>Top Fade</td><td>Bottom Fade</td><td>Bottom Border</td></tr>'."\n";
	$rtn .= '			<tr valign="top">'."\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"mainmenu_fade_color_top",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"mainmenu_fade_color_bot",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"mainmenu_border_color_bot",'inputtype'=>'color'))."</td>\n";
	$rtn .= '			</tr>'."\n";
	//text color, hover background color, hover text color
	$rtn .= '			<tr valign="bottom"><td>Text Color</td><td>Hover Background</td><td></td></tr>'."\n";
	$rtn .= '			<tr valign="top">'."\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"mainmenu_text_color",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"mainmenu_hover_background",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td></td>'."\n";
	$rtn .= '			</tr>'."\n";
	$rtn .= '		</table>'."\n";
	//reset
	$rtn .= '	<div style="padding-left:3px;"><a class="w_link w_red" href="#" onclick="return ajaxGet(\'/php/css.php\',\'null\',\'default=mainmenu\');"><img src="/wfiles/iconsets/16/reset.png" border="0"> Reset to default settings</a></div>'."\n";

	//Action Menu
	$rtn .= '	<div id="adminmenu" class="w_bold"><img src="/wfiles/iconsets/16/settings.png" border="0"> Action Menu</div>'."\n";
	//icons, text, tablesbyletter
	$tvals="ICO\r\nTXT";
	$dvals="Show Icons\r\nShow Text";
	$rtn .= '	<div>'. buildFormField('_pages','title',array('name'=>"actionmenu_toggle",'inputtype'=>'checkbox','tvals'=>$tvals,'dvals'=>$dvals)).'</div>'."\n";
	$rtn .= '		<table cellspacing="0" cellpadding="2" border="0" width="100%">'."\n";
	//text color, hover background color, hover text color
	$rtn .= '			<tr valign="bottom"><td>Text Color</td><td>Hover Background</td><td></td></tr>'."\n";
	$rtn .= '			<tr valign="top">'."\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"actionmenu_text_color",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"actionmenu_hover_background",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td></td>'."\n";
	$rtn .= '			</tr>'."\n";
	$rtn .= '		</table>'."\n";
	//reset
	//reset
	$rtn .= '	<div style="padding-left:3px;"><a class="w_link w_red" href="#" onclick="return ajaxGet(\'/php/css.php\',\'null\',\'default=actionmenu\');"><img src="/wfiles/iconsets/16/reset.png" border="0"> Reset to default settings</a></div>'."\n";

	//Content Settings
	$rtn .= '	<div id="adminmenu" class="w_bold"><img src="/wfiles/iconsets/16/settings.png" border="0"> Content Position</div>'."\n";
	//Fixed Width Center or Full Width Left
	$tvals="fixed\r\nfull";
	$dvals="Fixed Width Center\r\nFull Width Left";
	$rtn .= '	<div>'. buildFormField('_pages','title',array('name'=>"content_position",'inputtype'=>"radio",'tvals'=>$tvals,'dvals'=>$dvals)).'</div>'."\n";
	//reset
	$rtn .= '	<div style="padding-left:3px;"><a class="w_link w_red" href="#" onclick="return ajaxGet(\'/php/css.php\',\'null\',\'default=content\');"><img src="/wfiles/iconsets/16/reset.png" border="0"> Reset to default settings</a></div>'."\n";

	//Table settings
	$rtn .= '	<div id="adminmenu" class="w_bold"><img src="/wfiles/iconsets/16/settings.png" border="0"> Table Colors and Shading</div>'."\n";
	//top fade color, bottom fade color, bottom border color
	$rtn .= '		<table cellspacing="0" cellpadding="2" border="0" width="100%">'."\n";
	$rtn .= '			<tr valign="bottom"><td>TH Text Color</td><td>TH Background</td><td>Even Row Color</td></tr>'."\n";
	$rtn .= '			<tr valign="top">'."\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"table_header_text",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"table_header_background",'inputtype'=>'color'))."</td>\n";
	$rtn .= '				<td>'. buildFormField('_pages','title',array('name'=>"table_even_background",'inputtype'=>'color'))."</td>\n";
	$rtn .= '			</tr>'."\n";
	$rtn .= '		</table>'."\n";
	//reset
	//reset
	$rtn .= '	<div style="padding-left:3px;"><a class="w_link w_red" href="#" onclick="return ajaxGet(\'/php/css.php\',\'null\',\'default=table\');"><img src="/wfiles/iconsets/16/reset.png" border="0"> Reset to default settings</a></div>'."\n";

	$rtn .= '</div>'."\n";
	$rtn .= '<div align="right" class="w_pad">'.buildFormSubmit('Save Changes').'</div>'."\n";
	$rtn .= buildFormEnd();
	$_REQUEST=$old_request;
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
			echo '<div class="w_red"><img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle" /> '.$table.' has no sync fields defined.</div>'."\n";
			continue;
		}
		//determine the field to use as a name field.
		unset($namefield);
		foreach($checkfields as $checkfield){
			if(in_array($checkfield,$fields)){$namefield=$checkfield;break;}
		}
		if(!isset($namefield)){
			echo '<div class="w_red"><img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle" />'."\n";
			echo "	No namefield specified in stage for {$table}:".implode(', ',$fields)."<br />\n";
			echo "	One of the following fields must be in the table and marked as syncronize in the meta data:".implode(', ',$checkfields)."<br />\n";
			echo '</div>'."\n";
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
			echo '<div class="w_red"><img src="/wfiles/iconsets/16/alert.png" border="0" class="w_middle" />'."\n";
			echo "	No namefield specified in stage for {$table}:".implode(', ',$fields)."<br />\n";
			echo "	One of the following fields must be in the table and marked as syncronize in the meta data:".implode(', ',$checkfields)."<br />\n";
			echo '</div>'."\n";
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
			if(!isset($info['recs_live'][$table][$index])){
            	//new record on stage
            	$stage_userid=$stagerec['_euser'];
            	if(!isNum($stagerec['_euser'])){$stage_userid=$stagerec['_cuser'];}
				if(isset($info['users_stage'][$stage_userid]['username'])){$stage_user=$info['users_stage'][$stage_userid]['username'];}
            	else{$stage_user='Unknown';}
				$actions=' <a href="#" onclick="return ajaxGet(\'\',\'centerpop\',\'_menu=synchronize&sync_action=sync&sync_items[]='.$index.'--'.$table.'\');"><img src="/wfiles/iconsets/16/synchronize_push.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Push change Live"></a>';
            	$actions.=' <a href="/php/admin.php?_menu=edit&_table_='.$table.'&_id='.$index.'"><img src="/wfiles/iconsets/16/edit.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Edit Record"></a>';
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
            	$actions='<label style="cursor:pointer" for="'.$for.'" onclick="ajaxGet(\'\',\'centerpop\',\'_menu=synchronize&sync_action=diff_table&diff_table='.$table.'&diff_id='.$index.'\');"><img src="/wfiles/iconsets/16/synchronize_diff.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Compare Differences"></label>';
            	$actions.=' <a href="#" onclick="return ajaxGet(\'\',\'centerpop\',\'_menu=synchronize&sync_action=sync&sync_items[]='.$index.'--'.$table.'\');"><img src="/wfiles/iconsets/16/synchronize_push.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Push change Live"></a>';
            	$actions.=' <a href="/php/admin.php?_menu=edit&_table_='.$table.'&_id='.$index.'"><img src="/wfiles/iconsets/16/edit.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Edit Record"></a>';
				//check to see if live is newer - if so place an alert icon
            	$date_live_val=setValue(array($info['recs_live'][$table][$index]['_edate'],$info['recs_live'][$table][$index]['_cdate'],'unknown'));
            	$date_stage_val=setValue(array($stagerec['_edate'],$stagerec['_cdate'],'unknown'));
            	if(!strlen($date_live_val) || strtotime($date_live_val) > strtotime($date_stage_val)){
					$date_live_val .= ' <img src="/wfiles/iconsets/16/warning.png" border="0" width="16" height="16" class="w_middle" title="Warning: Live Record is NEWER!"> ';
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
			$actions=' <a href="/php/admin.php?_menu=properties&_table_='.$table.'"><img src="/wfiles/iconsets/16/properties.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Properties"></a>';
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
			$actions='<a href="#" onclick="ajaxGet(\'\',\'centerpop\',\'_menu=synchronize&sync_action=diff_schema&diff_table='.$table.'\');return false;"><img src="/wfiles/iconsets/16/synchronize_diff.png" border="0" title="diff"></a>';
			$actions .=' <a href="/php/admin.php?_menu=properties&_table_='.$table.'"><img src="/wfiles/iconsets/16/properties.png" border="0" style="margin:0 2px 0 2px;padding:0 2px 0 2px;" title="Properties"></a>';
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
	//display change tabs by table
	$rtn .= '	<div  align="left">'."\n";
	$rtn .= '	<table id="syncTableMain" cellspacing="0" cellpadding="1" border="0">'."\n";
	$rtn .= '		<tr valign="bottom">'."\n";
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
        $syncTableHref='sync'.$table.'href';
        $img='';
		$src=getImageSrc($table);
		if(strlen($src)){$img='<img src="'.$src.'" border="0" class="w_bottom"> ';}
        $rtn .= '		<td id="'.$table.'"><div id="'.$syncTableTab.'" onclick="syncTableClick(\''.$syncTableHref.'\');return false;" class="table_header_background w_roundsmall_topleft w_roundsmall_topright w_arial w_small" style="cursor:pointer;padding:3px 2px 2px 2px;">'."\n";
        $rtn .= '			<a id="'.$syncTableHref.'" class="table_header_text w_link w_bold" style="text-decoration:none;padding:1px 2px 1px 2px;" href="'.$table.'" onclick="return false;"><sup title="Your change count">'.$change_user_count.'</sup> '.$img.$table.' <sup title="Total count">'.$change_count.'</sup></a>'."\n";
        $rtn .= '		</div></td>'."\n";
	}
	$rtn .= '	</tr></table>'."\n";
	//build each table's changes in a hidden div
	$formname='syncform';
	$rtn .=  buildFormBegin('',array('_menu'=>"synchronize",'-name'=>$formname,'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"));
	$rtn .= '	<input type="hidden" name="sync_action" value="">'."\n";
	$rtn .= '	<div id="syncDiv"></div>'."\n";
	unset($first_table);
	$rtn .= '	<div id="syncTableHidden" style="display:none;">'."\n";
	foreach($changes as $table=>$recs){
		if(!isset($first_table)){$first_table=$table;}
		$syncTableDiv='sync'.$table.'div';
		$syncTableTab='sync'.$table.'tab';
		$rtn .= '		<div table="'.$table.'" id="'.$syncTableDiv.'">'."\n";
		$rtn .=  listDBRecords(array(
			'-list'			=> $changes[$table],'_id_align'=>"left",
			'-tableclass'	=> "w_table sortable",
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
		$rtn .= '		</div>'."\n";
	}
	$rtn .= '	</div>'."\n";
	$rtn .= '	</div>'."\n";
	//show the first table or the one they sorted by
	$showtable=setValue(array($_REQUEST['synctab'],$first_table));
	$syncTableDiv='sync'.$showtable.'div';
	$syncTableHref='sync'.$showtable.'href';
	$rtn .=  buildOnLoad("syncTableClick('{$syncTableHref}');");
	//show sync and cancel buttons
	$rtn .= '<br clear="both" />'."\n";
	$rtn .= '<input style="padding-left:23px;background:url(/wfiles/iconsets/16/synchronize_push.png);background-repeat:no-repeat;background-position: 5px;" type="submit" class="w_bold w_round" onclick="document.'.$formname.'.sync_action.value=\'sync\';ajaxSubmitForm(document.'.$formname.',\'centerpop\');return false;" value="Push Changes Live">'."\n";
	$rtn .= '<input style="padding-left:23px;background:url(/wfiles/iconsets/16/synchronize_pull.png);background-repeat:no-repeat;background-position: 5px;" type="submit" class="w_red w_bold w_round" onclick="if(!confirm(\'Cancel selected changes on stage and restore back to live?\')){return false;}document.'.$formname.'.sync_action.value=\'cancel\';ajaxSubmitForm(document.'.$formname.',\'centerpop\');return false;" value="Restore from Live">'."\n";
	$rtn .=  buildFormEnd();
	return $rtn;
}

//---------- begin function adminCreateNewTable ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminCreateNewTable($table,$fieldstr){
	//exclude:true
	if(isDBTable($table)){return "{$table} already exists";}
	$lines=preg_split('/[\r\n]+/',trim($fieldstr));
	if(!count($lines)){return "no fields defined for {$table}";}
	//common fields to all wasql tables
	$cfields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "int NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "int NULL",
		);
	$fields=array();
	$errors=array();
	foreach($lines as $line){
		if(!strlen(trim($line))){continue;}
		list($name,$type)=preg_split('/[\s\t]+/',$line,2);
		if(!strlen($type)){
			$errors[]="Missing field type for {$line}";
			}
        elseif(!strlen($name)){
			$errors[]="Invalid line: {$line}";
			}
		else{$fields[$name]=$type;}
        }
    if(count($errors)){
		return "Field errors for {$table}:".printValue($errors);
		}
	//add common fields
	foreach($cfields as $key=>$val){$fields[$key]=$val;}
    $ok = createDBTable($table,$fields);
    return $ok;
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
    	case 'xml':$behavior='xmleditor';break;
    	default:$behavior='phpeditor';break;
	}
	$content=getFileContents($file);
	$rtn = '';
	$rtn .= '<div style="position:relative;">'."\n";
	$rtn .= buildFormBegin($_SERVER['PHP_SELF'],array('emenu'=>'file','_menu'=>"editor",'file'=>$file,'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"));
	$rtn .= '<table border="0" cellpadding="4" cellspacing="0"><tr><td>'."\n";
	$rtn .= '	<div class="w_bold w_bigger w_dblue">Editing File: '.$file.'</div>'."\n";
	$rtn .= '</td><td>'.buildFormSubmit('Save').'</td></tr></table>'."\n";
	$rtn .= '<div style="border:1px inset #000;width:800px;">'."\n";
	$rtn .= '<textarea name="file_content" data-behavior="'.$behavior.'" style="width:800px;height:400px;">'."\n";
	$rtn .= encodeHtml($content);
	$rtn .= '</textarea>'."\n";
	$rtn .= '</div>'."\n";
	$rtn .= buildFormSubmit('Save');
	$rtn .= buildFormEnd();
	$rtn .= '</div>'."\n";
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
	$rtn .= '<div style="position:relative;">'."\n";
	$rtn .= '<table border="0" cellpadding="4" cellspacing="0"><tr><td><div class="w_bold w_bigger w_dblue">New '.strtoupper($filetype).' File</div></td><td><div id="w_editor_status"></div></td></tr></table>'."\n";
	$rtn .= buildFormBegin($_SERVER['PHP_SELF'],array('-name'=>'addedit','emenu'=>'file','filetype'=>$filetype,'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"));
	$rtn .= '<div style="margin-bottom:5px;">FileName: <input type="text" name="filename" required="1" style="width:400px;">.'.$filetype.'</div>'."\n";
	$rtn .= '<div style="border:1px inset #000;width:800px;">'."\n";
	$rtn .= '<textarea name="file_content" data-behavior="'.$behavior.'" style="width:800px;height:400px;">'."\n";
	$rtn .= '</textarea>'."\n";
	$rtn .= '</div>'."\n";
	$rtn .= buildFormSubmit('Save');
	$rtn .= buildFormEnd();
	$rtn .= '</div>'."\n";
	$rtn .= buildOnLoad("document.addedit.filename.focus();");
 	return $rtn;
}

//---------- begin function contentManager ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function contentManager(){
	$rtn = '<div style="height:600px;overflow:auto;padding-right:25px;">'."\n";
	//pages
	$expand='';
	$recs=getDBRecords(array('-table'=>'_pages','page_type'=>1,'-order'=>'name','-index'=>'_id','-fields'=>"_id,name,permalink,_template"));
	$ico='<img src="/wfiles/_pages.gif" border="0" class="w_middle">';
	$title=$ico.' Pages ('.count($recs).')';
	//add new link
	//$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=contentmanager&emenu=add&table=_pages\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" border="0" class="w_middle"> add new</a></div>'."\n";
	$expand .= buildTableBegin(2,0);
	$expand .= '	<tbody>'."\n";
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td><a href="#" onclick="removeTinyMCE(\'txtfield_content\');return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=contentmanager&emenu=edit&table=_pages&id='.$id.'\');" class="w_link w_lblue w_block">'.$rec['name'].'</a></td></tr>'."\n";
	}
	$expand .= '	</tbody>'."\n";
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
	$rtn = '<div style="height:600px;overflow:auto;padding-right:25px;">'."\n";
	//files
	if(isset($_SERVER['DOCUMENT_ROOT'])){
		$expand='';
		$files=listFilesEx($_SERVER['DOCUMENT_ROOT']);
		$cnt=0;
		foreach($files as $file){
			if(!isTextFile($file['name'])){continue;}
			$cnt++;
			$ico=getFileIcon($file['name']);
			$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$ico.' '.$file['name'].'</a></div>'."\n";
		}
		$ico=getFileIcon("x.log");
		$title=$ico.' Root Files ('.$cnt.')';
		$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	}

	//.htaccess
	if(is_file(realpath("../.htaccess"))){
		echo '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file=../.htaccess\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/settings.png" border="0" class="w_middle"> .htaccess</a></div>'."\n";
	}
	//Configuration
	echo '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file=../config.xml\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/xml.png" border="0" class="w_middle"> config.xml</a></div>'."\n";

	//PHP Sandbox
	echo '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=sandbox&file=../config.xml\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/php.png" border="0" class="w_middle"> PHP Sandbox</a></div>'."\n";
	//templates
	$recs=getDBRecords(array('-table'=>'_templates','-order'=>'name','-index'=>'_id','-fields'=>'_id,name'));
	$ico='<img src="/wfiles/_templates.gif" border="0" class="w_middle">';
	$title=$ico.' Templates ('.count($recs).')';
	$expand='';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&table=_templates\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" border="0" class="w_middle"> add new</a></div>'."\n";
	$expand .= buildTableBegin(1,1,1);
	$expand .= buildTableTH(array('ID','Name'),array('thead'=>1));
	$expand .= '	<tbody>'."\n";
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td>'.$rec['_id'].'</td><td><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&table=_templates&id='.$id.'\');" class="w_link w_lblue">'.$rec['name'].'</a></td></tr>'."\n";
	}
	$expand .= '	</tbody>'."\n";
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	//pages
	$expand='';
	$recs=getDBRecords(array('-table'=>'_pages','-order'=>'name','-index'=>'_id','-fields'=>"_id,name,permalink,_template"));
	$ico='<img src="/wfiles/_pages.gif" border="0" class="w_middle">';
	$title=$ico.' Pages ('.count($recs).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&table=_pages\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" border="0" class="w_middle"> add new</a></div>'."\n";
	$expand .= buildTableBegin(1,1,1);
	$expand .= buildTableTH(array('ID','Name','TID'),array('thead'=>1));
	$expand .= '	<tbody>'."\n";
	foreach($recs as $id=>$rec){
    	$expand .= '	<tr><td>'.$rec['_id'].'</td><td><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&table=_pages&id='.$id.'\');" class="w_link w_lblue">'.$rec['name'].'</a></td><td>'.$rec['_template'].'</td></tr>'."\n";
	}
	$expand .= '	</tbody>'."\n";
	$expand .= buildTableEnd();
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	
	//Custom CSS
	$expand='';
	$files=listFilesEx('../wfiles/css/custom',array('ext'=>"css"));
	$ico=getFileIcon("x.css");
	$title=$ico.' Custom CSS Files ('.count($files).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&filetype=css\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" border="0" class="w_middle"> add new</a></div>'."\n";
	foreach($files as $file){
    	$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$file['name'].'</a></div>'."\n";
	}
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	
	//Custom Js
	$expand='';
	$files=listFilesEx('../wfiles/js/custom',array('ext'=>"js"));
	$ico=getFileIcon("x.js");
	$title=$ico.' Custom Js Files ('.count($files).')';
	//add new link
	$expand .= '<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=add&filetype=js\');" class="w_link w_lblue"><img src="/wfiles/iconsets/16/add.png" border="0" class="w_middle"> add new</a></div>'."\n";
	foreach($files as $file){
    	$expand .= '	<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'w_editor_main\',\'_menu=editor&emenu=edit&file='.$file['afile'].'\');" class="w_link w_lblue">'.$file['name'].'</a></div>'."\n";
	}
	$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
	
	$rtn .= '</div>'."\n";
	return $rtn;
}

//---------- begin function adminCronBoard ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function adminCronBoard(){
	$rtn='';
	$rtn .= '<div class="w_round w_dropshadow" style="background:#FFF;border:1px solid #b9b9b9;padding:3px 0 7px 0;margin-top:0px;" align="center">'."\n";
	//return printValue($recs);
	$rtn .= buildTableBegin(2,0);
	$rtn .= '	<tr>'."\n";
	$rtn .= '		<td><a href="/php/admin.php?_menu=list&_table_=_cron"><img src="/wfiles/_cron.png" border="0" class="w_middle" title="goto Cron" /></a></td>'."\n";
	$rtn .= '		<td colspan="5" class="w_bold w_dblue" align="center" style="font-size:1.1em;">Cron Activity Dashboard</td>'."\n";
	$rtn .= '		<td align="right"><div title="Update Timer" data-behavior="countdown" class="w_lblue w_smaller" id="cronboard_countdown">31</div></td>'."\n";
	$rtn .= '		<td align="right"><a href="/php/admin.php?_menu=list&_table_=_cronlog"><img src="/wfiles/_cronlog.png" border="0" class="w_middle" title="goto CronLog" /></a></td>'."\n";
	$rtn .= '	</tr>'."\n";
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
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<td>'.$t.'</td>'."\n";
		//crons
		$bg=$rec['count_crons']==0?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons'].'</td>'."\n";
		//active
		$bg=$rec['count_crons_active']==0?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_active'].'</td>'."\n";
		//inactive
		$bg='';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_inactive'].'</td>'."\n";
		//running
		$bg=$rec['count_crons_running'] > ($rec['count_crons']/2)?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_running'].'</td>'."\n";
		//MaxRun
		$bg='';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['maxrun'].'</td>'."\n";
		//listening
		$bg=$rec['count_crons_listening'] < ($rec['maxrun']*1.5)?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_crons_listening'].'</td>'."\n";
		//Logs
		$bg=$rec['logs'] > 100000?' style="color:#990101;"':'';
		$rtn .= '		<td align="center"'.$bg.'>'.$rec['count_cronlogs'].'</td>'."\n";
		$rtn .= '	</tr>'."\n";
	}
	$rtn .= buildTableEnd();
	$rtn .= '</div>'."\n";
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
	$db_stage=$SETTINGS['wasql_synchronize_master'];
	$db_live=$SETTINGS['wasql_synchronize_slave'];
	$recopts=array(
		'-table'=>"_fielddata",
		'tablename'	=> $table,
		'-index'	=> "fieldname",
		'-fields'	=> "fieldname,synchronize"
	);
	//echo printValue($recopts);exit;
	$recs=getDBRecords($recopts);
	if(!is_array($recs)){return $recs;}
	$flds=array();
	$fields=getDBFields("{$db_stage}.{$table}",1);
	$fields_live=getDBFields("{$db_live}.{$table}",1);
	//echo "{$table}:::".printValue($fields_live).printValue($fields);
	$wfields=array('_cdate','_cuser','_edate','_euser','_auser','_amem','_id','_aip','_adate');
	foreach($fields as $field){
    	if(isset($recs[$field]) && $recs[$field]['synchronize'] !=1){continue;}
    	if(in_array($field,$wfields)){continue;}
    	if(!in_array($field,$fields_live)){continue;}
		$flds[]=$field;
	}
	//if($table=='_pages'){echo $table.printValue($flds).printValue($fields).printValue($recs);}
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
?>