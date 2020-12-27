<?php
$progpath=dirname(__FILE__);
global $user_logfile;
$user_scriptname=basename(__FILE__, '.php');
$wpath=dirname( dirname(__FILE__) );
if(PHP_OS == 'WINNT' || PHP_OS == 'WIN32' || PHP_OS == 'Windows'){
	$user_logfile="{$wpath}\\logs\\{$user_scriptname}.log";
	$user_logfile=str_replace("/","\\",$user_logfile);
}
else{
   	$user_logfile="{$wpath}/logs/{$user_scriptname}.log";
}
//requires common,wasql, and database to be loaded first
//parse Server Variables
if(!isset($_SERVER['UNIQUE_HOST'])){parseEnv();}
if(!isDBTable('_users')){
	return;
}
global $USER;
global $CONFIG;
global $ConfigXml;
if(isset($CONFIG['userlog'])){
	$CONFIG['userlog_file']=$user_logfile;
}
//Check for any Wasql variables in the header and add to $_REQUEST
if(function_exists('getallheaders')){
	$headers=getallheaders();
	foreach($headers as $name => $value){
		if(preg_match('/^WaSQL\-(.+?)$/i',$name,$m)){
			$k=strtolower($m[1]);
			switch($k){
				case 'auth':$k='_auth';break;
				case 'noguid':$k='_noguid';break;
				case 'sessionid':$k='_sessionid';break;
			}
			$_REQUEST[$k]=$value;
			//echo "HERE:{$k} = {$_REQUEST[$k]}<br>".PHP_EOL;
		}
	}
	//echo printValue($_REQUEST);exit;
}
else{
	//PHP-FPM does not have the getallheaders but passes them through via HTTP_ in $_SERVER
	foreach($_SERVER as $name=>$value){
		if(preg_match('/^HTTP\_WASQL\_(.+)$/i',$name,$m)){
			$k=strtolower($m[1]);
			switch($k){
				case 'auth':$k='_auth';break;
				case 'noguid':$k='_noguid';break;
				case 'sessionid':$k='_sessionid';break;
			}
			$_REQUEST[$k]=$value;
		}
	}
}
//look for logoff
if(isset($_REQUEST['_logout']) && $_REQUEST['_logout']==1 && (!isset($_REQUEST['_login']) || $_REQUEST['_login'] != 1)){
	$_REQUEST=array();
	userLogout();
}
elseif(isset($_REQUEST['_logoff']) && $_REQUEST['_logoff']==1 && (!isset($_REQUEST['_login']) || $_REQUEST['_login'] != 1)){
	$_REQUEST=array();
	userLogout();
}
$USER=array();
//check for login request via multiple ways - _auth, _tauth, ldap, user/pass
if(isset($_REQUEST['_auth']) && $_REQUEST['_auth']==1 && isset($_REQUEST['username']) && strlen($_REQUEST['username']) && isset($_REQUEST['apikey']) && strlen($_REQUEST['apikey'])){
	//apikey code
	$rec=userDecodeApikeyAuth($_REQUEST['apikey'],$_REQUEST['username']);
	//confirm record is valid and active
	if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
		$USER=$rec;
		$ok=userSetCookie($rec);
		$ok=userLogMessage("Apikey Auth passed for user {$rec['username']}");
	}
	else{$_REQUEST['login_failed']=1;}
}
elseif(isset($_REQUEST['_auth']) && strlen($_REQUEST['_auth'])){
	//auth code
	$rec=userDecodeAuthCode($_REQUEST['_auth']);
	//confirm record is valid and active
	if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
		$USER=$rec;
		$ok=userSetCookie($rec);
		$ok=userLogMessage("Auth passed for user {$rec['username']}");
	}
	else{$_REQUEST['login_failed']=1;}	
}
elseif(isset($_REQUEST['_tauth']) && strlen($_REQUEST['_tauth'])){
	//temporary auth code
	$rec=userDecodeTempAuthCode($_REQUEST['_tauth']);
	//confirm record is valid and active
	if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
		$USER=$rec;
		$ok=userSetCookie($rec);
		$ok=userLogMessage("Temp Auth passed for user {$rec['username']}");
	}
	else{$_REQUEST['login_failed']=1;}	
}
elseif(isset($_REQUEST['_sessionid']) && strlen($_REQUEST['_sessionid'])){
	//temporary auth code
	$rec=userDecodeSessionAuthCode($_REQUEST['_sessionid']);
	//confirm record is valid and active
	if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
		$USER=$rec;
		$ok=userSetCookie($rec);
		$ok=userLogMessage("Session Auth passed for user {$rec['username']}");
	}
	else{$_REQUEST['login_failed']=1;}	
}
elseif(isset($_REQUEST['_login']) && $_REQUEST['_login']==1 && isset($_REQUEST['password']) && strlen($_REQUEST['password'])){
	//login form
	if(isset($_REQUEST['username']) && strlen($_REQUEST['username'])){
		//use ldap?
		if(isset($CONFIG['authldap']) || isset($CONFIG['authldaps'])){
			//ldap auth
			$rec=userDecodeLDAPAuth($_REQUEST['username'],$_REQUEST['password']);
			//confirm record is valid and active
			if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
				$USER=$rec;
				$ok=userSetCookie($rec);
				$ok=userLogMessage("LDAP Auth passed for user {$rec['username']}");
			}
			else{$_REQUEST['login_failed']=1;}	
		}
		else{
			//username/password auth
			$rec=userDecodeUsernameAuth($_REQUEST['username'],$_REQUEST['password']);
			//confirm record is valid and active
			 if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
				$USER=$rec;
				$ok=userSetCookie($rec);
				$ok=userLogMessage("Username Auth passed for user {$rec['username']}");
			}
			else{$_REQUEST['login_failed']=1;}
		}
	}
	elseif(isset($_REQUEST['email']) && strlen($_REQUEST['email'])){
		//email/password auth
		$rec=userDecodeEmailAuth($_REQUEST['email'],$_REQUEST['password']);
		//confirm record is valid and active
		 if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
			$USER=$rec;
			$ok=userSetCookie($rec);
			$ok=userLogMessage("Email Auth passed for user {$rec['username']}");
		}
		else{$_REQUEST['login_failed']=1;}
	}
	elseif(isset($_REQUEST['phone']) && strlen($_REQUEST['phone'])){
		//phone/password auth
		$rec=userDecodePhoneAuth($_REQUEST['phone'],$_REQUEST['password']);
		//confirm record is valid and active
		 if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
			$USER=$rec;
			$ok=userSetCookie($rec);
			$ok=userLogMessage("Phone Auth passed for user {$rec['username']}");
		}
		else{$_REQUEST['login_failed']=1;}
	}
}
elseif(isset($_COOKIE['WASQLGUID'])){
	$rec=userDecodeCookieCode($_COOKIE['WASQLGUID']);
	//confirm record is valid and active
	 if(isset($rec['_id']) && (!isset($rec['active']) || $rec['active']==1)){
		$USER=$rec;
		$ok=userSetCookie($rec);
	}
	else{$_REQUEST['login_failed']=1;}
}
global $SETTINGS;
if(isset($USER['_id'])){
	if(!isDBTable('_settings')){$ok=createWasqlTable('_settings');}
	$uid=isset($USER['_id'])?$USER['_id']:0;
	$SETTINGS=settingsValues($uid);
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userLogMessage($msg){
	global $CONFIG;
	if(!isset($CONFIG['userlog'])){return;}
	if($CONFIG['userlog'] != 1){return;}
	global $user_logfile;
	$ctime=time();
	$cdate=date('Y-m-d h:i:s',$ctime);
	$msg="{$cdate},{$ctime},{$msg}".PHP_EOL;
	//echo $msg;
	if(!file_exists($user_logfile) || filesize($user_logfile) > 1000000 ){
        setFileContents($user_logfile,$msg);
    }
    else{
        appendFileContents($user_logfile,$msg);
    }
	return;
}
//****************************************************************************************************************************//
//---------- begin function userGetApikey ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetApikey($rec=array()){
	global $CONFIG;
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}

	$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$auth=array(
		encodeBase64(encrypt($CONFIG['dbname'],$cryptkey)),
		encodeBase64(encrypt($rec['username'],$cryptkey)),
	    encodeBase64(encrypt($pw,$cryptkey))
	    );
	return encodeBase64(implode(':',$auth));
}
//---------- begin function userGetApikey ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
* @infor    - returns dbname,username,password

