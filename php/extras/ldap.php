<?php
/* 
	LDAP functions
	References:
		http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
		http://stackoverflow.com/questions/14815142/php-ldap-bind-on-secure-remote-server-windows-fail
		http://forums.phpfreaks.com/topic/129205-solved-disabling-account-using-ldap-and-php/
		
        http://pear.php.net/package/Net_LDAP2/download
        http://stackoverflow.com/questions/8276682/wamp-2-2-install-pear

*/
$progpath=dirname(__FILE__);
//---------- begin function ldapAddUser--------------------
/**
* @describe add entry to LDAP server
* @param params array any key/value pair associated with this user
* @return boolean - true on success, false on failure
* @usage $rec=ldapAddUser(array('cn'=>'John Doe','sn'=>'Jones','mail'=>'jdoe@mycompany.com));
* @link http://php.net/manual/en/function.ldap-add.php
*/
function ldapAddUser($params){
	global $ldapInfo;
	if(isset($params['cn']) && !isset($params['samaccountname'])){
    	$params['samaccountname']=$params['cn'];
	}
	//create user class if not defined
	if(!isset($params['objectclass'])){
		$params['objectclass']= array("top","person","organizationalPerson","user");
	}
	//user password
	if(!isset($params['userpassword']) && isset($params['password'])){
		$params["userpassword"] = '{md5}' . base64_encode(pack('H*', md5($params['password'])));
		//unset the password
		unset($params['password']);
	}
	//prevent the user from being disabled
	$params['UserAccountControl']="512";
	//check for mapped fields
	foreach($params as $key=>$val){
    	$mkey=ldapMapField($key);
    	if($mkey != $key){
        	$params[$mkey]=$val;
        	unset($params[$key]);
		}
	}
	//call ldap_add to add the entry
	$ldapInfo['lastdn']= "cn={$params['cn']},{$ldapInfo['basedn']}";
	if(!ldap_add($ldapInfo['connection'], $ldapInfo['lastdn'], $params)){
		$enum=ldap_errno($ldapInfo['connection']);
		if(isNum($enum)){
        	$msg=ldap_err2str( $enum );
			return "ldapAddUser {$enum} -{$msg}";
		}
	}
	return 'success';
}
function ldapErrorMessage($no){
	return ldap_err2str($no);
}
//---------- begin function LDAP Auth--------------------
/**
* @describe used ldap to authenticate user and returns user ldap record
* @param params array
*	-host - LDAP host
*	-username
*	-password
*	[-domain] - domain to bind to. defaults to -host
*	[-secure] - prepends ldaps:// to the host name. Use for secure ldap servers
* @return mixed - ldap user record array on success, error msg on failure
* @usage $rec=LDAP Auth(array('-host'=>'myldapserver','-username'=>'myusername','-password'=>'mypassword'));
*/
function ldapAuth($params=array()){
	if(!isset($params['-host'])){return 'LDAP Auth Error: no host';}
	if(!isset($params['-username'])){return 'LDAP Auth Error: no username';}
	if(!isset($params['-password'])){return 'LDAP Auth Error: no password';}
	if(!isset($params['-domain'])){$params['-domain']=$params['-host'];}
	if(!isset($params['-checkmemberof'])){$params['-checkmemberof']=1;}
	global $CONFIG;
	if(!isset($params['-bind'])){$params['-bind']="{$params['-username']}@{$params['-domain']}";}
	$ldap_base_dn = array();
	$hostparts=preg_split('/\./',$params['-domain']);
	foreach($hostparts as $part){
		$ldap_base_dn[]="dc={$part}";
	}
	$params['basedn']=implode(',',$ldap_base_dn);
	if($params['-secure']){
		$params['-host']='ldaps://'.$params['-host'];
	}
	else{
    	$params['-host']='ldap://'.$params['-host'];
	}
	//connect

	global $ldapInfo;
	$ldapInfo=array('dirty'=>0);
	$ldapInfo['connection'] = ldap_connect($params['-host']);
	if(!$ldapInfo['connection']){return 'LDAP Auth Error: unable to connect to host';}
	// We need this for doing an LDAP search.
	ldap_set_option($ldapInfo['connection'], LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_NETWORK_TIMEOUT, 10);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_SIZELIMIT, 500);
    ldap_set_option($ldapInfo['connection'], LDAP_OPT_TIMELIMIT, 300);
    if(!isset($ldapInfo['page_size'])){
		ldap_get_option($ldapInfo['connection'],LDAP_OPT_SIZELIMIT,$ldapInfo['page_size']);
	}
    $enum=ldap_errno($ldapInfo['connection']);
    $msg=ldap_err2str( $enum );
    //echo printValue($params);exit;
	//bind
    $bind=ldap_bind($ldapInfo['connection'],$params['-bind'],$params['-password']);
    if(!$bind){
        $enum=ldap_errno($ldapInfo['connection']);
        $msg=ldap_err2str( $enum );
		ldap_unbind($ldapInfo['connection']); // Clean up after ourselves.
		$params['-password']=preg_replace('/./','*',$params['-password']);
		return "LDAP Auth Error: auth failed. Err:{$enum}, Msg:{$msg} .. ".printValue($params);
	}
	foreach($params as $k=>$v){
    	$ldapInfo[$k]=$v;
	}
	//set cookie to blank - used for paging results
	$cookie='';
	//set paging to 1000
	ldap_control_paged_result($ldapInfo['connection'], 500, true, $cookie);
    //now get this users record and return it
	$rec=array();
	//$search_filter = "(&(objectCategory=person))";
	//set search filter to be the current username so we get the current user record back
	$ldapInfo['lastsearch'] = "(&(objectClass=user)(sAMAccountName={$params['-username']}))";
	$result = ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $ldapInfo['lastsearch']);
	//echo "result".printValue($result).printValue($params);exit;
	if (FALSE !== $result){
		$entries = ldap_get_entries($ldapInfo['connection'], $result);
	    if ($entries['count'] == 1){
	    	$rec=ldapParseEntry($entries[0],$params['-checkmemberof']);
	    	//echo printValue($rec);ldapClose();exit;
	    	if(is_array($rec)){return $rec;}
	    	return 'LDAP Auth Error 2: unable to parse LDAP entry'.printValue($rec).printValue($entries[0]);
		}
		ldapClose();
		//ldap_unbind($ldap_connection); // Clean up after ourselves.
    	return 'LDAP Auth Error: unable to get a unique LDAP user object'.printValue($entries);
	}
	ldapClose();
    return 'LDAP Auth Error: unable to search'.printValue($result);
}
//---------- begin function ldapConvert2UserRecord--------------------
/**
* @describe converts an ldap user record to a record for the _users table
* @param params array
* @return array
* @usage $rec=ldapConvert2UserRecord($rec);
*/
function ldapClose(){
	global $ldapInfo;
	@ldap_unbind($ldapInfo['connection']);
	$ldapInfo=array();
}
//---------- begin function ldapGetRecords--------------------
/**
* @describe returns a list of LDAP records based on parameters
* @param params array - filters to apply
*	active=>1  filter our inactive accouts
*	email=>%@doterra.com  email must end with @doterra.com
*	firstname=>ste%  firstname must start with ste
*	lastname=>%jon%  lastname much contain jon
*	lastname=>~jon  lastname must contain jon (same as %jon%)
*	lastname=>jones lastname must equal jones
*	[-index]=>{field} returns array with {field} value as the index
*	[-fields]=list of fields - limits fields returned to this list. Either an array or a comma separated list of fields.
* @return array or null if blank
* @usage $recs=ldapGetRecords($params);
* @exclude - not ready yet
* @reference https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
*/
function ldapGetUsers($params=array()){
	global $ldapInfo;
	//set the pageSize dynamically
	if(!isset($ldapInfo['page_size'])){
		ldap_get_option($ldapInfo['connection'],LDAP_OPT_SIZELIMIT,$ldapInfo['page_size']);
	}
	//set search to perform
	$ldapInfo['lastsearch'] = "(&(objectClass=user)(objectCategory=person))";
	//set cookie to blank - used for paging results
	$cookie='';
	//initialize the recs array
	$recs=array();
	$list=getStoredValue("return ldapGetUsersAll();",$ldapInfo['dirty'],12);
	foreach($list as $rec){
		if(isset($params['-index'])){
			$index_field=$params['-index'];
			if(!isset($rec[$index_field]) || !strlen($rec[$index_field])){
            	$rec[$index_field]='?';
			}
		}

    	$skip=0;
    	if(isset($rec['objectguid'])){
        	$rec['objectguid_base64']=encodeBase64($rec['objectguid']);
		}
		if(isset($rec['objectsid'])){
        	$rec['objectsid_base64']=encodeBase64($rec['objectsid']);
		}
		foreach($params as $k=>$v){
			//skip instruction params - ones that start with a dash
			if(preg_match('/^\-/',$k)){continue;}
			if($k=='*'){
				$found=0;
            	foreach($rec as $rk=>$rv){
                	if(preg_match('/^\%(.+)\%$/',$v,$m)){
						//contains
						if(stringContains($rv,$m[1])){$found=1;break;}
					}
					elseif(preg_match('/^\%(.+)$/',$v,$m)){
						//ends with
						if(stringEndsWith($rv,$m[1])){$found=1;break;}
					}
					elseif(preg_match('/^(.+)\%$/',$v,$m)){
						//Begins With
						if(stringBeginsWith($rv,$m[1])){$found=1;break;}
					}
					elseif(preg_match('/^\~(.+)$/',$v,$m)){
						//contains
						if(stringContains($rv,$m[1])){$found=1;break;}
					}
					elseif((strtolower($rv)=='null' || $rv=='') && !strlen(trim($v))){
						//is null (empty)
                    	$found==1;break;
					}
					else{
		                if(strtolower($rv) == strtolower($v)){$found=1;break;}
					}
				}
				if(!$found){$skip=1;}
				continue;
			}
            if(!isset($rec[$k])){$skip=1;break;}
			if(preg_match('/^\%(.+)\%$/',$v,$m)){
				//contains
				if(!stringContains($rec[$k],$m[1])){$skip=1;break;}
			}
			elseif(preg_match('/^\%(.+)$/',$v,$m)){
				//ends with
				if(!stringEndsWith($rec[$k],$m[1])){$skip=1;break;}
			}
			elseif(preg_match('/^(.+)\%$/',$v,$m)){
				//Begins With
				if(!stringBeginsWith($rec[$k],$m[1])){$skip=1;break;}
			}
			elseif(preg_match('/^\~(.+)$/',$v,$m)){
				//contains
				if(!stringContains($rec[$k],$m[1])){$skip=1;break;}
			}
			else{
                	if(strtolower($rec[$k]) != strtolower($v)){$skip=1;break;}
			}
		}
		if($skip==1){continue;}
		ksort($rec);
		//fields
		if(isset($params['-fields'])){
        	if(!is_array($params['-fields'])){
            	$params['-fields']=preg_split('/\,+/',$params['-fields']);
			}
            $xrec=array();
            foreach($params['-fields'] as $field){
                if(isset($rec[$field])){$xrec[$field]=$rec[$field];}
			}
			$rec=$xrec;
			unset($xrec);
		}
		//index?
		if(isset($params['-index'])){
			$index_field=$params['-index'];
			if(isset($rec[$index_field]) && strlen($rec[$index_field])){
        		$index_value=$rec[$index_field];
        		if(isset($recs[$index_value])){
                	$x=1;
                	while(isset($recs["{$index_value}_{$x}"])){
						$x++;
					}
					$index_value="{$index_value}_{$x}";
				}
				ksort($rec);
        		$recs[$index_value]=$rec;
			}
		}
		else{
			$recs[]=$rec;
		}
	}
	return $recs;
}

