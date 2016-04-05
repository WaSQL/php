<?php
$progpath=dirname(__FILE__);
//requires common,wasql, and database to be loaded first
//parse Server Variables
if(!isset($_SERVER['UNIQUE_HOST'])){parseEnv();}
//echo "DEBUG".printValue(headers_list());exit;

//echo "DEBUG".printValue(headers_list());exit;
if(!isDBTable('_users')){
	include_once("$progpath/schema.php");
	$ok=createWasqlTables();
}
if(!isDBTable('states')){
	include_once("$progpath/schema.php");
	$ok=createWasqlTables();
}
if(!isDBTable('countries')){
	include_once("$progpath/schema.php");
	$ok=createWasqlTables();
}
if(!isDBTable('_sessions')){
	include_once("$progpath/schema.php");
	$ok=createWasqlTables();
}
//get the user GUID stored in a cookie
$guid=getGUID();
$oldguid=$guid;
global $USER;
global $CONFIG;
global $ConfigXml;

//SQL injection prevention
if(isset($_REQUEST['username'])){
	$_REQUEST['username']=preg_replace('/\'/',"\\'",$_REQUEST['username']);
}
$userfieldinfo=getDBFieldInfo("_users");
if(isset($_REQUEST['_auth']) && preg_match('/^([0-9]+?)\./s',$_REQUEST['_auth']) &&  strtoupper($_SERVER['REQUEST_METHOD'])=='GET'){
	list($key,$encoded)=preg_split('/\./',$_REQUEST['_auth'],2);
	$decoded=decrypt($encoded,$key);
	//abort($decoded);
	list($_REQUEST['username'],$_REQUEST['apikey'])=preg_split('/\:/',$decoded,2);
	$_REQUEST['_auth']=1;
}