*/
function userDecodeApikey($apikey,$rec=array()){
	global $CONFIG;
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec,1);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){
		$ok=userLogMessage("userDecodeApikey Failed - No rec.");
		return null;
	}
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$str=decodeBase64($apikey);
	$parts=preg_split('/\:/',$str,3);
	return array(
		'dbname'=>decrypt(decodeBase64($parts[0]),$cryptkey),
		'username'=>decrypt(decodeBase64($parts[1]),$cryptkey),
		'password'=>decrypt(decodeBase64($parts[2]),$cryptkey)
	);
}
//---------- begin function userDecodeApikeyAuth ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeApikeyAuth($apikey,$user){
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'username'=>addslashes($user)));
	if(!isset($rec['_id'])){return null;}
	$info=userDecodeApikey($apikey,$rec);
	//echo $user.printValue($info);exit;
	//make sure username and $info['username'] match
	if(!isset($info['username']) || $user != $info['username']){
		$ok=userLogMessage("userDecodeApikeyAuth Failed - {$user}.");
		return null;
	}
	return $rec;
}
//---------- begin function userGetAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetAuthCode($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){
		$ok=userLogMessage("userGetAuthCode Failed - no rec.");
		return null;
	}
	$rec['apikey']=userGetApikey($rec);
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$auth=encrypt("{$rec['username']}:{$rec['apikey']}",$cryptkey);
	return base64_encode("{$rec['_id']}.{$auth}");
}
//---------- begin function userGetAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeAuthCode($authcode){
	//decode authcode to id.auth
	$str=decodeBase64($authcode);
	list($id,$auth)=preg_split('/\./',$str,2);
	if(!isNum($id)){
		$ok=userLogMessage("userDecodeAuthCode Failed - id is not a num.");
		return null;
	}
	//get user record with that id
	$rec=getDBRecordById('_users',$id,1);
	if(!isset($rec['_id'])){
		$ok=userLogMessage("userDecodeAuthCode Failed - no rec.");
		return null;
	}
	//decode username:apikey
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$str=decrypt($auth,$cryptkey);
	list($user,$apikey)=preg_split('/\:/',$str,2);
	if($rec['username'] != $user){
		$ok=userLogMessage("userDecodeAuthCode Failed - user {$user}.");
		return null;
	}
	//decode apikey
	$info=userDecodeApikey($apikey,$rec);
	//make sure username and $info['username'] match
	if(!isset($info['username']) || $user != $info['username']){
		$ok=userLogMessage("userDecodeAuthCode Failed - user2 {$user}.");
		return null;
	}
	return $rec;
}
//---------- begin function userGetTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetTempAuthCode($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}
	$rec['apikey']=userGetApikey($rec);
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$rtime=time();	
	$auth=encrypt("{$rec['username']}:{$rtime}:{$rec['apikey']}",$cryptkey);
	return base64_encode("{$rec['_id']}.{$auth}");
}
//---------- begin function userDecodeTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeTempAuthCode($authcode){
	global $CONFIG;
	//decode authcode to id.auth
	$str=decodeBase64($authcode);
	list($id,$auth)=preg_split('/\./',$str,2);
	if(!isNum($id)){
		$ok=userLogMessage("userDecodeTempAuthCode Failed - id is not a number.");
		return null;
	}
	//get user record with that id
	$rec=getDBRecordById('_users',$id,1);
	if(!isset($rec['_id'])){
		$ok=userLogMessage("userDecodeTempAuthCode Failed - no rec.");
		return null;
	}
	//decode auth to username:ctime:apikey
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$str=decrypt($auth,$cryptkey);
	list($user,$ctime,$apikey)=preg_split('/\:/',$str,3);
	if($rec['username'] != $user){
		$ok=userLogMessage("userDecodeTempAuthCode Failed - user {$user}.");
		return null;
	}
	//decode apikey
	$info=userDecodeApikey($apikey,$rec);
	//make sure username and $info['username'] match
	if(!isset($info['username']) || $user != $info['username']){
		$ok=userLogMessage("userDecodeTempAuthCode Failed - user2 {$user}.");
		return null;
	}
	//make sure the atime is within the allowed time frame - 30 minutes
	$minutes=isset($CONFIG['auth_timeout'])?$CONFIG['auth_timeout']:30;
	$seconds=$minutes*60;
	$elapsed=time()-$ctime;
	if($elapsed > $seconds){
		$ok=userLogMessage("userDecodeTempAuthCode Failed - time elapsed.");
		return null;
	}
	return $rec;
}
//---------- begin function userGetTempAuthLink ----
/**
* get a temporary auth link that you can email to a user.
* @return string
* @usage
*	global $USER;
*	$temp_auth_link=userGetTempAuthLink($USER); 
*	$auth_link_timeout=userGetTempAuthLinkTimout();
*/
function userGetTempAuthLink($rec=array(),$pagename=''){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}
	//make sure pagename starts with a slash
	if(strlen($pagename) && !preg_match('/^\//',$pagename)){
		$pagename="/".$pagename;
	}
	$tauth=userGetTempAuthCode($rec);
	$http=isSSL()?'https://':'http://';
	$href=$http.$_SERVER['HTTP_HOST'].$pagename.'?_tauth='.$tauth;
	return $href;
}
//---------- begin function userGetTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetSessionAuthCode($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}
	$rec['apikey']=userGetApikey($rec);
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$rtime=time();	
	$auth=encrypt("{$rec['_id']}:{$rtime}:{$rec['apikey']}",$cryptkey);
	return base64_encode("{$rec['_id']}.{$auth}");
}
//---------- begin function userDecodeTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeSessionAuthCode($authcode){
	global $CONFIG;
	//decode authcode to id.auth
	$str=decodeBase64($authcode);
	list($id,$auth)=preg_split('/\./',$str,2);
	if(!isNum($id)){
		$ok=userLogMessage("userDecodeSessionAuthCode Failed - id is not a number.");
		return null;
	}
	//get user record with that id
	$rec=getDBRecordById('_users',$id,1);
	if(!isset($rec['_id'])){
		$ok=userLogMessage("userDecodeSessionAuthCode Failed - no rec.");
		return null;
	}
	//decode auth to username:ctime:apikey
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$str=decrypt($auth,$cryptkey);
	list($rid,$ctime,$apikey)=preg_split('/\:/',$str,3);
	if($rec['_id'] != $rid){
		$ok=userLogMessage("userDecodeSessionAuthCode Failed - id {$rid}.");
		return null;
	}
	//decode apikey
	$info=userDecodeApikey($apikey,$rec);
	//make sure username and $info['username'] match
	if(!isset($info['username']) || $rec['username'] != $info['username']){
		$ok=userLogMessage("userDecodeSessionAuthCode Failed - user {$info['username']}.");
		return null;
	}
	//make sure the atime is within the allowed time frame - 10 minutes
	$minutes=isset($CONFIG['session_timeout'])?$CONFIG['session_timeout']:10;
	$seconds=$minutes*60;
	$elapsed=time()-$ctime;
	if($elapsed > $seconds){
		$ok=userLogMessage("userDecodeSessionAuthCode Failed - time elapsed.");
		return null;
	}
	return $rec;
}
//---------- begin function userDecodeLDAPAuth ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeLDAPAuth($user,$pass){
	global $CONFIG;
	loadExtras('ldap');
 	$host=isset($CONFIG['authldap'])?$CONFIG['authldap']:$CONFIG['authldaps'];
 	if(!strlen($host)){
 		$ok=userLogMessage("userDecodeLDAPAuth Failed -no host. user {$user}");
 		return null;
 	}
 	$authopts=array(
		'-host'		=> $host,
		'-username'	=> $_REQUEST['username'],
		'-password'	=> $_REQUEST['password']
	);
	if(isset($CONFIG['authldap_domain'])){
    	$authopts['-domain']=$CONFIG['authldap_domain'];
	}
	if(isset($CONFIG['authldap_checkmemberof'])){
    	$authopts['-checkmemberof']=$CONFIG['authldap_checkmemberof'];
	}
 	$ldap=ldapAuth($authopts);
 	//confirm valid ldap record
 	if(!isset($ldap['username'])){
 		$ok=userLogMessage("userDecodeLDAPAuth Failed -ldap auth failed for {$user}");
 		return null;
 	}
   	$finfo=getDBFieldInfo('_users');
   	//convert some fields to JSON if the field type is json
   	foreach($finfo as $field=>$info){
   		if(!isset($ldap[$field])){continue;}
   		if($info['_dbtype']=='json'){
        	switch(strtolower($field)){
        		case 'memberof_cn':
        		case 'memberof_ou':
        		case 'distinguishedname_ou':
        		case 'objectcategory':
        		case 'objectclass':
        			$ldap[$field]=json_encode(preg_split('/\,/',$ldap[$field]));
        		break;
        	}
        }
    }
   	$admins=array();
    if(isset($CONFIG['authldap_admin'])){
       	$admins=preg_split('/[\,\;\:]+/',$CONFIG['authldap_admin']);
	}
  	//add or update this user record
  	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"username='{$ldap['username']}' or email='{$ldap['email']}'"));
  	if(isset($rec['_id'])){
       	$changes=array();
       	if(isset($ldap['password']) && isset($rec['password'])){
			$ldap['password']=userEncryptPW($ldap['password']);
		}
       	foreach($finfo as $field=>$info){
            if(isset($ldap[$field]) && sha1($rec[$field]) != sha1($ldap[$field])){
                $changes[$field]=$rec[$field]=$ldap[$field];
			}
			elseif(isset($_REQUEST[$field]) && sha1($rec[$field]) != sha1($_REQUEST[$field])){
                $changes[$field]=$rec[$field]=$_REQUEST[$field];

			}
		}
		//set utype to 0 for admins
		if(in_array($rec['username'],$admins) || in_array($rec['email'],$admins)){
			if($rec['utype'] != 0){
				$changes['utype']=$rec['utype']=0;
			}
		}
		elseif($rec['utype'] == 0){
			$changes['utype']=$rec['utype']=1;
		}
		if(count($changes)){
            $ok=editDBRecordById('_users',$rec['_id'],$changes);
            if(is_array($ok)){$ok=json_encode($ok);}
            $ok=userLogMessage("userDecodeLDAPAuth editDBRecordById {$ok}");
		}
		return $rec;
	}
	else{
       	$ldap['-table']='_users';
       	if(in_array($ldap['username'],$admins) || in_array($ldap['email'],$admins)){
			$ldap['utype']= 0;
		}
		else{$ldap['utype']=1;}
		//add extra fields
		foreach($finfo as $field=>$info){
           if(isset($_REQUEST[$field]) && !isset($ldap[$field])){
                $ldap[$field]=$_REQUEST[$field];
			}
		}
       	$id=addDBRecord($ldap);
       	if(isNum($id)){
       		$rec=getDBRecordById('_users',$id,1);
       		$ok=userLogMessage("userDecodeLDAPAuth addDBRecord {$id}");
       		return $rec;
		}
		else{
			if(is_array($id)){$id=json_encode($id);}
			$ok=userLogMessage("userDecodeLDAPAuth addDBRecord {$id}");
		}
	}
	$ok=userLogMessage("userDecodeLDAPAuth Failed - unknown reason - user {$user}");
	return null;
}
//---------- begin function userDecodeUsernameAuth ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeUsernameAuth($user,$pass){
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'username'=>addslashes($user)));
	if(!isset($rec['_id'])){
		$ok=userLogMessage("userDecodeUsernameAuth Failed - no rec for user {$user}");
		return null;
	}
	if(userIsEncryptedPW($rec['password'])){
		$pw=userEncryptPW(addslashes($pass));
		if($pw != $rec['password']){
			$ok=userLogMessage("userDecodeUsernameAuth Failed - password failed for user {$user}");
			return null;
		}
	}
	else{
		if($pass != $rec['password']){
			$ok=userLogMessage("userDecodeUsernameAuth Failed - password2 failed for user {$user}");
			return null;
		}
	}	
	return $rec;
}
//---------- begin function userDecodeEmailAuth ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeEmailAuth($email,$pass){
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'email'=>addslashes($email)));
	if(!isset($rec['_id'])){
		$ok=userLogMessage("userDecodeEmailAuth Failed - no rec for email {$email}");
		return null;
	}
	if(userIsEncryptedPW($rec['password'])){
		$pw=userEncryptPW(addslashes($pass));
		if($pw != $rec['password']){
			$ok=userLogMessage("userDecodeEmailAuth Failed - password failed for email {$email}");
			return null;
		}
	}
	else{
		if($pass != $rec['password']){
			$ok=userLogMessage("userDecodeEmailAuth Failed - password failed for email {$email}");
			return null;
		}
	}	
	return $rec;
}
//---------- begin function userDecodeEmailAuth ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodePhoneAuth($phone,$pass){
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'phone'=>addslashes($phone)));
	if(!isset($rec['_id'])){
		$_REQUEST['_login_error']="Invalid Account";
		$ok=userLogMessage("userDecodePhoneAuth Failed - no rec for phone {$phone}");
		return null;
	}
	if(userIsEncryptedPW($rec['password'])){
		$pw=userEncryptPW(addslashes($pass));
		if($pw != $rec['password']){
			$_REQUEST['_login_error']="Phone Auth Check 1 Failed";
			$ok=userLogMessage("userDecodePhoneAuth Failed - password failed for phone {$phone}");
			return null;
		}
	}
	else{
		if($pass != $rec['password']){
			$_REQUEST['_login_error']="Phone Auth Check 2 Failed".printValue($rec);
			$ok=userLogMessage("userDecodePhoneAuth Failed - password failed for phone {$phone}");
			return null;
		}
	}	
	return $rec;
}
//---------- begin function userGetTempAuthLinkTimout ----
/**
* gets the number of minutes a temporary auth link is valid for. Set by auth_timeout in config.xml.
* @return number
* @usage
*	global $USER;
*	$temp_auth_link=userGetTempAuthLink($USER); 
*	$auth_link_timeout=userGetTempAuthLinkTimout();
*/
function userGetTempAuthLinkTimout(){
	global $CONFIG;
	$minutes=isset($CONFIG['auth_timeout'])?$CONFIG['auth_timeout']:30;
	return $minutes;
}
function userGetTimeout($type,$rec=array()){
	switch(strtolower($type)){
		case 'auth':
		case 'auth_timeout':
			if(isset($rec['auth_timeout']) && strlen($rec['auth_timeout'])){
				$timeout=$rec['auth_timeout'];
			}
			elseif(isset($CONFIG['auth_timeout']) && strlen($CONFIG['auth_timeout'])){
				$timeout=$CONFIG['auth_timeout'];
			}
			else{
				$timeout='1 year';
			}
		break;
		case 'tauth':
		case 'tauth_timeout':
			if(isset($rec['tauth_timeout']) && strlen($rec['tauth_timeout'])){
				$timeout=$rec['tauth_timeout'];
			}
			elseif(isset($CONFIG['tauth_timeout']) && strlen($CONFIG['tauth_timeout'])){
				$timeout=$CONFIG['tauth_timeout'];
			}
			else{
				$timeout='30 min';
			}
		break;
		case 'sessionid':
		case 'sessionid_timeout':
			if(isset($rec['sessionid_timeout']) && strlen($rec['sessionid_timeout'])){
				$timeout=$rec['sessionid_timeout'];
			}
			elseif(isset($CONFIG['sessionid_timeout']) && strlen($CONFIG['sessionid_timeout'])){
				$timeout=$CONFIG['sessionid_timeout'];
			}
			else{
				$timeout='10 min';
			}
		break;
		case 'apikey':
		case 'apikey_timeout':
			if(isset($rec['apikey_timeout']) && strlen($rec['apikey_timeout'])){
				$timeout=$rec['apikey_timeout'];
			}
			elseif(isset($CONFIG['apikey_timeout']) && strlen($CONFIG['apikey_timeout'])){
				$timeout=$CONFIG['apikey_timeout'];
			}
			else{
				$timeout='1 year';
			}
		break;
		case 'ldap':
		case 'ldap_timeout':
			if(isset($rec['ldap_timeout']) && strlen($rec['ldap_timeout'])){
				$timeout=$rec['ldap_timeout'];
			}
			elseif(isset($CONFIG['ldap_timeout']) && strlen($CONFIG['ldap_timeout'])){
				$timeout=$CONFIG['ldap_timeout'];
			}
			else{
				$timeout='1 year';
			}
		break;
		default:
			$timeout='1 year';
		break;
	}
	$parts=preg_split('/\ /',$timeout,2);
	if(count($parts)==2){
		switch(strtolower(trim($parts[1]))){
			case 'year':
			case 'years':
				$days=(integer)$parts[0]*365;
				$seconds=$days*86400;
			break;
			case 'month':
			case 'months':
				$days=(integer)$parts[0]*30;
				$seconds=$days*86400;
			break;
			case 'day':
			case 'days':
				$days=(integer)$parts[0];
				$seconds=$days*86400;
			break;
			case 'hour':
			case 'hours':
				$hours=(integer)$parts[0];
				$seconds=$hours*3600;
			break;
			case 'minute':
			case 'minutes':
				$minutes=(integer)$parts[0];
				$seconds=$minutes*60;
			break;
			default:
				$days=365;
				$seconds=$days*86400;
			break;
		}
	}
	return $seconds;
}
//---------- begin function userGetTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetCookieCode($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}
	$rec['apikey']=userGetApikey($rec);
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$ctime=time();	
	$auth=encrypt("{$rec['username']}:{$ctime}:{$rec['apikey']}",$cryptkey);
	return base64_encode("{$rec['_id']}.{$auth}");
}
//---------- begin function userDecodeTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userDecodeCookieCode($code){
	global $CONFIG;
	//decode authcode to id.auth
	$str=decodeBase64($code);
	list($id,$auth)=preg_split('/\./',$str,2);
	if(!isNum($id)){return null;}
	//get user record with that id
	$rec=getDBRecordById('_users',$id,1);
	if(!isset($rec['_id'])){return null;}
	//decode auth to username:ctime:apikey
	$cryptkey=userGetUserCryptKey($rec['_id']);
	$str=decrypt($auth,$cryptkey);
	list($user,$ctime,$apikey)=preg_split('/\:/',$str,3);
	if($rec['username'] != $user){return null;}
	//decode apikey
	$info=userDecodeApikey($apikey,$rec);
	//make sure username and $info['username'] match
	if(!isset($info['username']) || $user != $info['username']){
		return null;
	}
	$login_timeout='';
	//make sure the atime is within the allowed time frame (days) - check for login_timeout  (2 days,  1 minute, 5 min, 3 hours, 1 hrs)
	if(isset($rec['login_timeout']) && strlen($rec['login_timeout'])){
		$login_timeout=$rec['login_timeout'];
	}
	elseif(isset($CONFIG['login_timeout']) && isNum($CONFIG['login_timeout'])){
		$login_timeout=$CONFIG['login_timeout'];
	}
	if(strlen($login_timeout)){
		$parts=preg_split('/\ /',$login_timeout,2);
		if(count($parts)==2){
			switch(strtolower(trim($parts[1]))){
				case 'year':
				case 'years':
					$days=(integer)$parts[0]*365;
					$seconds=$days*86400;
				break;
				case 'month':
				case 'months':
					$days=(integer)$parts[0]*30;
					$seconds=$days*86400;
				break;
				case 'day':
				case 'days':
					$days=(integer)$parts[0];
					$seconds=$days*86400;
				break;
				case 'hour':
				case 'hours':
					$hours=(integer)$parts[0];
					$seconds=$hours*3600;
				break;
				case 'minute':
				case 'minutes':
					$minutes=(integer)$parts[0];
					$seconds=$minutes*60;
				break;
				default:
					$days=365;
					$seconds=$days*86400;
				break;
			}
		}
		$elapsed=time()-$ctime;
		if($elapsed > $seconds){
			return null;
		}
	}
	return $rec;
}
//---------- begin function userDecodeTempAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userSetCookie($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return null;}
	//remove old cookies
	if(isset($_COOKIE['GUID'])){
		commonSetCookie('GUID', null, -1, '/');
	}
	if(isset($_COOKIE['WASQLRP'])){
		commonSetCookie('WASQLRP', null, -1, '/');
	}
	if(isset($_COOKIE['WASQL_ERROR'])){
		commonSetCookie('WASQL_ERROR', null, -1, '/');
	}
	$code=userGetCookieCode($rec);
	commonSetCookie("WASQLGUID", $code);
	$_SERVER['WASQLGUID']=$code;
	$USER=userSetUserInfo($rec);
	//echo $code.printValue($USER);exit;
	//look for login trigger
	if(function_exists("_usersLogin") && isUser()){
    	$ok=call_user_func("_usersLogin",$USER);
	}
	return $code;
}
//---------- begin function userSetUserInfo ----
/**
* Updates the User information
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userSetUserInfo($rec=array()){
	global $USER;
	if(!is_array($rec) && isNum($rec)){
		if($rec==0 || $rec == $USER['_id']){
			$rec=$USER;
		}
		else{
			$rec=getDBRecordById('_users',$rec);
		}
	}
	if(!isset($rec['_id']) || !isNum($rec['_id'])){return 'No ID';}
	$changes=array();
	/* update access date */
	$changes['_adate']=$rec['_adate']=date("Y-m-d H:i:s");
	$finfo=getDBFieldInfo("_users");
	if(!isset($finfo['_sid'])){
		$query="ALTER TABLE _users ADD _sid varchar(150) NULL;";
		$ok=executeSQL($query);
    }
	$changes['_sid']=$rec['_sid']=$rec['_sessionid']=session_id();

	if($finfo['password']['_dblength'] != 255 && $finfo['password']['_dbtype'] != 'text'){
		//increase password length
		$ok=@databaseQuery('alter table _users modify password VARCHAR(255)');
	}
	if(!userIsEncryptedPW($USER['password'])){
		$changes['password']=$rec['password']=userEncryptPW($USER['password']);
	}
	if(isset($finfo['_aip'])){
		$changes['_aip']=$rec['_aip']=$_SERVER['REMOTE_ADDR'];
	}
	//check for fields that match a SERVER Variable
	foreach($_SERVER as $k=>$v){
		$lk=strtolower($k);
		if(isset($finfo[$lk]) && !isset($opts[$lk]) && !is_array($v)){
			$changes[$lk]=$rec[$lk]=$v;
		}
	}
	//update the user record
	if(count($changes)){
		$ok=editDBRecordById('_users',$rec['_id'],$changes);
	}
	//_auth
	$rec['apikey']=userGetApikey($rec);
	$rec['_auth']=userGetAuthCode($rec);
	$rec['_tauth']=userGetTempAuthCode($rec);
	$rec['_sessionid']=userGetSessionAuthCode($rec);
    /* replace the user password with stars */
	//$USER['password']=preg_replace('/./','*',$USER['password']);
	ksort($rec);
	return $rec;
}