function ldapGetUsersAll(){
	global $ldapInfo;
	//set the pageSize dynamically
	if(!isset($ldapInfo['page_size'])){
		ldap_get_option($ldapInfo['connection'],LDAP_OPT_SIZELIMIT,$ldapInfo['page_size']);
	}
	//set search to perform
	$ldapInfo['lastsearch'] = "(&(objectClass=user)(objectCategory=person))";
	//set cookie to blank - used for paging results
	$cookie='';
	//initialize the recs array
	$recs=array();
	//loop through based on page_size and get the records
	do {
        ldap_control_paged_result($ldapInfo['connection'], $ldapInfo['page_size'], true, $cookie);
        $result = ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $ldapInfo['lastsearch']);
        //echo printValue($cookie).printValue($ldapInfo);exit;
        $entries = ldap_get_entries($ldapInfo['connection'], $result);
        foreach ($entries as $e) {
			//lowercase the keys
			$e=array_change_key_case($e,CASE_LOWER);
			//do not include Service Accounts
			if(isset($e['distinguishedname']) && stringContains(ldapValue($e['distinguishedname']),'Service Account')){continue;}
			if(isset($e['description']) && stringContains(ldapValue($e['description']),'Service Account')){continue;}
			//do not include Build-in accounts
			if(isset($e['description']) && stringContains(ldapValue($e['description']),'Built-in account')){continue;}
			//require a memberof key
			//echo printValue($e);
			if(!isset($e['memberof'])){$e['memberof']='USERS';}
			$rec=ldapParseEntry($e);
			$recs[]=$rec;
        }
    	ldap_control_paged_result_response($ldapInfo['connection'], $result, $cookie);
    	//if(count($recs) > 400){return $recs;}

	} while($cookie !== null && $cookie != '');
	return $recs;
}
//---------- begin function ldapSearch--------------------
/**
* @describe returns a list of LDAP users based on search 
* @param str string - string to search for
* @param checkfields string - comma separated list of attributes to search in. defaults to sAMAccountName,name,email,title
* @param returnfields string - comma separated list of attributes to return. defaults to *
* @return recs array - record sets of users that match
* @usage $recs=ldapSearch('billy','name,email,title','dn,cn,sn,title,telephonenumber,givenname,displayname,memberof,employeeid,samaccountname,mail,photo');
* @reference https://stackoverflow.com/questions/48310553/how-to-do-ldapsearch-with-multiple-filters
*/
function ldapSearch($str,$checkfields='sAMAccountName,name,email,title',$returnfields=array()){
	global $ldapInfo;
	//set the pageSize dynamically
	if(!isset($ldapInfo['page_size'])){
		ldap_get_option($ldapInfo['connection'],LDAP_OPT_SIZELIMIT,$ldapInfo['page_size']);
	}
	//set defaults
	if(!is_array($checkfields) && strlen($checkfields)){
		$checkfields=preg_split('/\,/',$checkfields);
	}
	if(!count($checkfields)){
		debugValue("ldapSearch requires checkfields");
		return array();
	}
	$filters=array();
	foreach($checkfields as $checkfield){
		$filters[]="{$checkfield}=*{$str}*";
	}
	$filterstr=implode(')(',$filters);
	$filter="(&(objectClass=user)(objectCategory=person)(|({$filterstr})))";
	if(!is_array($returnfields) && strlen($returnfields)){
		$returnfields=preg_split('/\,/',$returnfields);
	}
	$recs=array();
	//loop through based on page_size and get the records
	do {
        ldap_control_paged_result($ldapInfo['connection'], $ldapInfo['page_size'], true, $cookie);
        if(count($returnfields)){
        	$result=ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $filter,$returnfields);
        }
        else{
        	$result=ldap_search($ldapInfo['connection'], $ldapInfo['basedn'], $filter);
        }
        //echo printValue($cookie).printValue($ldapInfo);exit;
        $entries = ldap_get_entries($ldapInfo['connection'], $result);
        foreach ($entries as $e) {
			//lowercase the keys
			$e=array_change_key_case($e,CASE_LOWER);
			//do not include Service Accounts
			if(isset($e['distinguishedname']) && stringContains(ldapValue($e['distinguishedname']),'Service Account')){continue;}
			if(isset($e['description']) && stringContains(ldapValue($e['description']),'Service Account')){continue;}
			//do not include Build-in accounts
			if(isset($e['description']) && stringContains(ldapValue($e['description']),'Built-in account')){continue;}
			//require a dn
			if(!isset($e['dn'])){continue;}
			//echo printValue($e);
			if(!isset($e['memberof'])){$e['memberof']='USERS';}
			$rec=ldapParseEntry($e);
			$recs[]=$rec;
        }
    	ldap_control_paged_result_response($ldapInfo['connection'], $result, $cookie);
    	//if(count($recs) > 400){return $recs;}

	} while($cookie !== null && $cookie != '');
	return $recs;
}
function ldapConvert2UserRecord($rec){
	return $rec;
}
//---------- begin function ldapIsActiveRecord--------------------
/**
* @describe determines if an ldap record is active or not
* @param ldaprec array
* @return int 0=disables, 1=active
* @usage $active=ldapIsActiveRecord($lrec);
*/
function ldapIsActiveRecord($lrec=array()){
	//lowercase the keys, if not already
	if(!isset($lrec['memberof'])){
		$lrec=array_change_key_case($lrec,CASE_LOWER);
	}
	if(!isset($lrec['useraccountcontrol'])){return 0;}
	$flags=ldapValue($lrec['useraccountcontrol']);
	$bool=$flags & 0x002;
	if(!$bool){return 1;}
	return 0;
}
//---------- begin function ldapMapField--------------------
/**
* @describe parses an ldap entry and returns a more human friendly record set
* @param ldaprec array
* @return array
* @usage $lrec=ldapMapField($lrec);
*/
function ldapMapField($field){
	switch(strtolower(trim($field))){
    	case 'username':return 'samaccountname';break;
    	case 'city':return 'l';break;
    	case 'country':return 'c';break;
    	case 'country_ex':return 'co';break;
    	case 'lastname':return 'sn';break;
    	case 'firstname':return 'givenname';break;
    	case 'name':return 'displayname';break;
    	case 'email':return 'mail';break;
    	case 'state':return 'st';break;
    	case 'address1':return 'streetaddress';break;
    	case 'phone':return 'telephonenumber';break;
    	case 'zip':return 'postalcode';break;

	}
	return $field;
}
//---------- begin function ldapParseEntry--------------------
/**
* @describe parses an ldap entry and returns a more human friendly record set
* @param ldaprec array
* @return array
* @usage $lrec=ldapParseEntry($lrec);
*/
function ldapParseEntry($lrec=array(),$checkmemberof=1){
	//lowercase the keys, if not already
	if(!isset($lrec['memberof'])){
		$lrec=array_change_key_case($lrec,CASE_LOWER);
	}
	//require a memberof key
	if($checkmemberof==1 && !isset($lrec['memberof'])){return 'NO MEMBER OF';}
	$rec=array('active'=>ldapIsActiveRecord($lrec));
	$skipkeys=array(
		'logonhours','msexchsafesendershash','count','usercertificate',
		'msexchblockedsendershash','msexchsaferecipientshash','msrtcsip-userroutinggroupid'
	);
	//echo printValue($lrec);exit;
	foreach($lrec as $key=>$val){
		//skip numeric keys - not needed
    	if(is_numeric($key)){continue;}
    	//skip keys
    	if(in_array($key,$skipkeys)){continue;}

        switch(strtolower($key)){
			case 'thumbnailphoto':
			case 'jpegphoto':
			case 'photo':
				if(isset($val[0]) && strlen($val[0])){
					$rec['photo']="data:image/jpeg;base64,".base64_encode($val[0]);
					//echo "<img src=\"{$rec['photo']}\" title=\"{$lrec['samaccountname'][0]}\" style=\"max-width:200px;max-height:200px;\" />".PHP_EOL;
				}
			break;
			case 'objectguid':
			case 'objectsid':
				//binary ojects - base64 encode them
				$rec[$key]=base64_encode(ldapValue($val));
				$rec['guid']=encodeCRC($rec[$key]);
			break;
            case 'whencreated':
            case 'whenchanged':
            case 'badpasswordtime':
            case 'dscorepropagationdata':
            case 'accountexpires':
            case 'lastlogontimestamp':
            case 'lastlogon':
            case 'pwdlastset':
                $rec[$key]=ldapValue($val);
                $rec["{$key}_unix"]=ldapTimestamp($val[0]);
                $rec["{$key}_date"]=date('Y-m-d h:i a',$rec["{$key}_unix"]);
            break;
            case 'memberof':
            case 'distinguishedname':
            case 'dn':
            case 'directreports':
            	$rec[$key]=ldapValue($val);
                $tmp=preg_split('/\,/',ldapValue($val));
                $parts=array();
                foreach($tmp as $part){
					list($k,$v)=preg_split('/\=/',$part,2);
					if(!is_array($parts[$k])){$parts[$k]=array();}
                    if(!in_array($v,$parts[$k])){$parts[$k][]=$v;}
				}
				foreach($parts as $k=>$v){
					$k=strtolower($k);
					$rec["{$key}_{$k}"]=implode(',',$v);
				}
            break;
            case 'samaccountname':$rec['username']=ldapValue($val);break;
          	case 'l':$rec['city']=ldapValue($val);break;
          	case 'c':$rec['country']=ldapValue($val);break;
          	case 'co':$rec['country_ex']=ldapValue($val);break;
          	case 'sn':$rec['lastname']=ldapValue($val);break;
          	case 'givenname':$rec['firstname']=ldapValue($val);break;
          	case 'displayname':$rec['name']=ldapValue($val);break;
          	case 'mail':$rec['email']=ldapValue($val);break;
          	case 'homephone':$rec['phone_home']=ldapValue($val);break;
          	case 'mobile':$rec['phone_mobile']=ldapValue($val);break;
          	case 'postalcode':$rec['zip']=ldapValue($val);break;
          	case 'st':$rec['state']=ldapValue($val);break;
          	case 'streetaddress':$rec['address1']=ldapValue($val);break;
          	case 'telephonenumber':$rec['phone']=ldapValue($val);break;
          	case 'manager':
          		$val=ldapValue($val);
          		if(preg_match('/CN\=(.+?)\,/',$val,$m)){$rec['manager']=$m[1];}
          		elseif(preg_match('/CN\=(.+?)$/',$val,$m)){$rec['manager']=$m[1];}
				else{$rec['manager']=$val;}
			break;
            default:
                $rec[$key]=ldapValue($val);
            break;
		}
	}
	$rec['password']='AD'.$rec['guid'];
	ksort($rec);
	return $rec;
}
//---------- begin function ldapModify--------------------
/**
* @describe modifies set Active Directory Record with changes
* @param objectguid string
* @param changes array
* @return boolean
* @usage $ok=ldapModify($objectguid,$changes);
*/
function ldapModify($objectguid,$changes){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
	if(!ldap_modify($ldapInfo['connection'], $dn, $changes)){
    	$enum=ldap_errno($ldapInfo['connection']);
    	if(isNum($enum)){
        	$msg=ldap_err2str( $enum );
			return "ldapModify Error {$enum} -{$msg}";
		}
	}
	return '';
}
//---------- begin function ldapAddAttribute--------------------
/**
* @describe adds an Active Directory Record attribute to the user record
* @param objectguid string
* @param changes array
* @return boolean
* @usage $ok=ldapAddAttribute($objectguid,$changes);
*/
function ldapAddAttribute($objectguid,$changes){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
	$result = ldap_mod_add($ldapInfo['connection'], $dn, $changes);
	return $result;
}
//---------- begin function ldapModifyAttribute--------------------
/**
* @describe modifies set Active Directory Record attribute with changes
* @param objectguid string
* @param changes array
* @return boolean
* @usage $ok=ldapModifyAttribute($objectguid,$changes);
*/
function ldapEditAttribute($objectguid,$changes){
	return ldapModifyAttribute($objectguid,$changes);
}
function ldapModifyAttribute($objectguid,$changes){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
	$result = ldap_mod_replace($ldapInfo['connection'], $dn, $changes);
	return $result;
}
//---------- begin function ldapEnable --------------------
/**
* @describe enables specified Active Directory Record
* @param objectguid string
* @return boolean
* @usage $ok=ldapEnable($objectguid);
*/
function ldapEnable($objectguid){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
	$ac=$recs[0]['useraccountcontrol'];
	//$disable=($ac |  2); // set all bits plus bit 1 (=dec2)
	$enable =($ac & ~2); // set all bits minus bit 1 (=dec2)
	$changes=array();
	$changes["useraccountcontrol"][0]=$enable;
	$result = ldap_modify($ldapInfo['connection'], $dn, $changes);
	return $result;
}
//---------- begin function ldapDisable --------------------
/**
* @describe disables specified Active Directory Record
* @param objectguid string
* @return boolean
* @usage $ok=ldapDisable($objectguid);
*/
function ldapDisable($objectguid){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
	$ac=$recs[0]['useraccountcontrol'];
	$disable=($ac |  2); // set all bits plus bit 1 (=dec2)
	//$enable =($ac & ~2); // set all bits minus bit 1 (=dec2)
	$changes=array();
	$changes["useraccountcontrol"][0]=$disable;
	$result = ldap_modify($ldapInfo['connection'], $dn, $changes);
	return $result;
}
//---------- begin function ldapTimestamp--------------------
/**
* @describe returns an ldap timestamp
* @param datestr string
* @return string
* @usage $ts=ldapTimestamp($datestr);
* @exclude - internal use only
*/
function ldapTimestamp($ad) {
	if(stringContains($ad,'.')){
     	//YYYYMMDDHHIISS
     	$str=substr($ad,0,4).'-'.substr($ad,4,2).'-'.substr($ad,6,2).' '.substr($ad,8,2).':'.substr($ad,10,2).':'.substr($ad,12,2);
		return strtotime($str);
	}
  $seconds_ad = $ad / (10000000);
   //86400 -- seconds in 1 day
   $unix = ((1970-1601) * 365 - 3 + round((1970-1601)/4) ) * 86400;
   $timestamp = $seconds_ad - $unix;
   return $timestamp;
}
//---------- begin function ldapValue--------------------
/**
* @describe returns an ldap value
* @param val mixed
* @return string
* @usage $val=ldapValue($val);
* @exclude - internal use only
*/
function ldapValue($val){
	if(!isset($val['count'])){return $val;}
	unset($val['count']);
	return implode(',',$val);
}
/**
* Create an organizational unit
    * 
    * @param array $attributes Default attributes of the ou
    * @return bool
    */
