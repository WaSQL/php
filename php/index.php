<?php
$starttime=microtime(true);
$loadtimes=array();
//Set upload size
//Set Post Max Size
ini_set('POST_MAX_SIZE', '64M');
ini_set('UPLOAD_MAX_FILESIZE', '60M');
ini_set('max_execution_time', 10000);
set_time_limit(5500);
error_reporting(E_ALL & ~E_NOTICE);
$_SERVER['TIME_START']=microtime(true);
$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
$stime=microtime(true);
include_once("$progpath/common.php");
$loadtimes['common']=number_format((microtime(true)-$stime),3);
//check for minify redirect
if(preg_match('/^w_min\/minify\_(.*?)\.css$/i',$_REQUEST['_view'],$m)){
	header("Location: /php/minify_css.php?_minify_={$m[1]}",TRUE,301);
	exit;
}
if(preg_match('/^w_min\/minify\_(.*?)\.js$/i',$_REQUEST['_view'],$m)){
	header("Location: /php/minify_js.php?_minify_={$m[1]}",TRUE,301);
	exit;
}
//check for special user login path
$url_parts=preg_split('/\/+/',preg_replace('/^\/+/','',$_REQUEST['_view']));
//echo printValue($_SERVER);exit;
if($url_parts[0]=='u'){
	//u=user   /u/username/apikey/pagename/....
	array_shift($url_parts);
	$_REQUEST['username']=array_shift($url_parts);
	$_REQUEST['apikey']=array_shift($url_parts);
	$_REQUEST['_auth']=1;
	$_REQUEST['_view']=implode('/',$url_parts);
	//echo printValue($_REQUEST['_view']);exit;
}
elseif($url_parts[0]=='t'){
	//t= template  /t/4/forms/....
	array_shift($url_parts);
	$_REQUEST['_template']=array_shift($url_parts);
	$_REQUEST['_view']=implode('/',$url_parts);
	//echo printValue($_REQUEST['_view']);exit;
}
global $CONFIG;
$stime=microtime(true);
include_once("$progpath/config.php");
$loadtimes['config']=number_format((microtime(true)-$stime),3);
$stime=microtime(true);
include_once("$progpath/wasql.php");
$loadtimes['wasql']=number_format((microtime(true)-$stime),3);
$stime=microtime(true);
include_once("$progpath/database.php");
$loadtimes['database']=number_format((microtime(true)-$stime),3);
//launch setup on new databases;
if(!isDBTable('_users')){
	if(is_file("{$progpath}/admin/setup_functions.php")){
    	include_once("{$progpath}/admin/setup_functions.php");
	}
	$body=getFileContents("{$progpath}/admin/setup_body.htm");
	$controller=getFileContents("{$progpath}/admin/setup_controller.php");
	echo evalPHP(array($controller,$body));
	exit;
}
//check for tiny urls - /y/B49Z  - checks the _tiny table
if($url_parts[0]=='y' && count($url_parts)==2){
	include_once("$progpath/schema.php");
	loadExtras('tiny');
	$url=tinyCode($url_parts[1]);
	//echo $url;exit;
	if(preg_match('/^http/i',$url)){
    	header("Location: {$url}");
    	exit;
	}
}
$stime=microtime(true);
include_once("$progpath/sessions.php");
$loadtimes['sessions']=number_format((microtime(true)-$stime),3);
$stime=microtime(true);
include_once("$progpath/user.php");
$loadtimes['user']=number_format((microtime(true)-$stime),3);
global $CONFIG;
if(isset($CONFIG['allow_origin']) && strlen($CONFIG['allow_origin'])){
	switch(strtolower($CONFIG['allow_origin'])){
		case '*':
		case 'all':
			$CONFIG['allow_origin']=$_SERVER['HTTP_REFERER'];
		break;
	}
	@header("Access-Control-Allow-Origin: {$CONFIG['allow_origin']}");
}
if(isset($CONFIG['allow_methods']) && strlen($CONFIG['allow_methods'])){
	@header("Access-Control-Allow-Methods: {$CONFIG['allow_methods']}");
}
if(isset($CONFIG['allow_headers']) && strlen($CONFIG['allow_headers'])){
	@header("Access-Control-Allow-Headers: {$CONFIG['allow_headers']}");
}
if(isset($CONFIG['allow_credentials'])){
	@header('Access-Control-Allow-Credentials:true');
}
if(!isset($CONFIG['allow_frames']) || !$CONFIG['allow_frames']){
	@header('X-Frame-Options: SAMEORIGIN');
}
else{
	//Allowing all domains is the default. Don't set the X-Frame-Options header at all if you want that.
	//@header('X-Frame-Options: ALLOWALL');
}
if(!isset($CONFIG['xss_protection']) || !$CONFIG['xss_protection']){
	@header('X-XSS-Protection: 1; mode=block');
}
//check for url_eval
if(isset($CONFIG['url_eval'])){
	$out=includePage($CONFIG['url_eval'],array());	
}
//X-Content-Type-Options
@header('X-Content-Type-Options: nosniff');
//check for valid_hosts in CONFIG settings and reject if needed
if(isset($CONFIG['valid_hosts'])){
	$valid_hosts=preg_split('/[\s\,\;]+/',strtolower(trim($CONFIG['valid_hosts'])));
    if(count($valid_hosts)){
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
}
//check for valid_uhosts in CONFIG settings and reject if needed
if(isset($CONFIG['valid_uhosts'])){
    $valid_hosts=preg_split('/[\s\,\;]+/',strtolower(trim($CONFIG['valid_uhosts'])));
    if(count($valid_hosts)){
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
}
//push file?
if(isset($_REQUEST['_pushfile'])){
	$params=array();
	if(isset($_REQUEST['-attach']) && $_REQUEST['-attach']==0){$params['-attach']=0;}
	$afile=decodeBase64($_REQUEST['_pushfile']);
	//for security purposes, only push file that are in document_root or the wasql path
	$wasqlpath=getWasqlPath();
	if(!stringContains($afile,$_SERVER['DOCUMENT_ROOT']) && !stringContains($afile,$wasqlpath)){
		echo "Error: denied push request";
		exit;
	}
 	$ok=pushFile($afile,$params);
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
//Check for ping
if(isset($_REQUEST['ping']) && count($_REQUEST)==1){
	$json=array(
		'status'=>'success',
		'time'=>number_format((microtime(true)-$starttime),3),
		'site'=>$_SERVER['HTTP_HOST'],
		'hostname'=>gethostname()
	);
	foreach($loadtimes as $k=>$v){
		$json[$k]=$v;
	}
	//if linux add loadavg
	if(!isWindows()){
		$out=cmdResults('cat /proc/loadavg');
		$json['loadavg']=$out['stdout'];
		$out=cmdResults('cat /proc/uptime');
		$json['uptime']=$out['stdout'];
	}
	
	header("Content-Type: application/json; charset=UTF-8");
	echo json_encode($json, JSON_PRETTY_PRINT);
	exit;
}
//Check for heartbeat
if(isset($_REQUEST['_heartbeat']) && $_REQUEST['_heartbeat']==1){
	echo '<heartbeat>' . time() . '</heartbeat>'."\n";
	$t=isNum($_REQUEST['t'])?$_REQUEST['t']:60;
	$div=isNum($_REQUEST['div'])?$_REQUEST['div']:'null';
	echo buildOnLoad("scheduleHeartbeat('{$div}',{$t});");
	exit;
}
//Check for websocket
if(isset($_REQUEST['wscmd_completed']) && isNum($_REQUEST['wscmd_completed']) && count($_REQUEST)==1){
	$port=$_REQUEST['wscmd_completed'];
	$ok=cmdResults("pkill -f  \"/websocketd --port={$port}\"");
	echo printValue($ok);
	exit;
}
if(isset($_REQUEST['_websocket']) && count($_REQUEST)==1){
	loadExtras('websockets');
	$params=array(
		'type'=>'access',
		'site'=>$_SERVER['HTTP_REFERER']
	);
	foreach($_SERVER as $k=>$v){
		if(preg_match('/^REMOTE_(.+)$/i',$k,$m)){
			$k=strtolower($m[1]);
    		$params[$k]=$v;
		}
	}
	$msg=json_encode($params);
	//echo $table.$msg;exit;
	$params['source']=isDBStage()?'db_stage':'db_live';
	$params['name']=$table;
	$params['icon']='icon-website w_success';
	$ok=wsSendMessage($msg,$params);
	//set correct header
	$ctype=getFileContentType("x.{$_REQUEST['_websocket']}");
	header("Content-type: {$ctype}");
	echo '';
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
if(isset($GLOBALS['HTTP_RAW_POST_DATA']) && strlen($GLOBALS['HTTP_RAW_POST_DATA']) && preg_match('/^\<\?xml\ /i',trim($GLOBALS['HTTP_RAW_POST_DATA']))){
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
		echo '<h4 class="w_danger"><span class="icon-warning w_warning"></span> Invalid email address.</h4>'."\n";
		echo ' Please enter a valid email address.'."\n";
		exit;
    }
	$ruser=getDBRecord(array('-table'=>'_users','email'=>$_REQUEST['email']));
	if(!is_array($ruser)){
		echo '<h4 class="w_danger"><span class="icon-warning w_warning w_bigger"></span> Invalid account.</h4>'."\n";
		echo "The email address you entered does not have an account with us.";
		exit;
    }
    else{
		$ruser['apikey']=encodeUserAuthCode($ruser['_id']);
		$auth=encrypt("{$ruser['username']}:{$rtime}:{$ruser['apikey']}",$salt);
		$dauth=decrypt($auth,$salt);
		//send the email.
		$to=$ruser['email'];
		$sitename=isset($CONFIG['reminder_site_name'])?$CONFIG['reminder_site_name']:$_SERVER['HTTP_HOST'];
		$fromname=isset($CONFIG['reminder_from_name'])?$CONFIG['reminder_from_name']:$_SERVER['HTTP_HOST'].' Team';
		$subject="Remind request from ".html_entity_decode($sitename);
		$message="Hi there!<p>We received a request to remind you of your {$sitename}  login information, located below: </p>";
		$message .= '<p>Your Username: '. $ruser['username']. "<br>".PHP_EOL;
		$pw=userIsEncryptedPW($ruser['password'])?userDecryptPW($ruser['password']):$ruser['password'];
		if(isset($CONFIG['authldap']) || isset($CONFIG['authldaps'])){
			$message .= 'Your password is your LDAP password'. PHP_EOL;
		}
		else{
			$pw=substr($pw,0,2);
			$message .= 'Your Password starts with "'.$pw.'"'. PHP_EOL;
		}
		$href=userGetTempAuthLink($ruser);
		$minutes=userGetTempAuthLinkTimout();
		$message .= '<p>You can also <a href="'.$href.'">click here</a> to log in automatically (link expires in '.$minutes.' minutes)</p>';
		$message .= '<p>Once you login to your account, please change your password.</p>';
		$message .= "<p>If you didn't ask to change your password, don't worry! Your password is still safe and you can ignore this email.</p>";
		$message .= "<p>Best regards,</p><p>{$fromname}</p>";
		//echo $message;exit;
		//clear out errors
		@trigger_error("");
		//attempt to send the email
		if(isset($CONFIG['smtp'])){
			if(isset($CONFIG['phpmailer'])){
            	loadExtras('phpmailer');
            	$sendopts=array('to'=>$to,'subject'=>$subject,'message'=>$message,'smtp'=>$CONFIG['smtp']);
            	if(isset($CONFIG['smtpuser'])){$sendopts['smtpuser']=$CONFIG['smtpuser'];}
				if(isset($CONFIG['smtppass'])){$sendopts['smtppass']=$CONFIG['smtppass'];}
				if(isset($CONFIG['smtpport'])){$sendopts['smtpport']=$CONFIG['smtpport'];}
				if(isset($CONFIG['email_from'])){$sendopts['from']=$CONFIG['email_from'];}
				if(isset($CONFIG['email_encrypt'])){$sendopts['encrypt']=$CONFIG['email_encrypt'];}
				if(isset($CONFIG['email_debug'])){$sendopts['maildebug']=1;}
				$ok=phpmailerSendMail($sendopts);
			}
			else{
				$ok=wasqlMail(array('to'=>$to,'subject'=>$subject,'message'=>$message));
			}
			if($ok==true || (isNum($ok) && $ok==1)){
				echo '<h4 class="w_success"><span class="icon-mark w_success w_bigger"></span> Account found!</h4>'."\n";
				echo 'We have sent your login information to ' . $ruser['email'];
				exit;
            }
            else{
				echo '<h4 class="w_danger"><span class="icon-warning w_warning w_bigger"></span> Technical failure.</h4>'."\n";
				echo 'Due to technical errors, we were unable to send you a reminder.<br />'."\n";
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
				echo '<h4 class="w_success"><span class="icon-mark w_success w_bigger"></span> Account found!</h4>'."\n";
				echo 'We have sent your login information to ' . $ruser['email'];
				exit;
            }
            else{
				echo '<h4 class="w_danger"><span class="icon-warning w_warning w_bigger"></span> Technical failure.</h4>'."\n";
				echo 'Due to technical errors, we were unable to send you a reminder.<br />'."\n";
				echo printValue($ok);
				exit;
            }
		}
    }
	echo '<h4 class="w_danger"><span class="icon-warning w_warning w_bigger"></span> Invalid request.</h4>'."\n";
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
//echo isUser().printValue($USER).isDBStage().printValue($CONFIG);exit;
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
set_error_handler("wasqlErrorHandler",E_STRICT | E_ALL);
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
		$error=xmlEncodeCDATA("User Authentication Failed for {$_REQUEST['username']}".printValue($_REQUEST));
		echo "<result>\r\n";
		echo "	<fatal_error>{$error}</fatal_error>\r\n";
		echo "</result>\r\n";
		exit;
    }
	//only allow administrators to use postedit
	if(!isAdmin()){
		$user=ucwords(xmlEncodeCDATA($USER['username']));
		header('Content-type: text/xml');
		header('X-Platform: WaSQL');
		echo xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
		$error=xmlEncodeCDATA("{$user}, you do not have sufficient rights. Sorry.");
		echo "<result>\r\n";
		echo "	<fatal_error>{$error}</fatal_error>\r\n";
		echo "</result>\r\n";
		exit;
    }
	switch(strtolower($_REQUEST['apimethod'])){
		case 'posteditsha':
			$tables=array('_pages','_templates','_models');
			if(strlen($_REQUEST['postedittables'])){
				$moretables=preg_split('/[\,\;\:]+/',$_REQUEST['postedittables']);
				foreach($moretables as $mtable){
					if(isDBTable($mtable)){
						//require name field
						$finfo=getDBFieldInfo($mtable,1);
						if(isset($finfo['name'])){
							array_push($tables,$mtable);	
						}
					}
                }
            }
            //get the fields
            $params=array();
            foreach($tables as $table){
            	$finfo=getDBFieldInfo($table,1);
            	//echo printValue($finfo);exit;
            	$fields=array();
            	foreach($finfo as $field=>$info){
            		if(isWasqlField($field)){continue;}
            		if(in_array($table,array('_pages','_templates')) && preg_match('/^(template|name|css_min|js_min)$/i',$field)){continue;}
            		if(in_array($info['_dbtype'],array('blob','text'))){
            			$fields[]=$field;
            		}
            	}
            	if(!count($fields)){continue;}
            	$params[$table]=$fields;
            }
            //echo printValue($params);exit;
			//return xml of pages and templates
			header('Content-type: application/json');
			echo postEditSha($params);
			exit;
		break;
		case 'posteditxmlfromjson':
			if(strlen($_REQUEST['json'])){
				$json=json_decode($_REQUEST['json'],true);
				if(is_array($json)){
					header('Content-type: text/xml');
					echo postEditXmlFromJson($json);
				}
				else{
					echo "Error: POSTEDITXMLFROMJSON json is invalid".printValue($_REQUEST);
				}
            }
            else{
            	echo "Error: POSTEDITXMLFROMJSON request is invalid".printValue($_REQUEST);
            }
            exit;
		break;
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
			$dbname=isset($_REQUEST['dbname'])?$_REQUEST['dbname']:'';
			$encoding=isset($_REQUEST['encoding'])?$_REQUEST['encoding']:'';
			echo postEditXml($tables,$dbname,$encoding);
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
//default to passthru
global $PASSTHRU;
$PASSTHRU=array();
$passthru=1;
if(!isset($CONFIG['passthru'])){
	$CONFIG['passthru']=1;
}
switch(strtolower($CONFIG['passthru'])){
	case 0:
	case 'false':
		$passthru=0;
	break;
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
	$_REQUEST['passthru']=$PASSTHRU=$tmp;
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
            	if(isset($rec['permalink']) && strlen($rec['permalink']) && strtolower($rec['permalink'])==strtolower($view)){
                	$PAGE=$rec;
                	$found=1;
                	break;
				}
			}
			if($found==0){
            	foreach($recs as $rec){
	            	if(isset($rec['name']) && strlen($rec['name']) && strtolower($rec['name'])==strtolower($view)){
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

if(!is_array($PAGE) && stringContains($view,'/') && $passthru==1){
	$parts=preg_split('/\/+/',$view);
	$view=array_shift($parts);
	$_REQUEST['_view']=$view;
	$_REQUEST['passthru']=$PASSTHRU=$parts;
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
			$PAGE=getDBRecord(array('-table'=>'_pages','name'=>'index'));
			if(!isset($PAGE['_id'])){
				header("HTTP/1.1 404 Not Found");
	    		abort('No page found (3)');
			}
		}
	}
	else{
		header("HTTP/1.1 404 Not Found");
    		abort('No page found');
	}
}
global $TEMPLATE;
if(is_array($PAGE) && $PAGE['_id'] > 0){
	//ignore viewfield if blank
	if(strlen(trim($PAGE[$_REQUEST['_viewfield']]))==0){
          	$_REQUEST['_viewfield']='body';
	}
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
	elseif(strtolower($_SERVER['SUBDOMAIN']) != 'www' && getDBCount(array('-table'=>'_templates','name'=>$_SERVER['SUBDOMAIN']))){
    	$tid=$_SERVER['SUBDOMAIN'];
	}
	elseif($_REQUEST['_viewfield'] != 'body'){$tid=1;}
	elseif(isset($PAGE['template']) && strlen($PAGE['template'])){$tid=$PAGE['template'];}
	elseif(isset($PAGE['_template']) && strlen($PAGE['_template'])){$tid=$PAGE['_template'];}
	if(!strlen($tid)){
		if(isset($PAGE['template']) && strlen($PAGE['template'])){$tid=$PAGE['template'];}
		elseif(isset($PAGE['_template']) && strlen($PAGE['_template'])){$tid=$PAGE['_template'];}
		else{$tid=1;}
	}
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
	//echo "HERE:::".$TEMPLATE
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
	//check for translate tags
	$htm=processTranslateTags($htm);
	echo $htm;exit;
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
	else{
		$PAGE=array('body'=>userLoginForm());
	}
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