/************************************************************************************/
/************************************************************************************/
function userSetWaSQLGUID(){
	global $USER;
	global $CONFIG;
	if(isset($_COOKIE['GUID'])){
		commonSetCookie('GUID', null, -1, '/');
	}
	if(isset($_COOKIE['WASQLRP'])){
		commonSetCookie('WASQLRP', null, -1, '/');
	}
	if(isset($_COOKIE['WASQL_ERROR'])){
		commonSetCookie('WASQL_ERROR', null, -1, '/');
	}
	if(!isset($USER['_id']) || !isNum($USER['_id'])){return false;}
	$rec=getDBRecord(array('-table'=>'_users','_id'=>$USER['_id'],'-fields'=>'_id,username,password'));
	if(!isset($rec['_id'])){
    	commonSetCookie("WASQL_ERROR", "USER");
    	return false;
    }
	$guid=userEncryptGUID($rec['_id'],$rec['username'],$rec['password']);
	commonSetCookie("WASQLGUID", $guid);
	$_SERVER['WASQLGUID']=$guid;
	setUserInfo();
	return $guid;
}

function userAuthorizeWASQLGUID(){
	if(!isset($_COOKIE['WASQLGUID']) || !strlen($_COOKIE['WASQLGUID'])){
		return false;
	}
	$guid=urldecode($_COOKIE['WASQLGUID']);
    if(preg_match('/^([0-9]+)/',base64_decode($guid),$m)){
    	$opts=array('-table'=>'_users','_id'=>$m[1],'-relate'=>1);
    	$rec=getDBRecord($opts);
    }
    if(!isset($rec['_id'])){
    	commonSetCookie("WASQL_ERROR", "REC");
    	return false;
    }
    $checkguid=userEncryptGUID($rec['_id'],$rec['username'],$rec['password']);
    if($checkguid === $guid){
    	$_SERVER['WASQLGUID']=$guid;
    	return $rec;
    }
    commonSetCookie("WASQL_ERROR", $checkguid);
    return false;
}
//---------- begin function userEncryptPW ----
/**
* returns a secure encrypted password using WaSQL's security algorythm.
* @return string
* @param string $pw - password to be encrypted
* @usage
* 	$securePassword = userEncryptPW($originalPassword);
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function userEncryptPW($pw){
	$salt=userEncryptSalt();
	$pw_enc=encrypt($pw,$salt);
	$pw_enc='~!'.$pw_enc.'^-';
	return $pw_enc;
}
//---------- begin function userEncryptGUID ----
/**
* returns a secure encrypted password using WaSQL's security algorythm.
* @return string
* @param string $pw - password to be encrypted
* @usage
* 	$securePassword = userEncryptPW($originalPassword);
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function userEncryptGUID($i,$u,$p){
	$u=strrev(base64_encode($u));
	$u=preg_replace('/[\=\+\ ]+/','',$u);
	$p=strrev(base64_encode($p));
	$p=preg_replace('/[\=\+\ ]+/','',$p);
	$str=strrev(base64_encode("{$u}{$p}"));
	$str=preg_replace('/[\=\+\ ]+/','',$str);
	$str=base64_encode("{$i}l02".$str);
	$str=preg_replace('/[\=\+\ ]+/','',$str);
	return $str;
}
//---------- begin function userEncryptPassEnc ----
/**
* returns a secure encrypted password using WaSQL's security algorythm.
* @return string
* @param string $pw - password to be encrypted
* @usage
* 	$securePassword = userEncryptPW($originalPassword);
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function userEncryptPassEnc($p){
	$salt='1A2K66RX94lobdRBRNzp3WBS9RQDzrIRXvIrtxmmmHXysj8cfJQVx89dyR8AaOrurFcjpxjN3BJabcCh0VNbtnHB3vMxUUOyEsf5G39A02-2y2fXyonJJnRGGucl5';
	return base64_encode(crypt($p,$salt));
}

//**********************************************************************************************************************//

