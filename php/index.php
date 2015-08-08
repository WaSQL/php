<?php
//Set upload size
//Set Post Max Size
ini_set('POST_MAX_SIZE', '34M');
ini_set('UPLOAD_MAX_FILESIZE', '30M');
ini_set('max_execution_time', 5000);
set_time_limit(5500);
error_reporting(E_ALL & ~E_NOTICE);
global $TIME_START;
$TIME_START=microtime(true);
$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("$progpath/common.php");
//check for special user login path
$url_parts=preg_split('/\/+/',preg_replace('/^\/+/','',$_REQUEST['_view']));
//echo printValue($_SERVER);exit;
if($url_parts[0]=='u'){
	array_shift($url_parts);
	$_REQUEST['username']=array_shift($url_parts);
	$_REQUEST['apikey']=array_shift($url_parts);
	$_REQUEST['_auth']=1;
	$_REQUEST['_view']=implode('/',$url_parts);
	//echo printValue($_REQUEST['_view']);exit;
}
include_once("$progpath/config.php");
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
include_once("$progpath/schema.php");
//check for tiny urls - /t/B49Z  - checks the _tiny table
if($url_parts[0]=='t' && count($url_parts)==2){
	loadExtras('tiny');
	$url=tinyCode($url_parts[1]);
	//echo $url;exit;
	if(preg_match('/^http/i',$url)){
    	header("Location: {$url}");
    	exit;
	}
}
include_once("$progpath/sessions.php");
include_once("$progpath/user.php");
global $CONFIG;
if(!isset($CONFIG['allow_frames']) || !$CONFIG['allow_frames']){
	@header('X-Frame-Options: SAMEORIGIN');
}
//check for valid_hosts in CONFIG settings and reject if needed
if(isset($CONFIG['valid_hosts'])){
	$valid_hosts=preg_split('/[\s\,\;]+/',strtolower(trim($CONFIG['valid_hosts'])));
    if(!count($valid_hosts)){break;}
    $valid=0;
    $host=strtolower(trim($_SERVER['HTTP_HOST']));
    if(!in_array($host,$valid_hosts)){
		$msg="Unauthorized host";
		$defmsg='';
		$exts=array('','_1','_2','_3','_4','_5','_6','_7','_8','_9');
		foreach($exts as $ext){
			$key='invalid_host_msg'.$ext;
			if(isset($CONFIG[$key])){
				$found=0;
				$pairs=preg_split('/\;+/',$CONFIG[$key]);
	            foreach($pairs as $pair){
	            	list($k,$v)=preg_split('/\=/',$pair,2);
	                if(!strlen($v) && !strlen($defmsg)){$defmsg=$k;}
					if(stringContains($host,$k)){
						$msg=$v;
						$found=1;
					}
					if($found==1){break;}
				}
			}
		}
		if(strlen($defmsg) && $msg=='Unauthorized host'){$msg=$defmsg;}
		header('HTTP/1.0 403 Forbidden');
		echo "{$msg}<br>\n";
		error_log("Unauthorized WaSQL host:{$host}, [{$_SERVER['REQUEST_URI']}] [{$msg}]",4);
	    exit;
	}
}
//check for valid_uhosts in CONFIG settings and reject if needed
if(isset($CONFIG['valid_uhosts'])){
    $valid_hosts=preg_split('/[\s\,\;]+/',strtolower(trim($CONFIG['valid_uhosts'])));
    if(!count($valid_hosts)){break;}
    $valid=0;
    $host=strtolower(trim($_SERVER['UNIQUE_HOST']));
    if(!in_array($host,$valid_hosts)){
		$msg="Unauthorized host";
		$defmsg='';
		$exts=array('','_1','_2','_3','_4','_5','_6','_7','_8','_9');
		foreach($exts as $ext){
			$key='invalid_host_msg'.$ext;
			if(isset($CONFIG[$key])){
				$found=0;
				$pairs=preg_split('/\;+/',$CONFIG[$key]);
                foreach($pairs as $pair){
                    list($k,$v)=preg_split('/\=/',$pair,2);
                    if(!strlen($v) && !strlen($defmsg)){$defmsg=$k;}
					if(stringContains($host,$k)){
						$msg=$v;
						$found=1;
					}
					if($found==1){break;}
				}
			}
		}
		if(strlen($defmsg) && $msg=='Unauthorized host'){$msg=$defmsg;}
		header('HTTP/1.0 403 Forbidden');
		echo "{$msg}<br>\n";
		error_log("Unauthorized WaSQL uhost:{$host}, [{$_SERVER['REQUEST_URI']}] [{$msg}]",4);
        exit;
	}
}
//push file?
if(isset($_REQUEST['_pushfile'])){
	$params=array();
	if(isset($_REQUEST['-attach']) && $_REQUEST['-attach']==0){$params['-attach']=0;}
 	$ok=pushFile(decodeBase64($_REQUEST['_pushfile']),$params);
}
//Fix up REQUEST
foreach($_REQUEST as $key=>$val){
	if(is_array($val)){continue;}
	//get rid of some dumb Request keys like __ktv -they are just google analytics stuff
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
//Check for heartbeat
if(isset($_REQUEST['_heartbeat']) && $_REQUEST['_heartbeat']==1){
	echo '<heartbeat>' . time() . '</heartbeat>'."\n";
	$t=isNum($_REQUEST['t'])?$_REQUEST['t']:60;
	$div=isNum($_REQUEST['div'])?$_REQUEST['div']:'null';
	echo buildOnLoad("scheduleHeartbeat('{$div}',{$t});");
	exit;
}
//check for favicon request
if(!isset($_REQUEST['_view']) || strlen(trim($_REQUEST['_view']))==0){
	if(isset($_REQUEST['favicon.ico'])){
		$_REQUEST['_view']='favicon.ico';
	}
	elseif(isset($CONFIG['index'])){$_REQUEST['_view']=$CONFIG['index'];}
    else{$_REQUEST['_view']='index';}
}
elseif($_REQUEST['_view']=='index' && isset($CONFIG['index']) && $CONFIG['index'] != 'index'){
	$_REQUEST['_view']=$CONFIG['index'];
}
//Check for special cases:  admin, errors
switch(strtolower($_REQUEST['_view'])){
    case 'admin':
    case 'wp-admin':
    case 'a':
    	if(!isDBPage($_REQUEST['_view'])){
	    	header('Location: /php/admin.php');
	    	exit;
		}
    break;
}

if(isAjax()){
	if(isset($_REQUEST['_serverdate'])){
		$dateformat=isset($_REQUEST['_dateformat'])?$_REQUEST['_serverdate']:"F j, Y, g:i a";
		echo date($dateformat);
		exit;
    }
}
//get_magic_quotes_gpc fix if it is on
wasqlMagicQuotesFix();
global $CONFIG;
//check for maintenance_datetime.  start, stop, format
if(isset($CONFIG['maintenance']) && strlen($CONFIG['maintenance'])){
	$maintenance=1;
	if(strlen($CONFIG['maintenance_datetime'])){
		$maintenance=0;
        list($start,$stop)=preg_split('/[\|\;]/',$CONFIG['maintenance_datetime'],2);
        $starttime=strtotime($start);
        $stoptime=strtotime($stop);
        $ctime=time();
        if(isNum($starttime) && isNum($stoptime)){
        	if($ctime > $starttime && $ctime < $stoptime){$maintenance=1;}
		}
		elseif(isNum($starttime) && $ctime > $starttime){$maintenance=1;}
	}
	if($maintenance==1){
		echo underMaintenance($CONFIG['maintenance']);
		exit;
	}
}
//Check for xml posts and parse them out...
unset($xmlpost);
if(isset($GLOBALS['HTTP_RAW_POST_DATA']) && strlen($GLOBALS['HTTP_RAW_POST_DATA']) && preg_match('/^\<\?xml /i',trim($GLOBALS['HTTP_RAW_POST_DATA']))){
	$xmlpost=trim($GLOBALS['HTTP_RAW_POST_DATA']);
}
elseif(isset($_REQUEST['xml']) && strlen($_REQUEST['xml']) && preg_match('/^\<\?xml /i',trim($_REQUEST['xml']))){
	$xmlpost=trim($GLOBALS['HTTP_RAW_POST_DATA']);
}
if(isset($xmlpost)){
	//set type
	$_REQUEST['_xmlrequest_type']=preg_match('/\<soap/is',$xmlpost)?'soap':'xml';
	//convert to array
    $_REQUEST['_xmlrequest_array']=xml2Array($xmlpost);
    $_REQUEST['_xmlrequest_raw']=$xmlpost;
}
//remind Me Form?
if(isset($_REQUEST['_remind']) && $_REQUEST['_remind']==1 && isset($_REQUEST['email'])){
	if(!isEmail($_REQUEST['email'])){
		echo '<img src="/wfiles/warn.gif" border="0" style="vertical-align:middle;">  <b class="w_red">Invalid email address.</b><br /><br />'."\n";
		echo ' Please enter a valid email address.'."\n";
		exit;
    }
	$ruser=getDBRecord(array('-table'=>'_users','email'=>$_REQUEST['email']));
	if(!is_array($ruser)){
		echo '<img src="/wfiles/warn.gif" border="0" style="vertical-align:middle;">  <b class="w_red">Invalid account.</b><br /><br />'."\n";
		echo "The email address you entered does not have an account with us.";
		exit;
    }
    else{
		//send the email.
		$to=$ruser['email'];
		$subject='RE:Remind Request from ' . $_SERVER['HTTP_HOST'];
		$message = 'We just received a request to remind you of your login information '. "\n\n";
		$message .= '<p>Username: '. $ruser['username']. "<br>\n";
		$pw=userIsEncryptedPW($ruser['password'])?userDecryptPW($ruser['password']):$ruser['password'];
		$message .= 'Password: '. $pw. "<br>\n";
		$message .= '<p>If you did not request this information, no worries. This email did go to you afterall, not them.' . "\n";
		//clear out errors
		@trigger_error("");
		//attempt to send the email
		if(isset($CONFIG['smtp'])){
			$ok=wasqlMail(array('to'=>$to,'subject'=>$subject,'message'=>$message));
			if($ok==true || $ok==1){
				echo '<img src="/wfiles/success.gif" border="0" style="vertical-align:middle;">  <b class="w_green">Account found! ['.$ok.']</b><br /><br />'."\n";
				echo 'We have sent your login information to ' . $ruser['email'];
				exit;
            }
            else{
				echo '<img src="/wfiles/warn.gif" border="0" style="vertical-align:middle;">  <b class="w_red">Technical failure.</b><br /><br />'."\n";
				echo 'Due to technical errors, we were unable to send you a reminder.<br /><br />'."\n";
				echo printValue($ok);
				exit;
            }
        }
		else{
			$headers = "From: ".$_SERVER['HTTP_HOST']." <no-reply@".$_SERVER['UNIQUE_HOST'].">\r\n";
			$headers .= "X-Mailer: WaSQL PHP/".phpversion();
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
			if(@mail($ruser['email'], $subject, $message, $headers)){
				echo '<img src="/wfiles/success.gif" border="0" style="vertical-align:middle;">  <b class="w_green">Account found!</b><br /><br />'."\n";
				echo 'We have sent your login information to ' . $ruser['email'];
				exit;
            }
            else{
				echo '<img src="/wfiles/warn.gif" border="0" style="vertical-align:middle;">  <b class="w_red">Technical failure.</b><br /><br />'."\n";
				echo 'Due to technical errors, we were unable to send you a reminder.<br /><br />'."\n";
				exit;
            }
		}
    }
	echo '<img src="/wfiles/warn.gif" border="0" style="vertical-align:middle;">  <b class="w_red">Invalid request.</b><br /><br />'."\n";
	echo 'We did not understand your request.'."\n";
	exit;
}
//require user to be logged in if the site is stage.
global $USER;
if(isAjax() && isUser()){
    //facebook functions
	if(isset($_REQUEST['_fbupdate'])){
    	$str=decodeBase64($_REQUEST['_fbupdate']);
    	list($id,$email)=preg_split('/\:/',$str,2);
    	$query="update _users set facebook_id='{$id}',facebook_email='{$email}' where _id={$USER['_id']}";
    	//echo $query;exit;
    	executeSQL($query);
    	echo buildOnLoad("facebook_id='{$id}';facebook_email='{$email}';facebookLinked();");
    	exit;
	}
	elseif(isset($_REQUEST['_fblink'])){
    	$str=decodeBase64($_REQUEST['_fblink']);
    	list($id,$email)=preg_split('/\:/',$str,2);
    	$query="update _users set facebook_id='{$id}',facebook_email='{$email}' where _id={$USER['_id']}";
    	//echo $query;exit;
    	executeSQL($query);
    	echo buildOnLoad("facebook_id='{$id}';facebook_email='{$email}';facebookLinked();");
    	exit;
	}
	if(isNum($_REQUEST['_wpass'])){
		echo wpassInfo($_REQUEST['_wpass']);
		exit;
	}
	if(isset($_REQUEST['_formname']) && $_REQUEST['_formname']=='_wpass_addedit'){
		$ok=processActions();
        	exit;
	}
}
//echo isUser().printValue($USER);exit;
if(!isUser() && isset($CONFIG['access']) && strtolower($CONFIG['access']) == 'user'){
    indexUserAccess();
    exit;
}
elseif(isDBStage() && !isUser() && (!isset($CONFIG['access']) || strtolower($CONFIG['access']) != 'all')){
    indexUserAccess();
    exit;
}

//redraw states field where country=CA .. _redraw=_users:states&opt_0=country&val_0=CA
if(isAjax() && isset($_REQUEST['_redraw'])){
	list($tablename,$fieldname)=preg_split('/\:/',$_REQUEST['_redraw'],2);
	$att=array();
	foreach($_REQUEST as $key=>$val){
        if(preg_match('/^opt\_([0-9]+)$/',$key,$m)){
            $field=$val;
            $_REQUEST[$field]=$_REQUEST["val_{$m[1]}"];
		}
		elseif(preg_match('/^att\_(.+)$/',$key,$m)){
			$att[$m[1]]=$val;
		}
	}
	if(isset($att['data-value']) && !strlen($att['value'])){$att['value']=$att['data-value'];}
	echo buildFormField($tablename,$fieldname,$att);
	exit;
}
//execute SQL Preview
if(isAjax() && (isset($_REQUEST['_sqlpreview_']) || isset($_REQUEST['_queryid_']))){
	if(isset($_REQUEST['_sqlpreview_'])){$cmd=trim($_REQUEST['_sqlpreview_']);}
	elseif(isNum($_REQUEST['_queryid_'])){
		$rec=getDBRecord(array('-table'=>"_queries",'_id'=>$_REQUEST['_queryid_']));
		if(is_array($rec)){
			$cmd=$rec['query'];
			if($_REQUEST['explain']){$cmd = "EXPLAIN\r\n".$cmd;}
		}
	}
	if(stringContains($_REQUEST['ajaxid'],'centerpop')){
		echo '<div class="w_centerpop_title">Preview</div>'."\n";
	}
	if($_REQUEST['view']){
		echo '<div class="w_centerpop_content" style="height:300px;overflow:auto;width:500px;">'."\n";
		echo preg_replace('/^Explain/i','',$cmd);
		echo '</div>'."\n";
		exit;
	}
	$cmd=trim($cmd);
	$singleline=preg_replace('/[\r\n]+/',' ',$cmd);
	if(preg_match('/^(select|show|explain)\ /is',$singleline)){
		if(preg_match('/^(select|show)\ /is',$singleline)){
			echo '<div class="w_centerpop_content" style="height:300px;overflow:auto;">'."\n";
			$recs=getDBRecords(array('-query'=>'explain '.$cmd));
			if(!is_array($recs) && strlen($recs)){
				echo $recs;
				echo '</div>'."\n";
        		exit;
			}
			elseif(isset($recs[0]['sql_error'])){
				echo $recs[0]['sql_error'];
				echo '</div>'."\n";
        		exit;
			}
			echo listDBResults($cmd,array('-hidesearch'=>1,'-limit'=>1000));
			echo '</div>'."\n";
        	exit;
		}
		else{
            echo '<div class="w_centerpop_content">'."\n";
           	echo listDBResults($cmd,array('-hidesearch'=>1,'-limit'=>1000));
            echo '<div class="w_pad" style="width:500px;">'."\n";
			echo preg_replace('/^Explain/i','',$cmd);
			echo '</div>'."\n";
			echo '</div>'."\n";
        	exit;
		}

    }
	else{
		echo 'Must be a select statement<hr size="1">'."[{$cmd}]";
        echo '</div>'."\n";
        exit;
	}
	echo '</div>'."\n";
	exit;
}
//if the requested url ends with a slash, look for an existing index file and use it.
$_REQUEST['_view']=preg_replace('/\/$/','',$_REQUEST['_view']);
//check for apps
if(preg_match('/apps\/(.+)/i',$_REQUEST['_view'],$m)){
	echo includeApp($m[1]);
	exit;
}
$rec=getDBRecord(array('-table'=>'_pages','-fields'=>"_id,name",'name'=>databaseEscapeString($_REQUEST['_view'])));
if(!is_array($rec)){
	if(isset($_SERVER['REQUEST_URI']) && preg_match('/\/$/',$_SERVER['REQUEST_URI'])){
    	$exts=array('htm','html','php');
    	foreach($exts as $ext){
			$cfile="{$_SERVER['DOCUMENT_ROOT']}{$_SERVER['REQUEST_URI']}/index.{$ext}";
			$cfile=preg_replace('/\/+/','/',$cfile);
			if(is_file($cfile)){
				header('X-Platform: WaSQL');
				if($ext=='php'){
					$phpdata=getFileContents($cfile);
					echo evalPHP($phpdata);
                }
                else{readfile($cfile);}
				exit;
            }
        }
	}
	//look for local files - used them if they exist
	if(isset($_REQUEST['_view']) && strlen($_REQUEST['_view'])){
		$exts=array('','htm','html','php','js','css','txt','ttf','eot','svg','otf','woff');
		foreach($exts as $ext){
			if(isset($_SERVER['REQUEST_URI']) && preg_match('/\/$/',$_SERVER['REQUEST_URI'])){
				$cfile="{$_SERVER['DOCUMENT_ROOT']}{$_SERVER['REQUEST_URI']}/{$_REQUEST['_view']}.{$ext}";
			}
			else{
            	$cfile="{$_SERVER['DOCUMENT_ROOT']}/{$_REQUEST['_view']}.{$ext}";
			}
			$cfile=preg_replace('/\/+/','/',$cfile);
			$cfile=preg_replace('/\.+$/','',$cfile);
			$ext=getFileExtension($cfile);
			if(is_file($cfile)){
				header('X-Platform: WaSQL');
				switch($ext){
					case 'js':header("Content-type: text/javascript");break;
					case 'css':header("Content-type: text/css");break;
					case 'ttf':header("Content-type: application/font-sfnt");break;
					case 'eot':header("Content-type: application/vnd.ms-fontobject");break;
					case 'svg':header("Content-type: image/svg+xml");break;
					case 'otf':header("Content-type: application/font-sfnt");break;
					case 'woff':header("Content-type: application/font-woff");break;
					default:
						$ctype=getFileContentType($cfile);
						header("Content-type: {$ctype}");
						break;
				}
				if($ext=='php'){
					echo evalPHP(getFileContents($cfile));
                }
                else{readfile($cfile);}
				exit;
            }
        }
	}
}
//set_error_handler("wasqlErrorHandler",E_STRICT | E_ALL);
global $CONFIG;
//Handle file uploads
if(isset($_SERVER['CONTENT_TYPE']) && preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
 	if(isset($CONFIG['processFileUploads']) && $CONFIG['processFileUploads']=='off'){}
 	elseif(isset($_REQUEST['processFileUploads']) && $_REQUEST['processFileUploads']=='off'){}
 	else{
		//filemanager will have _dir and _menu
		if(isset($_REQUEST['_dir']) && isset($_REQUEST['_menu'])){processFileUploads();}
		//form submit will have _table and _action
		elseif(isset($_REQUEST['_table']) && isset($_REQUEST['_action'])){processFileUploads();}
		//else{echo printValue($_REQUEST);exit;}
	}
}
//process actions
if(isset($_REQUEST['_action'])){
	$ok=processActions();
}
//	Build in API calls - requires the user to be logged in (via apikey)
if(isset($_REQUEST['apimethod']) && strlen($_REQUEST['apimethod'])){
	if(!isUser()){
		header('Content-type: text/xml');
		header('X-Platform: WaSQL');
		echo xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
		echo "<result>\r\n";
		echo "	<fatal_error>User Authentication Failed for {$_REQUEST['username']}".printValue($_REQUEST)."</fatal_error>\r\n";
		echo "</result>\r\n";
		exit;
    }
	//only allow administrators to use postedit
	if(!isAdmin()){
		$user=ucwords(xmlEncodeCDATA($USER['username']));
		header('Content-type: text/xml');
		header('X-Platform: WaSQL');
		echo xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
		echo "<result>\r\n";
		echo "	<fatal_error>{$user}, you do not have sufficient rights. Sorry.</fatal_error>\r\n";
		echo "</result>\r\n";
		exit;
    }
	switch(strtolower($_REQUEST['apimethod'])){
		case 'posteditxml':
			//return xml of pages and templates
			$tables=array('_pages','_templates','_models');
			if(strlen($_REQUEST['postedittables'])){
				$moretables=preg_split('/[\,\;\:]+/',$_REQUEST['postedittables']);
				foreach($moretables as $mtable){
					if(isDBTable($mtable) && !in_array($mtable,$tables)){array_push($tables,$mtable);}
                    }
                }
			header('Content-type: text/xml');
			header('X-Platform: WaSQL');

			echo postEditXml($tables,$_REQUEST['dbname']);
			exit;
			break;
		case 'posteditupload':
			//upload
			header('Content-type: text/plain');
			header('X-Platform: WaSQL');
			echo "PostEdit Upload:\n";
			echo printValue($_REQUEST) . printValue($_FILES);
			exit;
			break;
		case 'posteditlist':
			//list files in a specific path off of document root
			header('Content-type: text/plain');
			header('X-Platform: WaSQL');
			$listdir="{$_SERVER['DOCUMENT_ROOT']}/{$_REQUEST['_path']}";
			$files=listFiles($listdir);
			sort($files);
			foreach($files as $file){
				$afile="{$listdir}/{$file}";
				if(is_dir($afile) || is_link($afile)){continue;}
				echo "{$file}\n";
            }
			exit;
			break;
    }
}

//Preload?
if(isset($CONFIG['preload'])){
	if(is_file($CONFIG['preload'])){
		loadExtras($CONFIG['preload']);
    }
	else{
    	$ok=includePage($CONFIG['preload']);
    	if(!isNum($ok) && strlen($ok)){
            echo $ok;
            exit;
		}
	}
}
//get page record
if(!isset($_REQUEST['_view']) || strlen($_REQUEST['_view'])==0){
	abort("No Page View Specified<br>\n" . printValue($_REQUEST));
}
//check for mobile_index in config file if the client device is a mobile device
if(isset($CONFIG['mobile_index']) && isMobileDevice() && !isset($_REQUEST['_template']) && preg_match('/^(1|index)$/i',$_REQUEST['_view'])){
	//make sure the page exists first
	$query="select _id,name from _pages where";
	if(isNum($CONFIG['mobile_index'])){$query .= " _id={$CONFIG['mobile_index']}";}
	else{$query .= " name = '{$CONFIG['mobile_index']}'";}
	$r=getDBRecords(array('-query'=>$query));
	if(isNum($r[0]['_id'])){
		$_REQUEST['_view']=$r[0]['name'];
	}
}
//check for mobile_template in config file if the client device is a mobile device
if(isset($CONFIG['mobile_template']) && isMobileDevice() && !isset($_REQUEST['_template'])){
	$query="select _id from _templates where";
	if(isNum($CONFIG['mobile_template'])){$query .= " _id={$CONFIG['mobile_template']}";}
	else{$query .= " name = '{$CONFIG['mobile_template']}'";}
	$r=getDBRecords(array('-query'=>$query));
	if(isNum($r[0]['_id'])){
		$_REQUEST['_template']=$r[0]['_id'];
	}
}

/* Determine the page to view:
		1. check for permalink field
		2. check for name field match
		3. check for _id field match
*/
checkDBTableSchema('_pages');
$view=databaseEscapeString($_REQUEST['_view']);
$_REQUEST['_viewfield']='body';
if(stringContains($view,'.')){
	$ext=getFileExtension($_REQUEST['_view']);
	if(in_array($ext,array('xml','json','csv','phtm'))){
		$view=getFileName($_REQUEST['_view'],1);
		//echo "Ext:{$ext}, View:{$view}, REQUEST:{$_REQUEST['_view']}<br>\n";exit;
		//$view=preg_replace('/\.phtm$/i','',$view);
		$pagefields=getDBFields('_pages');
		if(in_array($ext,$pagefields)){
	     	$_REQUEST['_viewfield']=strtolower($ext);
		}
	}
}
$getopts=array(
	'-table'=>'_pages',
	'-notimestamp'=>1,
	'-where'=>"permalink = '{$view}' or name = '{$view}'"
);
$recs=getDBRecords($getopts);
if(is_array($recs)){
	if(count($recs)==1){
		$PAGE=$recs[0];
	}
	else{
    	//permalink first, then name, then id
    	$found=0;
    	foreach($recs as $rec){
            if(strlen($rec['permalink']) && strtolower($rec['permalink'])==strtolower($view)){
                $PAGE=$rec;
                $found=1;
                break;
			}
		}
		if($found==0){
            foreach($recs as $rec){
	            if(strlen($rec['name']) && strtolower($rec['name'])==strtolower($view)){
	                $PAGE=$rec;
	                $found=1;
	                break;
				}
			}
		}
		if($found==0 && isNum($view)){
            foreach($recs as $rec){
            	if($rec['_id']==$view){
                	$PAGE=$rec;
                	$found=1;
                	break;
				}
			}
		}
	}
}
//Check for  /page/a/b/c  /a/b/c/d/e/f
if(!is_array($PAGE) && isset($CONFIG['redirect_page'])){
	$parts=preg_split('/\/+/',$view);
	//remove all parts before $view and set passthru
	$stripped=0;
	$tmp=array();
	foreach($parts as $part){
        $part=trim($part);
        if($part==$view){
			$stripped=1;
			continue;
		}
		if($stripped){$tmp[]=$part;}
	}
	$_REQUEST['passthru']=$tmp;
	$view=includePage($CONFIG['redirect_page'],array('redirect_page'=>$parts));
	$view=trim($view);
	$_REQUEST['_view']=$view;
	$getopts=array(
		'-table'=>'_pages',
		'-notimestamp'=>1,
		'-where'=>"permalink = '{$view}' or name = '{$view}'"
	);
	if(isNum($view)){
    	$getopts['-where'] = "_id={$view}";
	}
	$recs=getDBRecords($getopts);
	//echo printValue($getopts).printValue($rec);
	if(is_array($recs)){
		if(count($recs)==1){
			$PAGE=$recs[0];
		}
		else{
    		//permalink first, then name, then id
    		$found=0;
    		foreach($recs as $rec){
            	if(strlen($rec['permalink']) && strtolower($rec['permalink'])==strtolower($view)){
                	$PAGE=$rec;
                	$found=1;
                	break;
				}
			}
			if($found==0){
            	foreach($recs as $rec){
	            	if(strlen($rec['name']) && strtolower($rec['name'])==strtolower($view)){
	                	$PAGE=$rec;
	                	$found=1;
	                	break;
					}
				}
			}
			if($found==0 && isNum($view)){
            	foreach($recs as $rec){
	            	if($rec['_id']==$view){
	                	$PAGE=$rec;
	                	$found=1;
	                	break;
					}
				}
			}
		}
	}
}

if(!is_array($PAGE) && stringContains($view,'/') && isset($CONFIG['missing_page']) && $CONFIG['missing_page']=='passthru'){
	$parts=preg_split('/\/+/',$view);
	$view=array_shift($parts);
	$_REQUEST['_view']=$view;
	$_REQUEST['passthru']=$parts;
	$getopts=array(
		'-table'=>'_pages',
		'-notimestamp'=>1,
		'-where'=>"permalink = '{$view}' or name = '{$view}'"
	);
	if(isNum($view)){
    	$getopts['-where'] = "_id={$view}";
	}
	$recs=getDBRecords($getopts);
	if(is_array($recs)){
		if(count($recs)==1){
			$PAGE=$recs[0];
		}
		else{
    		//permalink first, then name, then id
    		$found=0;
    		foreach($recs as $rec){
            	if(strlen($rec['permalink']) && strtolower($rec['permalink'])==strtolower($view)){
                	$PAGE=$rec;
                	$found=1;
                	break;
				}
			}
			if($found==0){
            	foreach($recs as $rec){
	            	if(strlen($rec['name']) && strtolower($rec['name'])==strtolower($view)){
	                	$PAGE=$rec;
	                	$found=1;
	                	break;
					}
				}
			}
			if($found==0 && isNum($view)){
            	foreach($recs as $rec){
	            	if($rec['_id']==$view){
	                	$PAGE=$rec;
	                	$found=1;
	                	break;
					}
				}
			}
		}
	}
}
//if errorpage is set in config.xml and page is missing then go to error page and set errorpage
if(!is_array($PAGE) && isset($CONFIG['missing_page'])){
	$_REQUEST['missing_page']=$_REQUEST['_view'];
	if(is_numeric($CONFIG['missing_page'])){
		$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'_id'=>$CONFIG['missing_page']));
	}
	else{
		$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'name'=>$CONFIG['missing_page']));
	}
}
//custom 404
if(!is_array($PAGE) && isset($CONFIG['page_404'])){
	header("Location: /{$CONFIG['page_404']}", true, 404);
	if(is_numeric($CONFIG['page_404'])){
		$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'_id'=>$CONFIG['page_404']));
	}
	else{
		$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'name'=>$CONFIG['page_404']));
	}
}
if(!is_array($PAGE)){
	if(isset($CONFIG['missing_template'])){
		$tid=$CONFIG['missing_template'];
		if(isNum($tid)){
			$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'_id'=>$tid));
		}
		else{
			$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'name'=>$tid));
		}
		if(is_array($TEMPLATE)){
			wasqlSetMinify();
			if(strlen(trim($TEMPLATE['functions']))){
				$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$TEMPLATE['_id']}"));
				if(!isNum($ok) && strlen(trim($ok))){echo $ok;}
	        }
			$htm=$TEMPLATE['body'];
			//show the page
			$PAGE['body']='No page found';
			$htm=evalPHP($htm);
    			$htm=str_replace('@self(body)',$PAGE['body'],$htm);
    			$htm = evalPHP($htm);
    			echo trim($htm);
    			unset($htm);
    			exit;
		}
		else{
			header("HTTP/1.1 404 Not Found");
    		abort('No page found');
		}
	}
	else{
		header("HTTP/1.1 404 Not Found");
    		abort('No page found');
	}
}
global $TEMPLATE;
if(is_array($PAGE) && $PAGE['_id'] > 0){
	//look for _marker requests
    if(isset($_REQUEST['_marker'])){
		if($_REQUEST['_marker']=='load'){
			//check to make sure the _markers table is available.  If not create it
			if(!isDBTable('_markers')){createWasqlTable('_markers');}
			$markers=array();
			$recs=getDBRecords(array('-table'=>'_markers','status'=>1,'page_id'=>$PAGE['_id']));
			foreach($recs as $rec){
				$marker=array('x'=>$rec['mousex'],'y'=>$rec['mousey'],'page'=>$rec['page_id'],'priority'=>$rec['priority']);
				$markers[]=$marker;
			}
			$json=json_encode($markers);
			//$json=str_replace('"',"'",$json);
			echo '<div id="wasqlMarkerTagsData" data-page="'.$PAGE['_id'].'">'.$json.'</div>'."\n";
			echo buildOnLoad("wasqlMarkerTagsJson('wasqlMarkerTagsData');");
			echo printValue($PAGE['name']);
			exit;
		}
		elseif($_REQUEST['_marker']=='close'){
			if(isNum($_REQUEST['add_id'])){
				$rec=getDBRecord(array('-table'=>'_markers','_id'=>$_REQUEST['add_id']));
				echo buildOnLoad("wasqlMarkerTag({$rec['mousex']},{$rec['mousey']},{$rec['page_id']},{$rec['priority']});");
			}
			elseif(isNum($_REQUEST['edit_id'])){
				$rec=getDBRecord(array('-table'=>'_markers','_id'=>$_REQUEST['edit_id']));
				if($rec['status']==1){
					echo buildOnLoad("wasqlMarkerTag({$rec['mousex']},{$rec['mousey']},{$rec['page_id']},{$rec['priority']});");
				}
				else{
					$guid="_marker_{$rec['mousex']}_{$rec['mousey']}_{$rec['page_id']}";
					echo buildOnLoad("removeId('{$guid}');");
				}
			}
	    	echo buildOnLoad("removeId('centerpop');");
	    	echo printValue($_REQUEST);
	    	exit;
		}
		$opts=array(
			'-table'=>'_markers',
			'-fields'=>'status:priority,problem',
			'-name'=>'_markerform',
			'_marker'=>'close',
			'priority_displayname'=>'Importance',
			'note_displayname'=>'Describe what you want different',
			'-focus'=>'problem',
			'-hide'=>'clone',
			'-onsubmit'=>"return ajaxSubmitForm(this,'_markernulldiv');"
		);
		$minx=$_REQUEST['mousex']-30;
		$maxx=$_REQUEST['mousex']+30;
		$miny=$_REQUEST['mousey']-30;
		$maxy=$_REQUEST['mousey']+30;
		$recopts=array(
			'-table'=>'_markers',
			'-where'=>"mousex between {$minx} and {$maxx} and mousey between {$miny} and {$maxy} and status=1 and page_id={$PAGE['_id']}"
		);
		$rec=getDBRecord($recopts);
		if(isset($rec['_id'])){
			$opts['_id']=$rec['_id'];
			$opts['_action']='EDIT';
			$opts['-fields']='status:priority,problem,solution';
			$opts['-focus']='solution';
			$opts['status']=2;
			$opts['-save']='Mark as Fixed';
		}
		else{
	    	$opts['mousex']=$_REQUEST['mousex'];
	    	$opts['mousey']=$_REQUEST['mousey'];
	    	$opts['status']=1;
	    	$opts['_action']='ADD';
	    	$opts['page_id']=$PAGE['_id'];
	    	$opts['-save']='Mark this Page';
		}
		echo addEditDBForm($opts);
		exit;
	}
	//ignore viewfield if blank
	if(strlen(trim($PAGE[$_REQUEST['_viewfield']]))==0){
          	$_REQUEST['_viewfield']='body';
	}
	//echo printValue($viewfield);exit;
	//determine Content-type
	if(!headers_sent()){
		if(strtolower($PAGE['name'])=='css'){header("Content-type: text/css");}
		elseif(strtolower($PAGE['name'])=='js'){header("Content-type: text/javascript");}
		else{
          	switch($_REQUEST['_viewfield']){
               	case 'xml':header("Content-type: text/xml; charset=utf-8");break;
               	case 'json':header("Content-type: application/json; charset=utf-8");break;
               	case 'csv':header("Content-type: text/csv; charset=utf-8");break;
               	default:
					header("Content-type: text/html; charset=utf-8");
				break;
			}
		}
	}
	$tid=1;
	if(isset($_REQUEST['template'])){$_REQUEST['_template']=$_REQUEST['template'];}
	if(isset($_REQUEST['_template']) && $_REQUEST['_template']==0){unset($_REQUEST['_template']);}
	if(isAjax() && !isset($_REQUEST['_template']) && strtoupper($_SERVER['REQUEST_METHOD'])=='GET'){
		//default to the first template (blank) if the request is ajax and the request not a post
		$tid=1;
	}
	elseif(isset($_REQUEST['_template']) && strlen($_REQUEST['_template'])){
		if(strtolower($_REQUEST['_template'])=='user' && isNum($USER['template'])){
        	$tid=$USER['template'];
		}
		else{
			$tid=$_REQUEST['_template'];
		}
	}
	elseif(isset($_SERVER['_template']) && strlen($_SERVER['_template'])){$tid=$_SERVER['_template'];}
	elseif($_REQUEST['_viewfield'] != 'body'){$tid=1;}
	elseif(isset($PAGE['template']) && strlen($PAGE['template'])){$tid=$PAGE['template'];}
	elseif(isset($PAGE['_template']) && strlen($PAGE['_template'])){$tid=$PAGE['_template'];}
	$getopts=array('-table'=>'_templates','-notimestamp'=>1);
	if(isNum($tid)){$getopts['_id']=$tid;}
	else{$getopts['name']=$tid;}
	$TEMPLATE=getDBRecord($getopts);
	//set minify array
	wasqlSetMinify(0);
	if(isset($PAGE['_counter']) && isNum($PAGE['_id'])){
        $ok=executeSQL("update _pages set _counter=_counter+1 where _id={$PAGE['_id']}");
	}
	if($PAGE['_cache']==1 && !isUser() && count($_REQUEST)==2){
		$progpath=dirname(__FILE__);
		$cachefile="{$progpath}/temp/cachedpage_{$CONFIG['dbname']}_{$PAGE['_id']}_{$PAGE['_template']}.htm";
		if(is_file($cachefile)){
			$cdate=strlen($PAGE['_edate'])?$PAGE['_edate']:$PAGE['_cdate'];
			header('X-Platform: WaSQL');
			header('X-Cached: '.$cdate);
			if($ext=='php'){
				$phpdata=getFileContents($cachefile);
				echo evalPHP($phpdata);
            }
            else{readfile($cachefile);}
			exit;
		}
	}
	//Load template Functions
	//echo "HERE:::".$TEMPLATE['functions'];exit;
	if(strlen(trim($TEMPLATE['functions']))){
		$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$TEMPLATE['_id']}"));
		if(!isNum($ok) && strlen(trim($ok))){echo $ok;}
    }
    //load page functions
    if(strlen(trim($PAGE['functions']))){
		$iopts=array('-table'=>'_pages','-field'=>'functions','-where'=>"_id={$PAGE['_id']}");
		$ok=includeDBOnce($iopts);
		if(strlen($ok) > 3){echo $ok;}
    }
	unset($tid);
	$viewfield=isset($_REQUEST['_viewfield'])?$_REQUEST['_viewfield']:'body';
	$controller=strlen(trim($PAGE['controller']))?trim($PAGE['controller']):'';
	//add controller before viewfield content
	$htm='';
	if($viewfield=='body'){
		$htm=$TEMPLATE['body'];
		$htm=str_replace('@self(body)',$PAGE[$viewfield],$htm);
		$htm=evalPHP(array($htm));
	}
	else{$htm=$PAGE[$viewfield];}
	$htm=evalPHP(array($controller,$htm));
    	//if the page name or permalink ends in .html then write the static file.
    	if(preg_match('/\.(htm|html)$/i',$PAGE['name'])){
		$afile="{$_SERVER['DOCUMENT_ROOT']}/{$PAGE['name']}";
		setFileContents($afile,$htm);
	}
	elseif(strlen($PAGE['permalink']) && preg_match('/\.(htm|html)$/i',$PAGE['permalink'])){
		$afile="{$_SERVER['DOCUMENT_ROOT']}/{$PAGE['permalink']}";
		setFileContents($afile,$htm);
	}
	if($PAGE['_cache']==1 && !isUser() && count($_REQUEST)==2){
		$progpath=dirname(__FILE__);
		$cachefile="{$progpath}/temp/cachedpage_{$CONFIG['dbname']}_{$PAGE['_id']}_{$TEMPLATE['_id']}.htm";
		setFileContents($cachefile,$htm);
	}
	echo trim($htm);
    	unset($htm);
    	global $USER;
    	$adate=date("Y-m-d H:i:s");
    	//template_tracking
    	if(!isset($CONFIG['template_tracking']) || $CONFIG['template_tracking']==1){
    	//update _adate,_auser, and _aip in the templates table
		$updateopts=array(
			'-table'	=> "_templates",
			'-where'	=> "_id={$TEMPLATE['_id']}",
			'-noupdate'	=> 1,
		);
		if(in_array('_aip',array_keys($TEMPLATE))){
			$updateopts['_aip']=$_SERVER['REMOTE_ADDR'];
		}
		if(in_array('_auser',array_keys($TEMPLATE)) && isNum($USER['_id'])){
			$updateopts['_auser']=$USER['_id'];
		}
		if(in_array('_adate',array_keys($TEMPLATE))){
			$updateopts['_adate']=$adate;
		}
		if(count($updateopts) > 3){
			$ok=editDBRecord($updateopts);
	     }
	}
	if(!isset($CONFIG['page_tracking']) || $CONFIG['page_tracking']==1){
	    //update _aip, _auser, _amem, and  _adate in pages table
	    $updateopts=array(
			'-table'=>'_pages',
			'-where'=>"_id={$PAGE['_id']}",
			'-noupdate'	=> 1,
		);
		if(in_array('_amem',array_keys($PAGE))){
			$updateopts['_amem']=getMemoryUsage(0);
		}
		if(in_array('_aip',array_keys($PAGE))){
			$updateopts['_aip']=$_SERVER['REMOTE_ADDR'];
		}
		if(in_array('_auser',array_keys($PAGE)) && isNum($USER['_id'])){
			$updateopts['_auser']=$USER['_id'];
		}
		if(in_array('_adate',array_keys($PAGE))){
			$updateopts['_adate']=$adate;
		}
		//update the _env int the _pages table if it exists
		if(in_array('_env',array_keys($PAGE))){
			$updateopts['_env']=getRemoteEnv();
		}
		if(count($updateopts) > 3){
			$ok=editDBRecord($updateopts);
	    }
	}
	if(!isset($CONFIG['user_tracking']) || $CONFIG['user_tracking']==1){
	    //update _apage in users table
	    if(isNum($USER['_id']) && $USER['_id'] > 0){
		    $updateopts=array(
				'-table'=>'_users',
				'-where'=>"_id={$USER['_id']}",
				'-noupdate'	=> 1,
			);
			if(in_array('_apage',array_keys($USER))){
				$updateopts['_apage']=$PAGE['_id'];
			}
			if(count($updateopts) > 3){
				$ok=editDBRecord($updateopts);
		    }
		}
	}
	unset($adate);
    //add _access Record
    addDBAccess();
}
else{
	$PAGE=array('body'=>"PAGE NOT FOUND" . printValue($_REQUEST));
	if(isset($CONFIG['missing_msg'])){
		//redirect if missing_msg is a url
		if(preg_match('/^http/i',$CONFIG['missing_msg'])){
			header("Location: {$CONFIG['missing_msg']}");
			exit;
        }
        else{
			$PAGE['body']=$CONFIG['missing_msg'];
		}
	}
	$_REQUEST['missing_page']=$_REQUEST['_view'];
	//display the No Page error in the main template
	$tid=2;
	if(isset($_REQUEST['_template']) && strlen($_REQUEST['_template'])){$tid=$_REQUEST['_template'];}
	elseif(isset($_SESSION['w_MINIFY']['template_id'])){$tid=$_SESSION['w_MINIFY']['template_id'];}
	elseif(isset($CONFIG['missing_template'])){$tid=$CONFIG['missing_template'];}
	if(isNum($tid)){
		$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'_id'=>$tid));
	}
	else{
		$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'name'=>$tid));
	}
	//set minify array
	wasqlSetMinify(0);
	if(is_array($TEMPLATE)){
		if(strlen(trim($TEMPLATE['functions']))){
			$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$TEMPLATE['_id']}"));
			if(!isNum($ok) && strlen(trim($ok))){echo $ok;}
        }
		//show the page
		$htm=$TEMPLATE['body'];
		//$htm=evalInsertPage($htm);
		$htm=evalPHP($htm);
    	$htm=str_replace('@self(body)',$PAGE['body'],$htm);
    	//$htm=evalInsertPage($htm);
    	$htm = evalPHP($htm);
    	echo trim($htm);
    	unset($htm);
	}
	else{
		abort("No PAGE<br>\n" . printValue($_REQUEST));
	}
}
//---------- begin function indexUserAccess ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function indexUserAccess(){
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	$TEMPLATE=null;
	if(isset($CONFIG['access_page'])){
		$pid=$CONFIG['access_page'];
		if(isNum($pid)){
			$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'_id'=>$pid));
		}
		else{
			$PAGE=getDBRecord(array('-table'=>'_pages','-notimestamp'=>1,'name'=>$pid));
		}
		if(!isset($CONFIG['access_template']) && isNum($PAGE['_template'])){
        	$CONFIG['access_template']=$PAGE['_template'];
		}
	}
	else{$PAGE=array('body'=>userLoginForm());}
	if(isset($CONFIG['access_template'])){
		$tid=$CONFIG['access_template'];
		if(isNum($tid)){
			$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'_id'=>$tid));
		}
		else{
			$TEMPLATE=getDBRecord(array('-table'=>'_templates','-notimestamp'=>1,'name'=>$tid));
		}
	}
	if(is_array($TEMPLATE)){
		wasqlSetMinify();
		if(strlen(trim($TEMPLATE['functions']))){
			$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$TEMPLATE['_id']}"));
			if(!isNum($ok) && strlen(trim($ok))){echo $ok;}
        }
        //load page functions
        if(strlen(trim($PAGE['functions']))){
			$iopts=array('-table'=>'_pages','-field'=>'functions','-where'=>"_id={$PAGE['_id']}");
			$ok=includeDBOnce($iopts);
			if(!isNum($ok) && strlen(trim($ok))){echo $ok;}
        }
		$htm=$TEMPLATE['body'];
		$htm=evalPHP($htm);
    	$htm=str_replace('@self(body)',$PAGE['body'],$htm);
    	$htm = evalPHP($htm);
    	echo trim($htm);
    	unset($htm);
    	exit;
	}
	else{
    	abort(userLoginForm(),$CONFIG['access_title'],$CONFIG['access_subtitle']);
	}
}