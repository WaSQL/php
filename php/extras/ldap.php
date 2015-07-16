<?php
/* 
	LDAP functions
	References:
		http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
		http://stackoverflow.com/questions/14815142/php-ldap-bind-on-secure-remote-server-windows-fail
		
        http://pear.php.net/package/Net_LDAP2/download
        http://stackoverflow.com/questions/8276682/wamp-2-2-install-pear

*/
$progpath=dirname(__FILE__);
//---------- begin function LDAP Auth--------------------
/**
* @describe used ldap to authenticate user and returns user ldap record
* @param params array
*	-host - LDAP host
*	-username
*	-password
*	[-secure] - prepends ldaps:// to the host name. Use for secure ldap servers
* @return mixed - ldap user record array on success, error msg on failure
* @usage $rec=LDAP Auth(array('-host'=>'myldapserver','-username'=>'myusername','-password'=>'mypassword'));



*/
function ldapAuth($params=array()){
	if(!isset($params['-host'])){return 'LDAP Auth Error: no host';}
	if(!isset($params['-username'])){return 'LDAP Auth Error: no username';}
	if(!isset($params['-password'])){return 'LDAP Auth Error: no password';}
	global $CONFIG;
	if(!isset($params['-bind'])){$params['-bind']="{$params['-username']}@{$params['-host']}";}
	$ldap_base_dn = array();
	$hostparts=preg_split('/\./',$params['-host']);
	foreach($hostparts as $part){
		$ldap_base_dn[]="dc={$part}";
	}
	$params['basedn']=implode(',',$ldap_base_dn);
	if($params['-secure']){
		$params['-host']='ldaps://'.$params['-host'];
	}

	//connect
	$params['-host']='ldap://'.$params['-host'];
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
		return "LDAP Auth Error: auth failed. Err:{$enum}, Msg:{$msg} .. ".printValue($params);
	}
	foreach($params as $k=>$v){
    	$ldapInfo[$k]=$v;
	}
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
	    	$rec=ldapParseEntry($entries[0]);
	    	//echo printValue($rec);ldapClose();exit;
	    	if(is_array($rec)){return $rec;}
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
	ldap_unbind($ldapInfo['connection']);
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
    	$skip=0;
		foreach($params as $k=>$v){
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
		$recs[]=$rec;
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
			if(!isset($e['memberof'])){continue;}
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
//---------- begin function ldapParseEntry--------------------
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
//---------- begin function ldapParseEntry--------------------
/**
* @describe parses an ldap entry and returns a more human friendly record set
* @param ldaprec array
* @return array
* @usage $lrec=ldapParseEntry($lrec);
*/
function ldapParseEntry($lrec=array()){
	//lowercase the keys, if not already
	if(!isset($lrec['memberof'])){
		$lrec=array_change_key_case($lrec,CASE_LOWER);
	}
	//require a memberof key
	if(!isset($lrec['memberof'])){return null;}
	$rec=array('active'=>ldapIsActiveRecord($lrec));
	$skipkeys=array(
		'logonhours','msexchsafesendershash','count','usercertificate',
		'msexchblockedsendershash','msexchsaferecipientshash','thumbnailphoto'
	);
	foreach($lrec as $key=>$val){
		//skip numeric keys - not needed
    	if(is_numeric($key)){continue;}
    	//skip keys
    	if(in_array($key,$skipkeys)){continue;}
        switch(strtolower($key)){
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
function ldapModify($objectguid,$changes){
	global $ldapInfo;
	$recs=ldapGetUsers(array('objectguid'=>$objectguid));
	$dn=$recs[0]['dn'];
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
?>