//---------- begin function userDecryptPW ----
/**
* returns the original password that was put in and secured by WaSQL's security algorythm.
* @return string
* @param string $pw_enc - encrypted password needing to be decrypted
* @usage
*	$originalPassword = userDecryptPW($securePassword);
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function userDecryptPW($pw_enc){
	$pw_enc=preg_replace('/^\~\!/','',$pw_enc);
	$pw_enc=preg_replace('/\^\-$/','',$pw_enc);
	$salt=userEncryptSalt();
	return decrypt($pw_enc,$salt);
}

//---------- begin function userEncryptPW ----
/**
* returns a base salt component for encrypting wasql passwords.
*         - There shouldn't be any need to use for default WaSQL functionality as unserEncryptPW will add salt.
* @return string
* @usage $pw_enc = encrypt($pw, $salt);
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history
*        - bbarten 2014-01-02 added documentation
*/
function userEncryptSalt(){return 'W8SQLU53RPa55';}

//---------- begin function userIsEncryptedPW ----
/**
* returns whether a string is a WaSQL encrypted password
* @return boolean
* @param string $pw - string to be tested
* @usage
* 	if(userIsEncryptedPW($pw)){
*		$pw = userDecryptPW($pw);
*	}
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function userIsEncryptedPW($pw=''){
	if(preg_match('/^\~\!/',$pw) && preg_match('/\^\-$/',$pw)){return true;}
	return false;
}
//---------- begin function userLoginAs ----
/**
* @describe login as another user - must be admin
* @param str - user id or username of the user to login as
* @return boolean 
* @usage 
*	$ok=userLoginAs(11);
*	$ok=userLoginAs('sammy');
* @author slloyd
*/
function userLoginAs($id=0){
	if(!isAdmin()){return false;}
	if(!strlen($id) || $id==0){return false;}
	if(isNum($id) && $id != 0){
		$rec=getDBRecordById('_users',$id);
	}
	else{
		$rec=getDBRecord(array(
			'-table'=>'_users',
			'username'=>$id
		));
	}
	if(!isset($rec['_id'])){return false;}
	global $USER;
	$USER=$rec;
    userSetWaSQLGUID();
    return true;
}
//---------- begin function setUserInfo ----
/**
* Updates the User information in the database  with informationn for the current user.
* @return boolean|void
* @param string $guid - not used
* @usage
*	if(setUserInfo()) === false){return 'Error: User Info was not updated';};
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 updated documentation
*/
function setUserInfo(){
	global $USER;
	if(!isUser()){return false;}
	//set WaSQL_PWE
	/* update access date */
	$adate=date("Y-m-d H:i:s");
	$finfo=getDBFieldInfo("_users");
	if(!isset($finfo['_sid'])){
		$query="ALTER TABLE _users ADD _sid varchar(150) NULL;";
		$ok=executeSQL($query);
    }
	$sessionID=session_id();
	$opts=array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'_adate'=>$adate,'_sid'=>$sessionID);

	if($finfo['password']['_dblength'] != 255 && $finfo['password']['_dbtype'] != 'text'){
		//increase password length
		$ok=@databaseQuery('alter table _users modify password VARCHAR(255)');
	}
	if(!userIsEncryptedPW($USER['password'])){
		$opts['password']=userEncryptPW($USER['password']);
	}
	if(isset($finfo['_aip'])){
		$opts['_aip']=$_SERVER['REMOTE_ADDR'];
		$USER['_aip']=$_SERVER['REMOTE_ADDR'];
	}
	//check for fields that match a SERVER Variable
	foreach($_SERVER as $k=>$v){
		$lk=strtolower($k);
		if(isset($finfo[$lk]) && !isset($opts[$lk]) && !is_array($v)){
			$opts[$lk]=$v;
			$USER[$lk]=$v;
		}
	}
	//_auth
	$USER['apikey']=encodeUserAuthCode();
	$auth=encrypt("{$USER['username']}:{$USER['apikey']}",$USER['_id']);
	$USER['_auth']="{$USER['_id']}.{$auth}";
	//_sessionid
	$rtime=time();
	$rnum=rand(1,9);
	$salt="Session{$USER['_id']}{$rnum}tlaS";
	//to make the session  as secure as possible
	if(isEven($rnum)){
		$string="{$USER['username']}:{$rtime}:{$USER['apikey']}";
	}
	else{
		$string="{$rtime}:{$USER['username']}:{$USER['apikey']}";
	}
	$auth=encrypt($string,$salt);
	$USER['_sessionid']=base64_encode("{$USER['_id']}{$rnum}.{$auth}");
	$USER['_adate']=$adate;
    /* replace the user password with stars */
	//$USER['password']=preg_replace('/./','*',$USER['password']);
	ksort($USER);
	/* logout? */
	if(isset($_REQUEST['_logout']) && $_REQUEST['_logout']==1 && (!isset($_REQUEST['_login']) || $_REQUEST['_login'] != 1)){
		userLogout();
	}
	else{
		$ok=editDBRecord($opts);
	}
}

