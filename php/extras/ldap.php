<?php
/* 
	LDAP functions
	References:
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/
		http://stackoverflow.com/questions/14815142/php-ldap-bind-on-secure-remote-server-windows-fail
		


*/
$progpath=dirname(__FILE__);
function ldapAuth($params=array()){
	if(!isset($params['-host'])){return 'ldapAuth Error: no host';}
	if(!isset($params['-username'])){return 'ldapAuth Error: no username';}
	if(!isset($params['-password'])){return 'ldapAuth Error: no password';}
	//default port to 389
	$params['-port']=isset($params['-port'])?$params['-port']:389;
	$params['-user']="{$params['-username']}@{$params['-host']}";
	if($params['-port']==636){
		$params['-host']='ldaps://'.$params['-host'];
	}
	//check to see if we can reach the ldap server
	/*
	$op = fsockopen($params['-host'], $params['-port'], $errno, $errstr, $timeout);
	if(!$op){
		fclose($op);
		return 'ldapAuth Error: unable to connect to host:'.$params['-host'];
	}
	fsockclose($op);
	*/
	//connect
	$ldap_connection = ldap_connect($params['-host']);
	if(!$ldap_connection){return 'ldapAuth Error: unable to connect to host';}
	// We need this for doing an LDAP search.
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	//bind
    $bind=ldap_bind($ldap_connection,$params['-user'],$params['-password']);
    if(!$bind){
		ldap_unbind($ldap_connection); // Clean up after ourselves.
		return 'ldapAuth Error: auth failed'.printValue($params);
	}

    //now get this users record and return it
	$rec=array();
	//$ldap_base_dn = 'DC=xyz,DC=local';
	$ldap_base_dn = "CN=Users,DC=ad,DC=domain";
	$search_filter = "(&(objectCategory=person))";
	$result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter);
	if (FALSE !== $result){
	    $entries = ldap_get_entries($ldap_connection, $result);
	    if ($entries['count'] > 0){
	        $odd = 0;
	        foreach ($entries[0] AS $key => $value){
	            if (0 === $odd%2){
	                $ldap_columns[] = $key;
	            }
	            $odd++;
	        }
	        echo '<table class="data">';
	        echo '<tr>';
	        $header_count = 0;
	        foreach ($ldap_columns AS $col_name){
	            if (0 === $header_count++){
	                echo '<th class="ul">';
	            }else if (count($ldap_columns) === $header_count){
	                echo '<th class="ur">';
	            }else{
	                echo '<th class="u">';
	            }
	            echo $col_name .'</th>';
	        }
	        echo '</tr>';
	        for ($i = 0; $i < $entries['count']; $i++){
	            echo '<tr>';
	            $td_count = 0;
	            foreach ($ldap_columns AS $col_name){
	                if (0 === $td_count++){
	                    echo '<td class="l">';
	                }else{
	                    echo '<td>';
	                }
	                if (isset($entries[$i][$col_name])){
	                    $output = NULL;
	                    if ('lastlogon' === $col_name || 'lastlogontimestamp' === $col_name){
	                        $output = date('D M d, Y @ H:i:s', ($entries[$i][$col_name][0] / 10000000) - 11676009600);
	                    }else{
	                        $output = $entries[$i][$col_name][0];
	                    }
	                    echo $output .'</td>';
	                }
	            }
	            echo '</tr>';
	        }
	        echo '</table>';
	    }
	}
    ldap_unbind($ldap_connection); // Clean up after ourselves.
    return $rec;
}
//---------- begin function ldapGetRecords--------------------
/**
* @describe returns a list of LDAP records based on parameters
* @param params array
* @return array or null if blank
* @usage $recs=ldapGetRecords($params);
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
?>