if(isset($_REQUEST['_login']) && $_REQUEST['_login']==1 && isset($_REQUEST['username']) && isset($_REQUEST['password'])){
	if(isNum($_REQUEST['_pwe']) && $_REQUEST['_pwe']==1 && !isset($CONFIG['authhost']) && !isset($CONFIG['auth365']) && !isset($CONFIG['authldap']) && !isset($CONFIG['authldaps'])){
		$rec=getDBRecord(array('-table'=>'_users','username'=>$_REQUEST['username']));
		if(is_array($rec) && userIsEncryptedPW($rec['password'])){
			$_REQUEST['password']=userEncryptPW($_REQUEST['password']);
		}
	}
	if(isUser()){
		$num=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>"NULL"));
	}
	if((isset($CONFIG['authldap']) || isset($CONFIG['authldaps'])) && (!isset($CONFIG['authldap_network']) || stringBeginsWith($_SERVER['REMOTE_ADDR'],$CONFIG['authldap_network']))){
     	loadExtras('ldap');
     	$host=isset($CONFIG['authldap'])?$CONFIG['authldap']:$CONFIG['authldaps'];
     	$authopts=array(
			'-host'		=> $host,
			'-username'	=> $_REQUEST['username'],
			'-password'	=> $_REQUEST['password']
		);
		if(isset($CONFIG['authldap_domain'])){
        	$authopts['-domain']=$CONFIG['authldap_domain'];
		}
     	$ldap=ldapAuth($authopts);
		if(is_array($ldap)){
          	$fields=getDBFields('_users');
          	$admins=array();
          	if(isset($CONFIG['authldap_admin'])){
            	$admins=preg_split('/[\,\;\:]+/',$CONFIG['authldap_admin']);
			}
          	//add or update this user record
          	$rec=getDBRecord(array('-table'=>'_users','-where'=>"username='{$ldap['username']}' or email='{$ldap['email']}'"));
          	if(is_array($rec)){
               	$changes=array();
               	foreach($fields as $field){
                    if(isset($ldap[$field]) && $rec[$field] != $ldap[$field]){
                        $changes[$field]=$ldap[$field];
                        $rec[$field]=$ldap[$field];
					}
				}
				if(in_array($rec['username'],$admins) || in_array($rec['email'],$admins)){
					if($rec['utype'] != 0){
						$changes['utype']=0;
						$rec['utype']=0;
					}
				}
				elseif($rec['utype'] == 0){
					$changes['utype']=1;
					$rec['utype']=1;
				}
				if(count($changes)){
                    $changes['-table']='_users';
                    $changes['-where']="_id={$rec['_id']}";
                    $ok=editDBRecord($changes);
				}
				$USER=$rec;
			}
			else{
               	$ldap['-table']='_users';
               	if(in_array($ldap['username'],$admins) || in_array($ldap['email'],$admins)){
					$ldap['utype']= 0;
				}
				else{$ldap['utype']=1;}
               	$id=addDBRecord($ldap);
               	if(isNum($id)){
                    $USER=getDBRecord(array('-table'=>'_users','_id'=>$id));
				}
			}
		}
		else{
          	$_REQUEST['_login_error']=$ldap;
		}
	}
	elseif(isset($CONFIG['auth365'])){
     	loadExtras('office365');
     	$authopts=array(
			'-username'	=> $_REQUEST['username'],
			'-password'	=> $_REQUEST['password']
		);
     	$auth=office365Auth($authopts);
		if(is_array($auth)){
          	//currently office365 can only tell me they are valid. I will need to makup the rest for now.
          	$admins=array();
          	if(isset($CONFIG['auth365_admin'])){
            	$admins=preg_split('/[\,\;\:]+/',$CONFIG['auth365_admin']);
			}
          	//add or update this user record
          	$rec=getDBRecord(array('-table'=>'_users','-where'=>"username='{$authopts['-username']}' or email='{$authopts['-username']}'"));
          	if(is_array($rec)){
				if(in_array($rec['username'],$admins) || in_array($rec['email'],$admins)){
					if($rec['utype'] !=0){
	                	$rec['utype']=0;
	                	$ok=editDBRecord(array(
							'-table'	=> "_users",
							'-where'	=> "_id={$rec['_id']}",
							'utype'		=> 0
						));
					}
				}
				elseif($rec['utype']==0){
                	$rec['utype']=1;
                	$ok=editDBRecord(array(
						'-table'	=> "_users",
						'-where'	=> "_id={$rec['_id']}",
						'utype'		=> 1
					));
				}
				$USER=$rec;
			}
			else{
				$opts=array(
					'-table'	=> "_users",
					'password'	=> substr(sha1($auth['value']),0,25),
					'username'	=> $authopts['-username'],
					'utype'		=> 1,
					'email'		=> $authopts['-username'],
				);
				if(in_array($authopts['-username'],$admins)){$opts['utype']=0;}
               	$id=addDBRecord($opts);
               	if(isNum($id)){
                    $USER=getDBRecord(array('-table'=>'_users','_id'=>$id));
				}
			}
		}
		else{
          	$_REQUEST['_login_error']=$auth;
		}
	}
	elseif(isset($CONFIG['authhost'])){
		$authname=$CONFIG['authhost'];
		switch(strtolower($authname)){
			case 'openid':
				//openid authentication coming soon
				break;
			default:
				//authenticate to a different host
				$authkey=encodeCRC($_REQUEST['username'].time());
				$authcode=encrypt("{$_REQUEST['username']}:{$_REQUEST['password']}",$authkey);
				$url="http://{$CONFIG['authhost']}/php/index.php";
				$authopts=array('skip_error'=>1,'_action'=>"Auth",'_authcode'=>$authcode,'_authkey'=>$authkey,'_pwe'=>$_REQUEST['_pwe']);
				$post=postURL($url,$authopts);
				if(!isset($post['body']) && isset($post['error'])){
					setWasqlError(debug_backtrace(),"Login Error: " . $post['error']);
					break;
                	}
				try {
					$xml=new SimpleXmlElement($post['body']);
					//abort("xml:" . printValue($xml));
					if(strlen((string)$xml->success)){
						$authcode=(string)$xml->success;
						$tmp=xml2Arrays(decrypt($authcode,$authkey));
						unset($_SESSION['authcode']);
						unset($_SESSION['authkey']);
						if(is_array($tmp) && isEmail($tmp[0]['email'])){
							//successful authentication - check for local user record.
							$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"username = '{$tmp[0]['username']}'"));
							if(!is_array($local) && isEmail($tmp[0]['email'])){
								$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"email = '{$tmp[0]['email']}'"));
                            	}
                            if(!is_array($local) && isEmail($tmp[0]['email'])){
								$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"username = '{$authname}_{$tmp[0]['username']}'"));
                            	}
                            if(!is_array($local)){
								//create a _user record
								$opts=$tmp[0];
								//set new users as non-admin
								$opts['utype']=1;
								//set username to authhost_{username}
								$opts['username']="{$tmp[0]['username']}";
								//set table
								$opts['-table']="_users";
								//add the record and load into local
								$id=addDBRecord($opts);
								if(isNum($id)){
                                    $local=getDBRecord(array('-table'=>'_users','_id'=>$id,'-relate'=>1));
                                	}
      							}
							if(is_array($local)){
								$USER=$local;
								unset($local);
								$USER['_local']=true;
								$eopts=array();
								$info=getDBFieldInfo("_users");
								foreach($tmp[0] as $key=>$val){
									if(isset($info[$key]['_dbtype']) && !preg_match('/^\_/',$key) && (strlen($USER[$key])==0 || $USER[$key] != $val)){
										$eopts[$key]=$val;
										}
									if($key=='password'){
										$val=preg_replace('/./','*',$val);
                                    	}
									$newkey="{$authname}_{$key}";
									$USER[$newkey]=$val;
                                	}
                                if(count($eopts) > 0){
									$eopts['-table']="_users";
									$eopts['-where']="_id={$USER['_id']}";
									$ok=editDBRecord($eopts);
									$USER['_updated']=$eopts;
                                	}
                            	}
							$USER['_authhost']=$authname;
							$_SESSION['authcode']=$authcode;
							$_SESSION['authkey']=$authkey;
							}
						}
					}
				catch (Exception $e){
	        		echo $e->faultstring;
	        		echo "<hr>\n";
	        		echo $post['body'];
	        		echo "<hr>\n";
	        		echo $url . printValue($authopts);
	        		exit;
	        		}
	        	break;
			}
    	}
    else{
		$getopts=array('-table'=>'_users','-relate'=>1,'username'=>$_REQUEST['username'],'password'=>$_REQUEST['password']);
		$USER=getDBRecord($getopts);
    }
}
elseif(isset($_REQUEST['_login']) && $_REQUEST['_login']==1 && isset($_REQUEST['email']) && isset($_REQUEST['password'])){
	if(isNum($_REQUEST['_pwe']) && $_REQUEST['_pwe']==1 && !isset($CONFIG['authhost'])){
		$rec=getDBRecord(array('-table'=>'_users','email'=>$_REQUEST['email']));
		if(is_array($rec) && userIsEncryptedPW($rec['password'])){
			$_REQUEST['password']=userEncryptPW($_REQUEST['password']);
		}
	}
	if(isUser()){
		$num=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>"NULL"));
	}
	if(isset($CONFIG['authhost'])){
		$authname=$CONFIG['authhost'];
		switch(strtolower($authname)){
			case 'openid':
				//openid authentication coming soon
				break;
			default:
				//authenticate to a different host
				$authkey=encodeCRC($_REQUEST['username'].time());
				$authcode=encrypt("{$_REQUEST['username']}:{$_REQUEST['password']}",$authkey);
				$url="http://{$CONFIG['authhost']}/php/index.php";
				$authopts=array('skip_error'=>1,'_action'=>"Auth",'_authcode'=>$authcode,'_authkey'=>$authkey,'_pwe'=>$_REQUEST['_pwe']);
				$post=postURL($url,$authopts);
				if(!isset($post['body']) && isset($post['error'])){
					setWasqlError(debug_backtrace(),"Login Error: " . $post['error']);
					break;
                }
				try {
					$xml=new SimpleXmlElement($post['body']);
					//abort("xml:" . printValue($xml));
					if(strlen((string)$xml->success)){
						$authcode=(string)$xml->success;
						$tmp=xml2Arrays(decrypt($authcode,$authkey));
						unset($_SESSION['authcode']);
						unset($_SESSION['authkey']);
						if(is_array($tmp) && isEmail($tmp[0]['email'])){
							//successful authentication - check for local user record.
							$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"email = '{$tmp[0]['username']}'"));
							if(!is_array($local) && isEmail($tmp[0]['email'])){
								$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"email = '{$tmp[0]['email']}'"));
                            	}
                            if(!is_array($local) && isEmail($tmp[0]['email'])){
								$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"email = '{$authname}_{$tmp[0]['username']}'"));
                            	}
                            if(!is_array($local)){
								//create a _user record
								$opts=$tmp[0];
								//set new users as non-admin
								$opts['utype']=1;
								//set username to authhost_{username}
								$opts['username']="{$tmp[0]['username']}";
								//set table
								$opts['-table']="_users";
								//add the record and load into local
								$id=addDBRecord($opts);
								if(isNum($id)){
                                    $local=getDBRecord(array('-table'=>'_users','_id'=>$id,'-relate'=>1));
                                	}
      							}
							if(is_array($local)){
								$USER=$local;
								unset($local);
								$USER['_local']=true;
								$eopts=array();
								$info=getDBFieldInfo("_users");
								foreach($tmp[0] as $key=>$val){
									if(isset($info[$key]['_dbtype']) && !preg_match('/^\_/',$key) && (strlen($USER[$key])==0 || $USER[$key] != $val)){
										$eopts[$key]=$val;
										}
									if($key=='password'){
										$val=preg_replace('/./','*',$val);
                                    	}
									$newkey="{$authname}_{$key}";
									$USER[$newkey]=$val;
                                }
                                if(count($eopts) > 0){
									$eopts['-table']="_users";
									$eopts['-where']="_id={$USER['_id']}";
									$ok=editDBRecord($eopts);
									$USER['_updated']=$eopts;
                                }
                            }
							$USER['_authhost']=$authname;
							$_SESSION['authcode']=$authcode;
							$_SESSION['authkey']=$authkey;
						}
					}
				}
				catch (Exception $e){
	        		echo $e->faultstring;
	        		echo "<hr>\n";
	        		echo $post['body'];
	        		echo "<hr>\n";
	        		echo $url . printValue($authopts);
	        		exit;
	        	}
	        	break;
			}
    	}
	else{
		$getopts=array('-table'=>'_users','-relate'=>1,'email'=>$_REQUEST['email'],'password'=>$_REQUEST['password']);
		$USER=getDBRecord($getopts);
    }
}
elseif(isset($_REQUEST['_login']) && $_REQUEST['_login']==1 && isset($_REQUEST['facebook_email']) && isset($_REQUEST['password'])){
	$getopts=array('-table'=>'_users','-relate'=>1,'facebook_email'=>$_REQUEST['facebook_email'],'facebook_id'=>$_REQUEST['password']);
	$USER=getDBRecord($getopts);
}
elseif(isset($_REQUEST['_login']) && $_REQUEST['_login']==1 && isset($_REQUEST['google_email']) && isset($_REQUEST['password'])){
	$getopts=array('-table'=>'_users','-relate'=>1,'google_email'=>$_REQUEST['google_email'],'google_id'=>$_REQUEST['password']);
	$USER=getDBRecord($getopts);
}
elseif(isset($_REQUEST['apikey']) && isset($_REQUEST['username']) &&  ((isset($_REQUEST['_auth']) && $_REQUEST['_auth']==1) || strtoupper($_SERVER['REQUEST_METHOD'])=='POST')){
	if(isUser()){
		$num=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>"NULL"));
	}
	//apikey login request - requires a POST for security
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'username'=>$_REQUEST['username']));
	if(is_array($rec)){
		$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
		$auth=array(
			str_replace(':','',crypt($_SERVER['UNIQUE_HOST'],$rec['username'])),
			str_replace(':','',crypt($rec['username'],$pw)),
		    str_replace(':','',crypt($pw,$rec['username']))
		);
		$api=preg_split('/[:]/',decodeBase64($_REQUEST['apikey']));
		if($api[0]==$auth[0] && $api[1]==$auth[1] && $api[2]==$auth[2]){
        	$USER=$rec;
        	$oldguid=$rec['guid'];
  		}
	}
}
elseif(isset($_SESSION['apikey']) && isset($_SESSION['username']) &&  strtoupper($_SERVER['REQUEST_METHOD'])=='GET'){
	if(isUser()){
		$num=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>"NULL"));
	}
	//apikey login request - requires a POST for security
	$rec=getDBRecord(array('-table'=>'_users','-relate'=>1,'username'=>$_SESSION['username']));
	if(is_array($rec)){
		$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
		$auth=array(
			str_replace(':','',crypt($_SERVER['UNIQUE_HOST'],$pw)),
			str_replace(':','',crypt($rec['username'],$pw)),
		    str_replace(':','',crypt($pw,$rec['username']))
		);
		$api=preg_split('/[:]/',decodeBase64($_SESSION['apikey']));
		if($api[0]==$auth[0] && $api[1]==$auth[1] && $api[2]==$auth[2]){
        	$USER=$rec;
        	$oldguid=$rec['guid'];
  		}
	}
	unset($_SESSION['apikey']);
	unset($_SESSION['username']);
}
elseif(isset($CONFIG['authhost']) && isset($_SESSION['authcode']) && isset($_SESSION['authkey'])){
	if(isUser()){
		$num=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>"NULL"));
	}
	$authstring=decrypt($_SESSION['authcode'],$_SESSION['authkey']);
	//echo printValue($_SESSION);
	//echo "Session authstring:" . printValue($authstring);
	$tmp=xml2Arrays($authstring);
	//echo "Session tmp:" . printValue($tmp);
	if(is_array($tmp) && isEmail($tmp[0]['email'])){
		//successful authentication - check for local user record.
		$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"email = '{$tmp[0]['email']}'"));
		if(!is_array($local) && isEmail($tmp[0]['email'])){
			$local=getDBRecord(array('-table'=>'_users','-relate'=>1,'-where'=>"username = '{$authname}_{$tmp[0]['username']}'"));
	    }
	    if(!is_array($local)){
			//create a _user record
			$opts=$tmp[0];
			//set new users as non-admin
			$opts['utype']=1;
			//set username to authhost_{username}
			$opts['username']="{$authname}_{$tmp[0]['username']}";
			//set table
			$opts['-table']="_users";
			//add the record and load into local
			$id=addDBRecord($opts);
			if(isNum($id)){
	            $local=getDBRecord(array('-table'=>'_users','-relate'=>1,'_id'=>$id));
	        }
	    }
	    $authname=$CONFIG['authhost'];
		if(is_array($local)){
			$USER=$local;
			foreach($tmp[0] as $key=>$val){
				$newkey="{$authname}_{$key}";
				$USER[$newkey]=$val;
	        }
	    }
	    $oldguid=$USER['guid'];
		$USER['_authhost']=$authname;
	}
}
else{
	//guid login request
	$recopts=array('-table'=>'_users','guid'=>$guid,'-relate'=>1);
	$USER=getDBRecord($recopts);
}
if(isUser() && isset($userfieldinfo['active']) && is_array($userfieldinfo['active']) && $userfieldinfo['active']['_dbtype']=='int' && $USER['active'] != 1){
	//do not allow users that are not active to log in
	$ok=editDBRecord(array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>'NULL'));
	unset($USER);
	unset($_SESSION['authcode']);
	unset($_SESSION['authkey']);
	setWasqlError(debug_backtrace(),"Login Error: Your account has been disabled");
	}