//---------- begin function userLogout ----
/**
* Logs the current user out.
* @return boolean
* @usage
*	userLogout(); //Logs the current user out.
* @author slloyd
* @history - bbarten 2014-01-02 updated documentation
*/
function userLogout(){
	global $USER;
	//look for logout trigger
	if(function_exists("_usersLogout") && isUser()){
    	$ok=call_user_func("_usersLogout",$USER);
	}
	$USER=array();
	unset($_SESSION['authcode']);
	unset($_SESSION['authkey']);
	if(isset($_COOKIE['GUID'])){
		commonSetCookie('GUID', null, -1, '/');
	}
	if(isset($_COOKIE['WASQLRP'])){
		commonSetCookie('WASQLRP', null, -1, '/');
	}
	if(isset($_COOKIE['WASQLCP'])){
		commonSetCookie('WASQLCP', null, -1, '/');
	}
	if(isset($_COOKIE['WASQLGUID'])){
		unset($_SERVER['WASQLGUID']);
		unset($_SESSION['WASQLGUID']);
		unset($_COOKIE['WASQLGUID']);
		//setcookie("WASQLGUID", "", time()-3600);
		commonSetCookie('WASQLGUID', null, -1, '/');
	}
	return true;
}

//---------- begin function getUserInfo ----
/**
* returns an array with User Information
* @return array
*	['type'] string                    displays the user type of the user. defaults are Administrator and User
*	['username'] string                displays the username of the user.
*	['icon'] string                    displays the URL of the icon
*	['create_timestamp'] int        displays the timestamp of when the user was created.
*	['created_time'] string            displays the created timestamp in a human readable format
*	['created_seconds']    int            displays the total seconds that have elapsed from the created time to now.
*	['created_age']    string             displays the total time that have elapsed from from the created time to now in a human readable format.
*	['accessed'] string                displays the last time the account was accessed by the user in db datetime format.
*	['accessed_timestamp'] int        displays the timestamp of the time the account was last accessed.
*	['accessed_time'] string        displays the last time the account was accessed by the user in a human readable format.
*	['status'] string                 displays the status of the account in human readable format.
*	['accessed_seconds'] int        displays how long ago the account was accessed in seconds.
*	['accessed_age'] string            displays how long ago the account was accessed in a human readable format.
* @param int $cuser - the userid of the user to be looked up.
* @param int $size    - The size of the icon to be displayed.
* @usage
*	$userArray = getUserInfo(1,16);
*	$username = $userArray['username'];
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 updated documentation
*/
function getUserInfo($cuser,$size=16){
	if(!is_array($cuser) && isNum($cuser)){
		$cuser=getDBRecord(array('-table'=>'_users','_id'=>$cuser));
    	}
    $utypes=mapDBDvalsToTvals("_users","utype");
    $info=array(
    	'type'=>isset($utypes[$cuser['utype']])?$utypes[$cuser['utype']]:'unknown',
    	'username'=>$cuser['username']
		);
	//set user icon based on admin or not
	if($cuser['utype']==0){
		$info['class']='icon-user-admin w_danger w_big';
    }
    else{
		$info['class']='icon-user w_big w_grey';
    }
    $nowstamp=time();
    $timestamp=$nowstamp-300;
    if(!is_array($cuser)){
		//no such user?
		$info['status']="No such user";
		return $info;
		}
	if(strlen($cuser['_cdate'])){
		$info['created_timestamp']=strtotime($cuser['_cdate']);
		$info['created_time']=date("D M jS Y g:i a",$info['created_timestamp']);
		$info['created_seconds']=$nowstamp - $info['created_timestamp'];
		$info['created_age']=verboseTime($info['created_seconds']);
		if(!strlen(trim($info['created_age']))){$info['created_age']="0 seconds";}
		}
	//inactive?
    if(isset($cuser['active']) && $cuser['active']==0){
		//inactive
		$info['status']="Inactive";
		//set user icon based on admin or not
		if($cuser['utype']==0){
			$info['class']='icon-user-admin w_info';
	    }
	    else{
			$info['class']='icon-user w_lgrey';
	    }

		return $info;
		}
	//never logged in
    if(!strlen($cuser['_adate'])){
		//Never logged In?
		$info['status']="Never logged in";
		if($cuser['utype']==0){
			$info['class']='icon-user-admin w_warning';
	    }
	    else{
			$info['class']='icon-user w_danger';
	    }
        //$info['status'] .= " - Created {$info['created_age']} ago on {$info['created_time']}";
		return $info;
		}
	//age of user
	$info['accessed']=$cuser['_adate'];
	$info['accessed_timestamp']=strtotime($cuser['_adate']);
	$info['accessed_time']=date("D M jS Y g:i a",$info['accessed_timestamp']);

	//online or offline
	if($info['accessed_timestamp'] > $timestamp){
		//online
		$info['status']="Online";
    }
    else{
		//offline
		$info['status']="Offline";
    }
    $info['accessed_seconds']=$nowstamp - $info['accessed_timestamp'];
	$info['accessed_age']=verboseTime($info['accessed_seconds']);
	if(!strlen(trim($info['accessed_age']))){$info['accessed_age']="0 seconds";}
	//$info['status'] .= " - Last Accessed {$info['accessed_age']} ago on {$info['accessed_time']}";
	return $info;
}

