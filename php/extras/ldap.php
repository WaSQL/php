<?php
/* 
	LDAP functions
	References:
		http://php.net/manual/en/function.ldap-connect.php
		https://samjlevy.com/use-php-and-ldap-to-list-members-of-an-active-directory-group-improved/

*/
$progpath=dirname(__FILE__);
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
 
// Example Output
 
print_r(get_members()); // Gets all users in 'Users'
 
print_r(get_members("Test Group")); // Gets all members of 'Test Group'
 
print_r(get_members(
array("Test Group","Test Group 2")
)); // EXCLUSIVE: Gets only members that belong to BOTH 'Test Group' AND 'Test Group 2'
 
print_r(get_members(
array("Test Group","Test Group 2"),TRUE
)); // INCLUSIVE: Gets members that belong to EITHER 'Test Group' OR 'Test Group 2'