//---------- begin function ldapCreateOU--------------------
/**
* @describe creates an organizational unit
* @param attributes array
* @return boolean
* @usage $val=ldapCreateOU($att);
* @exclude - internal use only
*/
function ldapCreateOU($attributes){
	global $ldapInfo;
	if (!is_array($attributes)){ return "Attributes must be an array"; }
    if (!is_array($attributes["container"])) { return "Container attribute must be an array."; }
    if (!array_key_exists("ou_name",$attributes)) { return "Missing compulsory field [ou_name]"; }
    if (!array_key_exists("container",$attributes)) { return "Missing compulsory field [container]"; }
    $attributes["container"] = array_reverse($attributes["container"]);
    $add=array();
    $add["objectClass"] = "organizationalUnit";
    $add["OU"] = $attributes['ou_name'];
    $containers = "";
    if (count($attributes['container']) > 0) {
        $containers = "OU=" . implode(",OU=", $attributes["container"]) . ",";
    }
    $containers = "OU=" . implode(",OU=", $attributes["container"]);
    $result = ldap_add($ldapInfo['connection'], "OU=" . $add["OU"] . ", " . $containers . $ldapInfo['basedn'], $add);
    if ($result != true) {
        return false;
     }
    return true;
}

/**
    * Returns a folder listing for a specific OU
    * See http://adldap.sourceforge.net/wiki/doku.php?id=api_folder_functions
    * 
    * @param array $folderName An array to the OU you wish to list. 
    *                           If set to NULL will list the root, strongly recommended to set 
    *                           $recursive to false in that instance!
    * @param string $dnType The type of record to list.  This can be ADLDAP_FOLDER or ADLDAP_CONTAINER.
    * @param bool $recursive Recursively search sub folders
    * @param bool $type Specify a type of object to search for
    * @return array
    */