//---------- begin function userValue ----
/**
* returns a single user value. If multiple values are passed in then it returns the first one with a value.
* @return mixed
* @param field mixed - array of fields or comma separated list of field or just a field.
* @usage
* 	<?=userValue('username');?>
*	<?=userValue('firstname,username');?>
* @author slloyd
* @history - bbarten 2014-01-02 added documentation
*/
function userValue($fields){
	global $USER;
	if(!is_array($fields)){$fields=preg_split('/\,/',$fields);}
	foreach($fields as $field){
		$field=trim($field);
		if(isset($USER[$field]) && strlen(trim($USER[$field]))){
			return $USER[$field];
		}
	}
}

//---------- begin function isUser ----
/**
* returns  true if a user is logged in
* @return boolean
* @usage
*	if(isUser()){}
* @author slloyd
* @history - bbarten 2014-01-02 added documentation
*/
function isUser(){
	global $USER;
	if(isset($USER['_id']) && isNum($USER['_id'])){return true;}
	return false;
}

//---------- begin function userProfileForm ----
/**
* returns a form that a user can use to edit their data if logged in. If not logged in, returns a login form.
* @return string
* @param array $params - any additional parameters to be included.
* 	See AddEditDBForm for additional params
* @usage
*	<?=userProfileForm();?>  returns an HTML form to change profile data
* @author slloyd
* @history - bbarten 2014-01-02 added documentation
*/
function userProfileForm($params=array()){
	if(!isUser()){return userLoginForm($params);}
	global $USER;
	$params['-table']="_users";
	$params['_id']=$USER['_id'];
	return addEditDBForm($params);
}

