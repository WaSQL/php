<?php
/* 
	LDAP functions
	References:
		http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
		http://stackoverflow.com/questions/14815142/php-ldap-bind-on-secure-remote-server-windows-fail
		


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
	if(!isset($params['-bind'])){$params['-bind']="{$params['-username']}@{$params['-host']}";}
	$ldap_base_dn = array();
	$hostparts=preg_split('/\./',$params['-host']);
	foreach($hostparts as $part){
		$ldap_base_dn[]="DC={$part}";
	}
	$ldap_base_dn=implode(',',$ldap_base_dn);
	if($params['-secure']){
		$params['-host']='ldaps://'.$params['-host'];
	}
	//connect
	$ldap_connection = ldap_connect($params['-host']);
	if(!$ldap_connection){return 'LDAP Auth Error: unable to connect to host';}
	// We need this for doing an LDAP search.
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	//bind
    $bind=ldap_bind($ldap_connection,$params['-bind'],$params['-password']);
    if(!$bind){
		ldap_unbind($ldap_connection); // Clean up after ourselves.
		return 'LDAP Auth Error: auth failed'.printValue($params);
	}
    //now get this users record and return it
	$rec=array();
	//$search_filter = "(&(objectCategory=person))";
	//set search filter to be the current username so we get the current user record back
	$search_filter = "(&(sAMAccountName={$params['-username']}))";
	$result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter);
	//echo "result".printValue($ldap_base_dn);
	if (FALSE !== $result){
		$entries = ldap_get_entries($ldap_connection, $result);
	    if ($entries['count'] == 1){
	    	$lrec=$entries[0];
	    	foreach($lrec as $key=>$val){
            if(is_numeric($key)){continue;}
            if($key=='objectguid' || $key=='count'){continue;}
               	switch(strtolower($key)){
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
                    	$tmp=preg_split('/\,/',ldapValue($val));
                    	$parts=array();
                    	foreach($tmp as $part){
                            if(!in_array($part,$parts)){$parts[]=$part;}
						}
						$rec[$key]=implode(',',$parts);
                    break;
                    default:
                    	$rec[$key]=ldapValue($val);
                    break;
				}
			}
			ldap_unbind($ldap_connection);
			ksort($rec);
			return $rec;
		}
		ldap_unbind($ldap_connection); // Clean up after ourselves.
    	return 'LDAP Auth Error: unable to get a unique LDAP user object'.printValue($entries);
	}
    ldap_unbind($ldap_connection); // Clean up after ourselves.
    return 'LDAP Auth Error: unable to search'.printValue($result);
}
//---------- begin function ldapConvert2UserRecord--------------------
/**
* @describe converts an ldap user record to a record for the _users table
* @param params array
* @return array
* @usage $rec=ldapConvert2UserRecord($rec);
*/
function ldapConvert2UserRecord($lrec=array()){
	global $CONFIG;
	$rec=array('active'=>1);
	foreach($lrec as $key=>$val){
		switch(strtolower($key)){
          	case 'samaccountname':$rec['username']=$val;break;
          	case 'dn':$rec['password']=substr(encodeBase64($val),10,30);break;
          	case 'l':$rec['city']=$val;break;
          	case 'c':$rec['country']=$val;break;
          	case 'sn':$rec['lastname']=$val;break;
          	case 'givenname':$rec['firstname']=$val;break;
          	case 'displayname':$rec['name']=$val;break;
          	case 'department':$rec['department']=$val;break;
          	case 'title':$rec['title']=$val;break;
          	case 'mail':$rec['email']=$val;break;
          	case 'company':$rec['company']=$val;break;
          	case 'homephone':$rec['phone_home']=$val;break;
          	case 'mobile':$rec['mobile_phone']=$val;break;
          	case 'postalcode':$rec['zip']=$val;break;
          	case 'st':$rec['state']=$val;break;
          	case 'streetaddress':$rec['address1']=$val;break;
          	case 'telephonenumber':$rec['phone']=$val;break;
          	case 'url':$rec['url']=$val;break;
          	case 'manager':
          		if(preg_match('/CN\=(.+?)\,/',$val,$m)){$rec['manager']=$m[1];}
          		elseif(preg_match('/CN\=(.+?)$/',$val,$m)){$rec['manager']=$m[1];}
				else{$rec['manager']=$val;}
			break;
          	case 'memberof':
				if(isset($CONFIG['authldap_admin'])){
					$tmp=preg_split('/\,/',$val);
					if(in_array($CONFIG['authldap_admin'],$tmp)){$rec['utype']=0;}
					else{$rec['utype']=1;}
				}
				else{
                    	$rec['utype']=1;
				}
				$rec['memberof']=$val;
			break;
          	//case 'samaccountname':$rec['username']=$val;break;
          	//case 'samaccountname':$rec['username']=$val;break;
		}
	}
	ksort($rec);
	return $rec;
}
//---------- begin function ldapGetRecords--------------------
/**
* @describe returns a list of LDAP records based on parameters
* @param params array
* @return array or null if blank
* @usage $recs=ldapGetRecords($params);
* @exclude - not ready yet
* @reference https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
*/
function ldapGetRecords($group=FALSE,$inclusive=FALSE) {
	// Active Directory server
    $ldap_host = "ad.domain";
    // Active Directory DN
    $ldap_dn = "CN=Users,DC=ad,DC=domain";
    // Domain, for purposes of constructing $user
    $ldap_usr_dom = "@".$ldap_host;
    // Active Directory user
    $user = "jdoe";
    $password = "password1234!";
    // User attributes we want to keep
    // List of User Object properties:
    // http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
    $keep = array(
        "samaccountname",
        "distinguishedname"
    );
    // Connect to AD
    $ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");
    ldap_bind($ldap,$user.$ldap_usr_dom,$password) or die("Could not bind to LDAP");
// Begin building query
if($group) $query = "(&"; else $query = "";
 
$query .= "(&(objectClass=user)(objectCategory=person))";
 
    // Filter by memberOf, if group is set
    if(is_array($group)) {
     // Looking for a members amongst multiple groups
     if($inclusive) {
     // Inclusive - get users that are in any of the groups
     // Add OR operator
     $query .= "(|";
     } else {
// Exclusive - only get users that are in all of the groups
// Add AND operator
$query .= "(&";
     }
 
     // Append each group
     foreach($group as $g) $query .= "(memberOf=CN=$g,$ldap_dn)";
 
     $query .= ")";
    } elseif($group) {
     // Just looking for membership of one group
     $query .= "(memberOf=CN=$group,$ldap_dn)";
    }
 
    // Close query
    if($group) $query .= ")"; else $query .= "";

// Uncomment to output queries onto page for debugging
// print_r($query);
 
    // Search AD
    $results = ldap_search($ldap,$ldap_dn,$query);
    $entries = ldap_get_entries($ldap, $results);
    // Remove first entry (it's always blank)
    array_shift($entries);
    $output = array(); // Declare the output array
    $i = 0; // Counter
    // Build output array
    foreach($entries as $u) {
        foreach($keep as $x) {
         // Check for attribute
     if(isset($u[$x][0])) $attrval = $u[$x][0]; else $attrval = NULL;
 
         // Append attribute to output array
         $output[$i][$x] = $attrval;
        }
        $i++;
    }
    return $output;
}
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
function ldapValue($val){
	if(!isset($val['count'])){return $val;}
	unset($val['count']);
	return implode(',',$val);
}
?>