//---------- begin function ldapCreateOU--------------------
/**
* @describe creates an organizational unit
* @param foldername array - An array to the OU you wish to list. If set to NULL will list the root, strongly recommended to set $recursive to false in that instance!
* @param dnType string - The type of record to list.  This can be ADLDAP_FOLDER or ADLDAP_CONTAINER. Defaults to ADLDAP_FOLDER
* @return boolean
* @usage $val=ldapCreateOU($att);
* @exclude - internal use only
*/
function ldapListOU($folderName = NULL, $dnType = adLDAP::ADLDAP_FOLDER, $recursive = NULL, $type = NULL)
    {
        if ($recursive === NULL) { $recursive = $this->adldap->getRecursiveGroups(); } //use the default option if they haven't set it
        if (!$this->adldap->getLdapBind()) { return false; }

        $filter = '(&';
        if ($type !== NULL) {
            switch ($type) {
                case 'contact':
                    $filter .= '(objectClass=contact)';
                    break;
                case 'computer':
                    $filter .= '(objectClass=computer)';
                    break;
                case 'group':
                    $filter .= '(objectClass=group)';
                    break;
                case 'folder':
                    $filter .= '(objectClass=organizationalUnit)';
                    break;
                case 'container':
                    $filter .= '(objectClass=container)';
                    break;
                case 'domain':
                    $filter .= '(objectClass=builtinDomain)';
                    break;
                default:
                    $filter .= '(objectClass=user)';
                    break;   
            }
        }
        else {
            $filter .= '(objectClass=*)';   
        }
        // If the folder name is null then we will search the root level of AD
        // This requires us to not have an OU= part, just the base_dn
        $searchOu = $this->adldap->getBaseDn();
        if (is_array($folderName)) {
            $ou = $dnType . "=" . implode("," . $dnType . "=", $folderName);
            $filter .= '(!(distinguishedname=' . $ou . ',' . $this->adldap->getBaseDn() . ')))';
            $searchOu = $ou . ',' . $this->adldap->getBaseDn();
        }
        else {
            $filter .= '(!(distinguishedname=' . $this->adldap->getBaseDn() . ')))';
        }

        if ($recursive === true) {
            $sr = ldap_search($this->adldap->getLdapConnection(), $searchOu, $filter, array('objectclass', 'distinguishedname', 'samaccountname'));
            $entries = @ldap_get_entries($this->adldap->getLdapConnection(), $sr);
            if (is_array($entries)) {
                return $entries;
            }
        }
        else {
            $sr = ldap_list($this->adldap->getLdapConnection(), $searchOu, $filter, array('objectclass', 'distinguishedname', 'samaccountname'));
            $entries = @ldap_get_entries($this->adldap->getLdapConnection(), $sr);
            if (is_array($entries)) {
                return $entries;
            }
        }
        
        return false;
    }

?>