//---------- begin function encodeUserAuthCode ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function encodeUserAuthCode($id=0){
	global $USER;
	global $CONFIG;
	if($id==0 || $id==$USER['_id']){
		$rec=$USER;
		$id=$USER['_id'];
	}
	else{$rec=getDBRecord(array('-table'=>'_users','_id'=>$id,'-fields'=>'password,username,_id'));}
	$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
	$cryptkey=userGetUserCryptKey($id);
	$auth=array(
		str_replace(':','',crypt($CONFIG['dbname'],$cryptkey)),
		str_replace(':','',crypt($rec['username'],$cryptkey)),
	    str_replace(':','',crypt($pw,$cryptkey))
	    );
	$code=encodeBase64(implode(':',$auth));
	return $code;
}
//---------- begin function userGetUserCryptKey ----
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function userGetUserCryptKey($id){
	return "abcde7890fghik{$id}lmnopqr456stu=vwxyz{$id}ABCDEFGHIK{$id}LMNOPQR123!STUVWXYZ{$id}";

}
//---------- begin function userLoginForm ----
/**
* returns the User Login Form.
* @return string
* @param array $params - any additional parameters to be included.
*	[title] text - sets a title above the login form. No Default
*	[form_action] text - sets the action attribute in the form tag. Defauls to the current page you are viewing
*	[form_onsubmit] text - sets the onsubmit attribute in the form tag. Default is "return submitForm(this);"
*	[form_class] text - sets the class attribute in the form tag. Default is "w_form"
*	[form_name] text - sets the name attribute in the form tag. Default is "login"
*	[form_id] text - sets the id attribute in the form tag. Default is "loginform"
*	[form_method] text - sets the method attribute in the form tag. Default is "POST"
*	[form_style] text - sets the style attribute in the form tag. No Default

*	[username_name] text - sets the name for the username field. Supported options are username,email,phone. Default is "username"
*	[username_text] text - sets the placeholder text for the username field. Default is "Username"
*	[username_class] text - sets the class for the username field. Default is "browser-default w_input-append"
*	[username_style] text - sets the style for the username field. No Default
*	[username_id] text - sets the id for the username field. Default is "loginform_username"
*	[username_title] text - sets the title attribute for the username field. No Default

*	[username_pre_text] text - sets the icon prepended before the username field . Default is <span class="icon-user"></span>
*	[username_pre_class] text - sets the icon class of the icon prepended before the username field . Default is "icon-user"
*	[username_pre_style] text - sets the icon style of the icon prepended before the username field . No Default
*	[username_pre_id] text - sets the icon id of the icon prepended before the username field . No Default
*	[username_pre_title] text - sets the title attribute. No Default

*	[username_post_text] text - sets the icon appended after the username field . No Default
*	[username_post_class] text - sets the icon class of the icon appended after the username field . No Default
*	[username_post_style] text - sets the icon style of the icon appended after the username field . No Default
*	[username_post_id] text - sets the icon id of the icon appended after the username field . No Default
*	[username_post_title] text - sets the title attribute. No Default

*	[password_text] text - sets the placeholder text for the password field. Default is "password"
*	[password_class] text - sets the class for the password field. Default is "browser-default w_input-append"
*	[password_style] text - sets the style for the password field. No Default
*	[password_id] text - sets the id for the password field. Default is "loginform_password"
*	[password_title] text - sets the title attribute. No Default

*	[password_pre_text] text - sets the icon prepended before the password field . Default is <span class="icon-lock"></span>
*	[password_pre_class] text - sets the icon class of the icon prepended before the password field . Default is "icon-lock"
*	[password_pre_style] text - sets the icon style of the icon prepended before the password field . No Default
*	[password_pre_id] text - sets the icon id of the icon prepended before the password field . No Default
*	[password_pre_title] text - sets the title attribute. No Default

*	[password_post_text] text - sets the icon appended after the password field . No Default
*	[password_post_class] text - sets the icon class of the icon appended after the password field . No Default
*	[password_post_style] text - sets the icon style of the icon appended after the password field . No Default
*	[password_post_id] text - sets the icon id of the icon appended after the password field . No Default
*	[password_post_title] text - sets the title attribute. No Default

*	[remind_text] text - sets the text for the "remind me" link. Default is "Remind Me". If blank then no remind link will be shown
*	[remind_class] text - sets the class for the remind link. No Default
*	[remind_style] text - sets the class for the remind link. No Default
*	[remind_id] text - sets the id for the remind link. No Default
*	[remind_title] text - sets the title attribute. Default is "Click here if you need your login information emailed to you."

*	[login_text] text - sets the text for the login button. Default is "Login".
*	[login_class] text - sets the class for the login button. No Default
*	[login_style] text - sets the class for the login button. No Default
*	[login_id] text - sets the id for the login button. No Default
*	[login_title] text - sets the title attribute. No Default

*	Additional params are passed through as hidden key/value pairs
* @usage
*	<?=userLoginForm();?> - returns the default wasql login form so a user can sign in.

* @author slloyd
* @history - bbarten 2014-01-02 updated documentation
*/
function userLoginForm($params=array()){
	global $PAGE;
	global $CONFIG;
	//setup the default params values
	$defaults=array(
		'-title'				=> '',
		'-form_action'			=> isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:"/{$PAGE['name']}",
		'-form_onsubmit'		=> "return submitForm(this);",
		'-form_class'			=> "w_form",
		'-form_name'			=> "loginform",
		'-form_id'				=> "loginform",
		'-form_method'			=> "POST",
		'-form_style'			=> '',
		'-form_title'			=> '',
		'-username_name'		=> 'username',
		'-username_text'		=> 'Username',
		'-username_class'		=> 'browser-default input',
		'-username_style'		=> '',
		'-username_id'			=> 'loginform_username',
		'-username_title'		=> 'loginform_username',
		'-username_pre_text'	=> '',
		'-username_pre_class'	=> 'icon-user w_biggest',
		'-username_pre_style'	=> 'vertical-align:middle;margin-right:3px;',
		'-username_pre_id'		=> '',
		'-username_pre_title'	=> '',
		'-username_post_text'	=> '',
		'-username_post_class'	=> '',
		'-username_post_style'	=> '',
		'-username_post_id'		=> '',
		'-username_post_title'	=> '',
		'-password_text'		=> 'password',
		'-password_class'		=> 'browser-default input',
		'-password_style'		=> '',
		'-password_id'			=> 'loginform_password',
		'-password_title'		=> 'loginform_password',
		'-password_pre_text'	=> '',
		'-password_pre_class'	=> 'icon-lock w_biggest',
		'-password_pre_style'	=> 'vertical-align:middle;margin-right:3px;',
		'-password_pre_id'		=> '',
		'-password_pre_title'	=> '',
		'-password_post_text'	=> '',
		'-password_post_class'	=> 'icon-eye',
		'-password_post_style'	=> 'vertical-align:middle;margin-left:3px;',
		'-password_post_id'		=> '',
		'-password_post_title'	=> '',
		'-remind_text'			=> 'Remind Me',
		'-remind_class'			=> 'w_small w_gray',
		'-remind_style'			=> 'text-decoration:none;margin-left:30px;',
		'-remind_id'			=> '',
		'-remind_title'			=> 'Click here if you need your login information emailed to you.',
		'-login_text'			=> 'Login',
		'-login_class'			=> 'btn button',
		'-login_style'			=> '',
		'-login_id'				=> '',
		'-login_title'			=> ''		
	);
	//check for email and phone names
	switch(strtolower($params['-username_name'])){
		case 'email':
			if(!isset($params['-username_text'])){
				$params['-username_text']='email address';
			}
			if(!isset($params['-username_pre_class'])){
				$params['-username_pre_class']='icon-mail w_biggest';
			}
		break;
		case 'phone':
			if(!isset($params['-username_text'])){
				$params['-username_text']='phone number';
			}
			if(!isset($params['-username_pre_class'])){
				$params['-username_pre_class']='icon-phone w_biggest';
			}
		break;
	}
	//set params to default if they do not exist
	foreach($defaults as $key=>$val){
    	if(!isset($params[$key])){
    		$xkey=preg_replace('/^\-/','',$key);
    		if(isset($CONFIG[$xkey])){
    			$params[$key]=$CONFIG[$xkey];
    		}
    		else{
    			$params[$key]=$val;
    		}
    	}
	}
	//return printValue($params);
	//plivo?
	if($params['-username_name']=='phone' && (isset($CONFIG['plivo_auth_id']) || isset($CONFIG['plivo_auth_token']) || isset($CONFIG['plivo_from']))){
		$ok=loadExtras('plivo');
		if(!isset($CONFIG['plivo_loaded'])){
			echo $ok;exit;
		}
		if(!strlen($params['-username_post_text'])){
			$params['-username_post_text']='<button type="button" id="'.$params['-form_name'].'_send_phone_auth" data-username_id="'.$params['-username_id'].'" onclick="formSendPhoneAuth(this);" style="margin-left:3px;" class="btn button">Send Auth</button>';	
		}
		
	}
	//backward compatibility settings
	if(isset($params['-username_title'])){$params['-username_text']=$params['-username_title'];unset($params['-username_title']);}
	if(isset($params['-password_title'])){$params['-password_text']=$params['-password_title'];unset($params['-password_title']);}
	if(isset($params['-login_title'])){$params['-title']=$params['-login_title'];unset($params['-login_title']);}
	elseif(isset($params['-message'])){$params['-title']=$params['-message'];unset($params['message']);}
	if(isset($params['_action'])){$params['-form_action']=$params['_action'];unset($params['_action']);}
	//If no title and using LDAP
	if(!isset($params['-title'])){
		if(isset($CONFIG['-login_title'])){$params['-title']=$CONFIG['login_title'];}
		elseif(isset($CONFIG['authhost'])){
			$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your "'.$CONFIG['authhost'].'" credentials.</div>'.PHP_EOL;
		}
		elseif(isset($CONFIG['authldap']) && (!isset($CONFIG['authldap_network']) || stringBeginsWith($_SERVER['REMOTE_ADDR'],$CONFIG['authldap_network']))){
			$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your LDAP "'.$CONFIG['authldap'].'" credentials.</div>'.PHP_EOL;
		}
		elseif(isset($CONFIG['authldaps']) && (!isset($CONFIG['authldap_network']) || stringBeginsWith($_SERVER['REMOTE_ADDR'],$CONFIG['authldap_network']))){
			$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your LDAP "'.$CONFIG['authldaps'].'" credentials.</div>'.PHP_EOL;
		}
		elseif(isset($CONFIG['auth365'])){
			$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your portal.office365.com credentials.</div>'.PHP_EOL;
		}	
	}
	//return the user Login form
	$form='';
	$form .= '<div style="clear:both;">'.PHP_EOL;
	//beginning form tag
	$attributes=array('id','name','style','class','method','action','onsubmit');
	$form .= '<form';
	foreach($attributes as $key){
		$key1="-{$key}";
		$key2="-form_{$key}";
		if(isset($params[$key1]) && strlen($params[$key1])){
			$form .= " {$key}=\"{$params[$key1]}\"";
		}
		elseif(isset($params[$key2]) && strlen($params[$key2])){
			$form .= " {$key}=\"{$params[$key2]}\"";
		}
	}
	$form .= ">".PHP_EOL;
	$attributes=array('id','style','class','title');
	$form .= '	<input type="hidden" name="_login" value="1">'.PHP_EOL;
	$form .= '	<input type="hidden" name="_pwe" value="1">'.PHP_EOL;
	//title
    if(strlen($params['-title'])){
		$form .= '<div>'.$params['-title'].'</div>'.PHP_EOL;
    }
    $form .= '<div style="max-width:300px;margin-left:10px;">'.PHP_EOL;
    //username div
	$form .= '	<div style="display:flex;flex-direction:row;justify-content:flex-start;margin-top:5px;">'.PHP_EOL;
	//username_pre
	$prefix='-username_pre';
	if(strlen($params["{$prefix}_text"])){
		$form .= '<div>'.$params["{$prefix}_text"].'</div>';
	}
	else{
		$form .= '<div><span';
		$tagopts=array();
		foreach($attributes as $key){
			$tkey="{$prefix}_{$key}";
			if(isset($params[$tkey]) && strlen($params[$tkey])){
				$tagopts[$key]=$params[$tkey];
			}
		}
		$form .= setTagAttributes($tagopts);
		$form .= "></span></div>".PHP_EOL;
	}
	//username
	$prefix='-username';
	$tagopts=array('placeholder'=>$params["{$prefix}_text"]);
	foreach($attributes as $key){
		$tkey="{$prefix}_{$key}";
		if(isset($params[$tkey]) && strlen($params[$tkey])){
			$tagopts[$key]=$params[$tkey];
		}
	}
	switch(strtolower($params['-username_name'])){	
		case 'email':
			$tagopts['-type']='email';
			$form .= '		'.buildFormText('email',$tagopts);
		break;
		case 'phone':
			$tagopts['-type']='tel';
			$form .= '		'.buildFormText('phone',$tagopts);
		break;
		default:
			//defaults to username
			$form .= '		'.buildFormText('username',$tagopts);
		break;
	}
	
	//username_post
	$prefix='-username_post';
	if(strlen($params["{$prefix}_text"])){
		$form .= '<div>'.$params["{$prefix}_text"].'</div>';
	}
	else{
		$form .= '<div><span';
		$tagopts=array();
		foreach($attributes as $key){
			$tkey="{$prefix}_{$key}";
			if(isset($params[$tkey]) && strlen($params[$tkey])){
				$tagopts[$key]=$params[$tkey];
			}
		}
		$form .= setTagAttributes($tagopts);
		$form .= "></span></div>".PHP_EOL;
	}
	$form .= '	</div>'.PHP_EOL;
	//password div
	$form .= '	<div style="display:flex;flex-direction:row;justify-content:flex-start;margin-top:5px;">'.PHP_EOL;
	//password_pre
	$prefix='-password_pre';
	if(strlen($params["{$prefix}_text"])){
		$form .= '<div>'.$params["{$prefix}_text"].'</div>';
	}
	else{
		$form .= '<div><span';
		$tagopts=array();
		foreach($attributes as $key){
			$tkey="{$prefix}_{$key}";
			if(isset($params[$tkey]) && strlen($params[$tkey])){
				$tagopts[$key]=$params[$tkey];
			}
		}
		$form .= setTagAttributes($tagopts);
		$form .= "></span></div>".PHP_EOL;
	}
	//password
	$prefix='-password';
	$tagopts=array('required'=>'required','placeholder'=>$params["{$prefix}_text"]);
	foreach($attributes as $key){
		$tkey="{$prefix}_{$key}";
		if(isset($params[$tkey]) && strlen($params[$tkey])){
			$tagopts[$key]=$params[$tkey];
		}
	}
	//$form .= printValue($tagopts);
	$tagopts['data-show_icon']=1;
	$form .= '		'.buildFormPassword('password',$tagopts);
	//password_post
	// $prefix='-password_post';
	// if(strlen($params["{$prefix}_text"])){
	// 	$form .= '<div>'.$params["{$prefix}_text"].'</div>';
	// }
	// else{
	// 	$form .= '<div style="cursor:pointer;" onmouseover="this.title=document.'.$params['-form_name'].'.password.value;" onmouseout="this.title=\'\';"><span';
	// 	$tagopts=array();
	// 	foreach($attributes as $key){
	// 		$tkey="{$prefix}_{$key}";
	// 		if(isset($params[$tkey]) && strlen($params[$tkey])){
	// 			$tagopts[$key]=$params[$tkey];
	// 		}
	// 	}
	// 	$form .= setTagAttributes($tagopts);
	// 	$form .= "></span></div>".PHP_EOL;
	// }
	$form .= '	</div>'.PHP_EOL;
	//remind me and login
	$form .= '	<div style="display:flex;flex-direction:row;justify-content:space-between;margin-top:10px;">'.PHP_EOL;
	//remind
	$prefix='-remind';
	if(strlen($params["{$prefix}_text"])){
		$form .= '<a href="#" onClick="remindMeForm(document.'.$params['-form_name'].'.username.value);return false;"';
		$tagopts=array();
		foreach($attributes as $key){
			$tkey="{$prefix}_{$key}";
			if(isset($params[$tkey]) && strlen($params[$tkey])){
				$tagopts[$key]=$params[$tkey];
			}
		}
		$form .= setTagAttributes($tagopts);
		$form .= ">{$params["{$prefix}_text"]}</a>".PHP_EOL;
	}
	//login
	$prefix='-login';
	if(strlen($params["{$prefix}_text"])){
		$form .= '<button type="submit"';
		$tagopts=array();
		foreach($attributes as $key){
			$tkey="{$prefix}_{$key}";
			if(isset($params[$tkey]) && strlen($params[$tkey])){
				$tagopts[$key]=$params[$tkey];
			}
		}
		$form .= setTagAttributes($tagopts);
		$form .= ">{$params["{$prefix}_text"]}</button>".PHP_EOL;
	}
	$form .= '	</div>'.PHP_EOL;

	$form .= '</div>'.PHP_EOL;
	//passthru params
	$form .= '<div style="display:none;" id="passthru">'.PHP_EOL;
	foreach($params as $key=>$val){
		if(stringBeginsWith($key,'-')){continue;}
		$form .= '	<textarea name="'.$key.'">'.$val.'</textarea>'.PHP_EOL;
    }
    $form .= '</div>'.PHP_EOL;
	$form .= '</form>'.PHP_EOL;
	if(!isset($params['-focus']) || $params['-focus'] == 'username'){
		$form .= buildOnLoad("document.{$params['-form_name']}.username.focus();");
	}
	if(isset($_REQUEST['_login_error'])){
		$form .= '<span class="w_red">'.$_REQUEST['_login_error'].'</span>'.PHP_EOL;
	}
	$form .= '</div>'.PHP_EOL;
	return $form;
}
//---------- begin function wpassSalt ----
/**
* returns a base salt component for encrypting wpass entries. Never change this.
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassSalt(){
	return 'W8P2A4S0S?S%A#L@T!';
}
//---------- begin function wpassEncrypt ----
/**
* encrypts wpass entries.
* @param string
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassEncrypt($str){
	$salt=wpassSalt();
	return encrypt($str,$salt);
}
//---------- begin function wpassDecrypt ----
/**
* decrypts wpass entries.
* @param string
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassDecrypt($encstr){
	$salt=wpassSalt();
	return decrypt($encstr,$salt);
}
//---------- begin function wpassEncryptArray ----
/**
* encrypts entire wpass record or add/edit options.
* @param array
* @return array
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassEncryptArray($rec=array()){
	$salt=wpassSalt();
	$encrypted_keys=array('user','pass','url','notes');
	foreach($rec as $key=>$val){
    	if(in_array($key,$encrypted_keys)){
        	$rec[$key]=wpassEncrypt($rec[$key]);
		}
	}
	return $rec;
}
//---------- begin function wpassDecryptArray ----
/**
* decrypts entire wpass record
* @param array
* @return array
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassDecryptArray($rec=array()){
	$salt=wpassSalt();
	$encrypted_keys=array('user','pass','url','notes');
	foreach($rec as $key=>$val){
    	if(in_array($key,$encrypted_keys)){
        	$rec[$key]=wpassDecrypt($rec[$key]);
		}
	}
	return $rec;
}
//---------- begin function wpassGetCategories ----
/**
* returns current users wpass categories from database
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassGetCategories($str=1){
	global $USER;
	if(!isNum($USER['_id'])){return '';}
	$query="select distinct(category) from _wpass where _cuser={$USER['_id']} order by category";
	$recs=getDBRecords(array('-query'=>$query,'-index'=>'category'));
	if(!is_array($recs)){return '';}
	if($str==1){
		return implode("\r\n",array_keys($recs));
	}
	return array_keys($recs);
}
//---------- begin function wpassModule ----
/**
* returns the wpass module - allows you to embed it on your site
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassModule(){
	global $USER;
	if(!isNum($USER['_id'])){return '';}
	if(!isDBTable('_wpass')){createWasqlTables('_wpass');}
	$rtn =  '<div class="w_wpass" style="z-index:998;position:relative;display:table-cell;">'.PHP_EOL;
	$rtn .= '	<input id="wpass_search" class="w_form-control" value="" list="wpass_datalist" oninput="return wpassInput(this.value);" style="width:40px;" onfocus="this.style.width=\'250px\';" onblur="this.style.width=\'40px\';" placeholder="wPass search" />'.PHP_EOL;
	$rtn .= '	<img onclick="wpassInput(0);" title="click to add new wPass record" class="w_pointer w_middle" src="/wfiles/_wpass.png" alt="add" />'.PHP_EOL;
	$rtn .= '	<div id="wpass_info" style="z-index:999;position:absolute;top:30px;right:0px;background:#FFF;"></div>'.PHP_EOL;
	$rtn .= '	<div style="display:none;"><div id="wpass_nulldiv"></div></div>'.PHP_EOL;
	$query="select _id,category,title from _wpass where users like '{$USER['_id']}:%' or users like '%:{$USER['_id']}' or users like '{$USER['_id']}' or users like '%:{$USER['_id']}:%' or  _cuser={$USER['_id']} order by category,title";
	$recs=getDBRecords(array('-query'=>$query));
	$rtn .= '	<datalist id="wpass_datalist">'.PHP_EOL;
	if(is_array($recs)){
		foreach($recs as $rec){
			$rtn .= '	<option value="'.$rec['_id'].'">'."{$rec['category']} - {$rec['title']}".'</option>'.PHP_EOL;
    	}
	}
    $rtn .= '	</datalist>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}
//---------- begin function wpassInfo ----
/**
* @param id int
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wpassInfo($id){
	global $USER;
	if(!isNum($USER['_id'])){return '';}
	$rtn='';
	if($id==0){
    	//add
    	$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'.PHP_EOL;
    	$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="new" /> New wPass Record</div>'.PHP_EOL;
    	$rtn .= addEditDBForm(array(
			'-table'=>'_wpass',
			'-name'=> '_wpass_addedit',
			'-action'=>'/php/index.php',
			'_wpass_addedit'=>1,
			'-fields'	=> "category:title,user:pass,url,notes,users",
			'-onsubmit'=>"ajaxSubmitForm(this,'wpass_info');return false;"
		));
		$rtn .= '<div class="w_smaller"> - Fields marked with ** are stored in the database encrypted</div>'.PHP_EOL;
		$rtn .= '<div class="w_smaller"> - only the creator of this record can edit it.</div>'.PHP_EOL;
		$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'.PHP_EOL;
	}
	else{
		$rec=getDBRecord(array(
			'-table'	=> '_wpass',
			'_id'		=> $_REQUEST['_wpass'],
			'-relate'	=> array('_cuser'=>'_users','_euser'=>'_users')
		));
		if(isset($rec['_id'])){
			$users_list=preg_split('/\:/',$rec['users']);
			if($rec['_cuser'] != $USER['_id'] && !in_array($USER['_id'],$users_list)){
            	$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="denied" /> Denied Access</div>'.PHP_EOL;
			}
			$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'.PHP_EOL;
			$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="edit" /> Edit wPass Record</div>'.PHP_EOL;
			$editopts=array(
				'-table'=>'_wpass',
				'-name'=> '_wpass_addedit',
				'_id'	=> $rec['_id'],
				'-action'=>'/php/index.php',
				'_wpass_addedit'=>1,
				'-fields'	=> "category:title,user:pass,url,notes",
				'-onsubmit'=>"ajaxSubmitForm(this,'wpass_info');return false;"
			);
			if($rec['_cuser']==$USER['_id']){
            	$editopts['-fields']="category:title,user:pass,url,notes,users";
			}
			else{
            	$editopts['-hide']="clone,delete,reset,save";
			}
	    	$rtn .= addEditDBForm($editopts);
			$rtn .= '<div class="w_smaller"> - Fields marked with ** are stored in the database encrypted</div>'.PHP_EOL;
			$rtn .= '<div class="w_smaller"> - only the creator of this record can edit it.</div>'.PHP_EOL;
			if($rec['_cuser']==$USER['_id']){
				$rtn .= '<div class="w_smaller"> - Created by you on '.$rec['_cdate'].'</div>'.PHP_EOL;
			}
			else{
            	$rtn .= '<div class="w_smaller"> - Created by '."{$rec['_cuser_ex']['firstname']} {$rec['_cuser_ex']['lastname']}".' on '.$rec['_cdate'].'</div>'.PHP_EOL;
			}
			if(strlen($rec['_edate']) && isset($rec['_euser_ex']['firstname'])){
            	$rtn .= '<div class="w_smaller"> - Last edited by '."{$rec['_euser_ex']['firstname']} {$rec['_euser_ex']['lastname']}".' on '.$rec['_cdate'].'</div>'.PHP_EOL;
			}
			$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'.PHP_EOL;
		}
		else{
			$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="no record" /> No such record</div>'.PHP_EOL;
		}
	}
	return $rtn;
}
//---------- begin function wguidSalt ----
/**
* returns a base salt component for encrypting wguid entries. Never change this.
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wguidSalt(){
	return 'W85Q1U53R?S%A#L@T!';
}