elseif(isUser()){
	if($guid != $oldguid && isset($_REQUEST['_noguid']) && $_REQUEST['_noguid']==1){$guid=$oldguid;}
	//echo "HERE{$guid}";
	setUserInfo($guid);
	}
else{
	unset($USER);
	unset($_SESSION['authcode']);
	unset($_SESSION['authkey']);
	if(isset($_REQUEST['_login']) && $_REQUEST['_login']==1){
		if(!isset($_REQUEST['_login_error'])){
			$_REQUEST['_login_error']="Login Error: Invalid username or password";
		}

	}
}
global $SETTINGS;
if(!isDBTable('_settings')){$ok=createWasqlTable('_settings');}
$uid=setValue(array($USER['_id'],0));
$SETTINGS=settingsValues($uid);
/************************************************************************************/

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
function setUserInfo($guid='NULL'){
	global $USER;
	if(!isUser()){return false;}
	//set WaSQL_PWE
	/* update access date */
	$adate=date("Y-m-d H:i:s");
	$finfo=getDBFieldInfo("_users");
	if($finfo['guid']['_dbtype'] != 'char'){
		$query="ALTER TABLE _users modify guid char(40) NULL;";
		$ok=executeSQL($query);
    }
	if(!isset($finfo['_sid'])){
		$query="ALTER TABLE _users ADD _sid varchar(150) NULL;";
		$ok=executeSQL($query);
    }
	$sessionID=session_id();
	$opts=array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'_adate'=>$adate,'_sid'=>$sessionID);

	if($finfo['password']['_dblength'] != 255){
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
	if(isset($finfo['_env'])){
		$opts['_env']=getRemoteEnv();
		$env_array=xml2array($opts['_env']);
		unset($env_array['env']['env']);
		if(isset($env_array['env'])){
			$USER['_env']=$env_array['env'];
		}
	}
	$USER['apikey']=encodeUserAuthCode();
	$auth=encrypt("{$USER['username']}:{$USER['apikey']}",$USER['_id']);
	$USER['_auth']="{$USER['_id']}.{$auth}";
	$USER['_adate']=$adate;
    /* replace the user password with stars */
	$USER['password']=preg_replace('/./','*',$USER['password']);
	ksort($USER);
	/* logout? */
	if(isset($_REQUEST['_logout']) && $_REQUEST['_logout']==1 && (!isset($_REQUEST['_login']) || $_REQUEST['_login'] != 1)){
		userLogout();
	}
	else{
		if($guid != $USER['guid']){$opts['guid']=$guid;}
		$opts['guid']=$guid;
		$num=editDBRecord($opts);
		//echo printValue($opts).printValue($num);
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
	if(!isNum($USER['_id'])){return false;}
	$opts=array('-table'=>'_users','-where'=>"_id={$USER['_id']}",'guid'=>'NULL');
	$num=editDBRecord($opts);
	$USER=array();
	unset($_SERVER['GUID']);
	unset($_SESSION['GUID']);
	unset($_SESSION['authcode']);
	unset($_SESSION['authkey']);
	unset($_COOKIE['GUID']);
	setcookie("GUID", "", time()-3600);
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
	//echo "cuser:".printValue($cuser);
	if(!is_array($cuser) && isNum($cuser)){
		$cuser=getDBRecord(array('-table'=>'_users','_id'=>$cuser));
    	}
    $utypes=mapDBDvalsToTvals("_users","utype");
    $info=array(
    	'type'=>$utypes[$cuser['utype']],
    	'username'=>$cuser['username']
		);
	//set user icon based on admin or not
	if($cuser['utype']==0){
		$info['icon']="/wfiles/iconsets/{$size}/user_admin.png";
		$info['class']='icon-user-admin w_primary';
    }
    else{
		$info['icon']="/wfiles/iconsets/{$size}/user.png";
		$info['class']='icon-user w_grey';
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
* returns a single user value.
* @return mixed
* @param string $field - field to be extracted from the current User array.
* @usage
* 	$username = userValue('username');
*	---
*	$firstName = userValue('firstname');
* @author slloyd
* @history - bbarten 2014-01-02 added documentation
*/
function userValue($field){
	global $USER;
	return $USER[$field];
	if(isset($USER[$field])){return $USER[$field];}
	return "Error! Unknown user field: {$field}";
}

//---------- begin function isUser ----
/**
* returns  true if a user is logged in
* @return boolean
* @usage
*	$userLoggedIn = isUser();
*	echo "Hello ".(isUser() ? userValue('username') : "Guest");
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
*	<?=userProfileForm();?> //echos an HTML form to change profile data
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
* returns a single string that can be used for user authorization for a particular server.
* @return string
* @usage
*	$userAuthCode = encodeUserAuthCode();
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
* @history - bbarten 2014-01-02 added documentation
*/
function encodeUserAuthCode($id=0){
	global $USER;
	if($id==0 || $id==$USER['_id']){$rec=$USER;}
	else{$rec=getDBRecord(array('-table'=>'_users','_id'=>$id,'-fields'=>'password,username,_id'));}
	$pw=userIsEncryptedPW($rec['password'])?userDecryptPW($rec['password']):$rec['password'];
	$auth=array(
		str_replace(':','',crypt($_SERVER['UNIQUE_HOST'],$rec['username'])),
		str_replace(':','',crypt($rec['username'],$pw)),
	    str_replace(':','',crypt($pw,$rec['username']))
	    );
	$code=encodeBase64(implode(':',$auth));
	//echo "PW:{$pw} [{$code}]<br>\n";
	return $code;
	//generate a user auth code  GUIDUsernamePasswordMM
	$raw='Wa6QI' .':'. $rec['username'] .':'. $pw;
	$string=encodeBase64($raw);
	return $string;
}

//---------- begin function userLoginForm ----
/**
* returns the User Login Form.
* @return string
* @param array $params - any additional parameters to be included.
*	['title']             adds a title above the login form. No Default
*	['username']         changes the text for the username field. Default is "Username"
*	['password']         changes the text for the password field. Default is "Password"
*	['login']             changes the text for the login submit button. Default is "Log In"
*	['remind']             changes the text for the "remind me" link. Default is "Remind Me"
*	['remind_title']     changes the title attribute for the "remind me" link. Default is "Click here if you need your login information emailed to you."
*	['format']             inline, inline, or standard. Default is "standard"
*	['icons']             true or false. if true, the Remind Me icon is displayed. Default is true.
*	['action']             changes the action attribute in the form tag. Defauls to the current page you are viewing
*	['onsubmit']         changes the onsubmit attribute in the form tag. Default is "return submitForm(this);"
*	['class']             changes the class attribute in the form tag. Default is "w_form"
*	['name']             changes the name attribute in the form tag. Default is "login"
*	['id']                changes the id attribute in the form tag. Default is "loginform"
*	['method']             changes the method attribute in the form tag. Default is "POST"
*	['style']             adds the style attribute in the form tag. No Default
*	[-noremind]			hide the remind me link
*	Additional params are passed through as hidden key/value pairs
* @usage
*	<?=userLoginForm();?> - returns the default wasql login form so a user can sign in.
*	---
*	<?=(isUser()?'Welcome User':userLoginForm());?>
* @author slloyd
* @history - bbarten 2014-01-02 updated documentation
*/
function userLoginForm($params=array()){
	global $PAGE;
	global $CONFIG;
	//setup the default params values
	$defaults=array(
		'-title'	=> '',
		'-login'	=> "Login",
		'-remind'	=> "Send Me My Login Info",
		'-remind_title'	=> "Click here if you need your login information emailed to you.",
		'-format'		=> "standard",
		'-icons'		=> true,
		'-action'		=> isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:"/{$PAGE['name']}",
		'-onsubmit'		=> "return submitForm(this);",
		'-class'		=> "w_form",
		'-name'			=> "loginform",
		'-id'			=> "loginform",
		'-method'		=> "POST",
		'-style'		=> ''
	);
	//backward compatibility settings
	if(isset($params['username_title'])){$params['-username']=$params['username_title'];unset($params['username_title']);}
	if(isset($params['password_title'])){$params['-password']=$params['password_title'];unset($params['password_title']);}
	if(isset($params['login_title'])){$params['-login']=$params['login_title'];unset($params['login_title']);}
	if(isset($params['message'])){$params['-title']=$params['message'];unset($params['message']);}
	if(isset($params['_action'])){$params['-action']=$params['_action'];unset($params['_action']);}

	//set params to default if they do not exist
	foreach($defaults as $key=>$val){
    	if(!isset($params[$key])){$params[$key]=$val;}
	}
	if(isset($CONFIG['login_title'])){$params['-title']=$CONFIG['login_title'];}
	elseif(isset($CONFIG['authhost'])){
		$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your "'.$CONFIG['authhost'].'" credentials.</div>'."\n";
	}
	elseif(isset($CONFIG['authldap']) && (!isset($CONFIG['authldap_network']) || stringBeginsWith($_SERVER['REMOTE_ADDR'],$CONFIG['authldap_network']))){
		$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your LDAP "'.$CONFIG['authldap'].'" credentials.</div>'."\n";
	}
	elseif(isset($CONFIG['authldaps']) && (!isset($CONFIG['authldap_network']) || stringBeginsWith($_SERVER['REMOTE_ADDR'],$CONFIG['authldap_network']))){
		$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your LDAP "'.$CONFIG['authldaps'].'" credentials.</div>'."\n";
	}
	elseif(isset($CONFIG['auth365'])){
		$params['-title'] .= '<div class="w_big"><b class="w_red">Note: </b>Use your portal.office365.com credentials.</div>'."\n";
	}
    if(!isset($params['-username'])){
    	$params['-username']='<span class="icon-user w_biggest w_grey"></span>';
	}
	if(!isset($params['-password'])){
    	$params['-password']='<span class="icon-lock w_biggest w_warning"></span>';
	}
	//return the user Login form
	$form='';
	$form .= '<div style="clear:both;">'."\n";
	//beginning form tag
	$attributes=array('id','name','style','class','method','action','onsubmit');
	$form .= '<form';
	foreach($attributes as $key){
		$dashkey="-{$key}";
		if(isset($params[$dashkey]) && strlen($params[$dashkey])){
			$form .= " {$key}=\"{$params[$dashkey]}\"";
		}
	}
	$form .= ">\n";
	$form .= '	<input type="hidden" name="guid" value="'.$_SERVER['GUID'].'">'."\n";
	$form .= '	<input type="hidden" name="_login" value="1">'."\n";
	$form .= '	<input type="hidden" name="_pwe" value="1">'."\n";
	//-title
    if(strlen($params['-title'])){
		$form .= '<div>'.$params['-title'].'</div>'."\n";
    	}
    //$params['-format']='inline';
    switch(strtolower($params['-format'])){
		case 'oneline':
			$form .= '<div id="w_loginform_oneline">'."\n";
			$form .= '<table>';
			$form .= '	<tr valign="middle" align="right">';
			$form .= '		<th class="w_align_left" style="padding-right:10px;"><label for="'.$params['-name'].'_username">'.$params['-username'].'</label></th><td>'.getDBFieldTag(array('-table'=>'_users','-field'=>"username",'id'=>$params['-name'].'_username','required'=>1,'tabindex'=>1)).'</td>'."\n";
			$form .= '		<th class="w_align_left" style="padding-right:10px;"><label for="'.$params['-name'].'_password">'.$params['-password'].'</label></th><td>'.getDBFieldTag(array('-table'=>'_users','inputtype'=>"password",'-field'=>"password",'id'=>$params['-name'].'_password','required'=>1,'tabindex'=>1)).'</td>'."\n";
			$form .= '		<td align="right" style="padding-right:10px;"><button class="btn btn-default w_formsubmit" tabindex="3" type="submit">'.$params['-login'].'</button></td>'."\n";
			if(isset($CONFIG['facebook_appid'])){
				if(!isset($CONFIG['facebook_text'])){$CONFIG['facebook_text']='Login with Facebook';}
    			$form .= '<td style="padding-right:10px;"><div style="width:152px;overflow:hidden;"><fb:login-button size="medium" scope="public_profile,email" onlogin="facebookCheckLoginState(1);">'.$CONFIG['facebook_text'].'</fb:login-button></div></td>';
			}
			if(isset($CONFIG['google_appid'])){
    			$form .= '<td style="padding-right:10px;"><div id="google_login"></div></td>';
			}
			if(!isset($params['-noremind'])){
				$form .= '		<td class="w_align_left" style="padding-right:10px;">'."\n";
				$form .= '				<a title="'.$params['-remind_title'].'" href="#" onClick="remindMeForm(document.'.$params['-name'].'.username.value);return false;" class="w_smaller w_link w_dblue">';
				if($params['-icons']){
					$form .= '<span class="icon-mail w_biggest" style="padding-right:10px;"></span>';
				}
				$form .= " {$params['-remind']}</a>\n";
				$form .= '		</td>'."\n";
			}
			$form .= '	</tr>'."\n";
			$form .= '</table>'."\n";
			if(isset($_REQUEST['_login_error'])){
				$form .= '<div style="display:inline;margin-left:25px;" class="w_red w_small icon-warning" id="loginform_msg"> '.$_REQUEST['_login_error'].'</div>'."\n";
			}
			$form .= '</div>'."\n";
			break;
		case 'inline':
			$form .= '<div id="w_loginform_inline">'."\n";
			$form .= '<table>';
			$form .= '	<tr valign="middle" align="right">';
			$form .= '		<th class="w_align_left"><label for="'.$params['-name'].'_username" style="padding:0px;">'.$params['-username'].'</label></th>'."\n";
			$form .= '		<th class="w_align_left"><label for="'.$params['-name'].'_password" style="padding:0px;">'.$params['-password'].'</label></th>'."\n";
			if(isset($CONFIG['facebook_appid']) || isset($CONFIG['google_appid'])){
				$form .= '		<td class="w_align_left" colspan="2">'."\n";
			}
			else{
				$form .= '		<td class="w_align_left">'."\n";
			}
			if(!isset($params['-noremind'])){
				$form .= '				<a title="'.$params['-remind_title'].'" href="#" onClick="remindMeForm(document.'.$params['-name'].'.username.value);return false;" class="w_smaller w_link w_dblue">';
				if($params['-icons']){
					$form .= '<span class="icon-mail w_biggest"  style="padding-right:10px;"></span>';
				}
				$form .= " {$params['-remind']}</a>\n";
			}
			$form .= '		</td>'."\n";
			$form .= '	</tr>'."\n";
			$form .= '	<tr valign="middle" align="right">';
			$form .= '		<td>'.getDBFieldTag(array('-table'=>'_users','-field'=>"username",'id'=>$params['-name'].'_username','required'=>1,'tabindex'=>1)).'</td>'."\n";
			$form .= '		<td>'.getDBFieldTag(array('-table'=>'_users','inputtype'=>"password",'-field'=>"password",'id'=>$params['-name'].'_password','required'=>1,'tabindex'=>2)).'</td>'."\n";
			$form .= '		<td align="right"><button class="btn btn-default w_formsubmit" tabindex="3" type="submit">'.$params['-login'].'</button></td>'."\n";
			if(isset($CONFIG['facebook_appid'])){
				if(!isset($CONFIG['facebook_text'])){$CONFIG['facebook_text']='Login with Facebook';}
    			$form .= '<td style="padding:3px;"><div style="width:152px;overflow:hidden;"><fb:login-button size="medium" scope="public_profile,email" onlogin="facebookCheckLoginState(1);">'.$CONFIG['facebook_text'].'</fb:login-button></div></td>';
			}
			if(isset($CONFIG['google_appid'])){
    			$form .= '<td style="padding:3px;"><div id="google_login"></div></td>';
			}
			$form .= '	</tr>'."\n";
			$form .= '</table>'."\n";
			if(isset($_REQUEST['_login_error'])){
				$form .= '<div style="display:inline;margin-left:15px;" class="w_red w_small icon-warning" id="loginform_msg"> '.$_REQUEST['_login_error'].'</div>'."\n";
			}
			$form .= '</div>'."\n";
			break;
		default:
			$form .= '<div id="w_loginform_default">'."\n";
			$form .= '<table>';
			$form .= '	<tr valign="middle" align="right">';
			$form .= '		<td class="w_align_left" title="Username" style="padding-right:10px;"><label for="'.$params['-name'].'_username">'.$params['-username'].'</label></td>'."\n";
			$form .= '		<td>'.getDBFieldTag(array('-table'=>'_users','-field'=>"username",'id'=>$params['-name'].'_username','required'=>1,'tabindex'=>1,'placeholder'=>'username')).'</td>'."\n";
			$form .= '		<td rowspan="2" valign="top"><button class="btn btn-default w_formsubmit" tabindex="3" type="submit">'.$params['-login'].'</button></td>'."\n";
			$form .= '	</tr>'."\n";
			$form .= '	<tr valign="middle" align="right">';
			$form .= '		<td class="w_align_left" title="Password" style="padding-right:10px;"><label for="'.$params['-name'].'_password">'.$params['-password'].'</label></td>'."\n";
			$form .= '		<td>'.getDBFieldTag(array('-table'=>'_users','inputtype'=>"password",'-field'=>"password",'id'=>$params['-name']."_password",'required'=>1,'tabindex'=>2,'placeholder'=>'password')).'</td>'."\n";
			$form .= '	</tr>'."\n";
			if(!isset($params['-noremind'])){
				$form .= '	<tr valign="middle" align="right">';
				$form .= '		<td class="w_align_left" style="padding-right:10px;">'."\n";
				if($params['-icons']){
					$form .= '			<span class="icon-mail"></span> ';
				}
				$form .= '</td><td colspan="2" class="w_align_left">'."\n";
				$form .= '			<a title="'.$params['-remind_title'].'" href="#" onClick="remindMeForm(document.'.$params['-name'].'.username.value);return false;" class="w_smaller w_link w_dblue">'.$params['-remind'].'</a>'."\n";
				$form .= '		</td>'."\n";
				$form .= '	</tr>'."\n";
			}
			if(isset($_REQUEST['_login_error'])){
				$form .= '<tr><td><span class="icon-warning w_red"></span></td><td colspan="2" class="w_red w_small" id="loginform_msg"> '.$_REQUEST['_login_error'].'</td></tr>'."\n";
			}
			$form .= '</table>'."\n";
			$form .= '<table><tr>'."\n";
			if(isset($CONFIG['facebook_appid'])){
				loadExtrasJs('facebook_login');
				checkDBTableSchema('_users');
				if(!isset($CONFIG['facebook_text'])){$CONFIG['facebook_text']='Login with Facebook';}
    			$form .= '<td style="margin-top:15px;"><div style="width:152px;overflow:hidden;"><fb:login-button size="medium" scope="public_profile,email" onlogin="facebookCheckLoginState(1);">'.$CONFIG['facebook_text'].'</fb:login-button></div></td>';
			}
			if(isset($CONFIG['google_appid'])){
    			$form .= '<td style="margin-top:15px;padding-left:10px;"><div id="google_login"></div></td>';
			}
			$form .= '</tr></table>'."\n";
			$form .= '</div>'."\n";
			break;
	}
	//pass thru params
	$form .= '<div style="display:none;" id="passthru">'."\n";
	foreach($params as $key=>$val){
		if(stringBeginsWith($key,'-')){continue;}
		$form .= '	<textarea name="'.$key.'">'.$val.'</textarea>'."\n";
    }
    $form .= '</div>'."\n";
	$form .= '</form>'."\n";
	$form .= buildOnLoad("document.{$params['-name']}.username.focus();");
	$form .= '</div>'."\n";
	//$form .=printValue($_REQUEST);
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
	//echo $query.printValue($recs);exit;
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
	$rtn =  '<div class="w_wpass" style="z-index:998;position:relative;display:table-cell;">'."\n";
	$rtn .= '	<input id="wpass_search" class="form-control" value="" list="wpass_datalist" oninput="return wpassInput(this.value);" style="width:40px;" onfocus="this.style.width=\'250px\';" onblur="this.style.width=\'40px\';" placeholder="wPass search" />'."\n";
	$rtn .= '	<img onclick="wpassInput(0);" title="click to add new wPass record" class="w_pointer w_middle" src="/wfiles/_wpass.png" alt="add" />'."\n";
	$rtn .= '	<div id="wpass_info" style="z-index:999;position:absolute;top:30px;right:0px;background:#FFF;"></div>'."\n";
	$rtn .= '	<div style="display:none;"><div id="wpass_nulldiv"></div></div>'."\n";
	$query="select _id,category,title from _wpass where users like '{$USER['_id']}:%' or users like '%:{$USER['_id']}' or users like '{$USER['_id']}' or users like '%:{$USER['_id']}:%' or  _cuser={$USER['_id']} order by category,title";
	$recs=getDBRecords(array('-query'=>$query));
	$rtn .= '	<datalist id="wpass_datalist">'."\n";
	if(is_array($recs)){
		foreach($recs as $rec){
			$rtn .= '	<option value="'.$rec['_id'].'">'."{$rec['category']} - {$rec['title']}".'</option>'."\n";
    	}
	}
    $rtn .= '	</datalist>'."\n";
	$rtn .= '</div>'."\n";
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
    	$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'."\n";
    	$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="new" /> New wPass Record</div>'."\n";
    	$rtn .= addEditDBForm(array(
			'-table'=>'_wpass',
			'-name'=> '_wpass_addedit',
			'-action'=>'/php/index.php',
			'_wpass_addedit'=>1,
			'-fields'	=> "category:title,user:pass,url,notes,users",
			'-onsubmit'=>"ajaxSubmitForm(this,'wpass_info');return false;"
		));
		$rtn .= '<div class="w_smaller"> - Fields marked with ** are stored in the database encrypted</div>'."\n";
		$rtn .= '<div class="w_smaller"> - only the creator of this record can edit it.</div>'."\n";
		$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'."\n";
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
            	$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="denied" /> Denied Access</div>'."\n";
			}
			$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'."\n";
			$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="edit" /> Edit wPass Record</div>'."\n";
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
			$rtn .= '<div class="w_smaller"> - Fields marked with ** are stored in the database encrypted</div>'."\n";
			$rtn .= '<div class="w_smaller"> - only the creator of this record can edit it.</div>'."\n";
			if($rec['_cuser']==$USER['_id']){
				$rtn .= '<div class="w_smaller"> - Created by you on '.$rec['_cdate'].'</div>'."\n";
			}
			else{
            	$rtn .= '<div class="w_smaller"> - Created by '."{$rec['_cuser_ex']['firstname']} {$rec['_cuser_ex']['lastname']}".' on '.$rec['_cdate'].'</div>'."\n";
			}
			if(strlen($rec['_edate']) && isset($rec['_euser_ex']['firstname'])){
            	$rtn .= '<div class="w_smaller"> - Last edited by '."{$rec['_euser_ex']['firstname']} {$rec['_euser_ex']['lastname']}".' on '.$rec['_cdate'].'</div>'."\n";
			}
			$rtn .= '<div class="w_right w_pointer w_red w_padright" onclick="setText(\'wpass_info\',\'\');">close</div>'."\n";
		}
		else{
			$rtn .= '<div class="w_bold" style="font-size:24px;font-family:arial;color:#ceb78b;"><img src="/wfiles/iconsets/32/keylock.png" class="w_middle" alt="no record" /> No such record</div>'."\n";
		}
	}
	return $rtn;
}
