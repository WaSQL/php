<?php


function office365Auth($params=array()){
	if(!isset($params['-username'])){return 'office365 Auth Error: no username';}
	if(!isset($params['-password'])){return 'office365 Auth Error: no password';}
	$token=getSecurityToken($params['-username'], $params['-password']);
	if(isset($token['value']) && isset($token['expire'])){
    	//valid user
    	return $token;
	}
	return 'office365 login failed';
}

/**
 * Get the FedAuth and rtFa cookies
 *
 * @param string $token
 * @param string $host
 * @return array
 * @throws Exception
 */
function getAuthCookies($token, $host) {
    $url = $host . "/_forms/default.aspx?wa=wsignin1.0";

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$token);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $certpath=getWasqlPath();
    curl_setopt($ch, CURLOPT_CAINFO, "{$certpath}/cacert.pem");
//    curl_setopt($ch,CURLOPT_VERBOSE, 1); // For testing
    curl_setopt($ch, CURLOPT_HEADER, true);
     
    $result = curl_exec($ch);
 
    // catch error
    if($result === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
 
    //close connection
    curl_close($ch);     
     
    return getCookieValue($result);
}
 
function parseToken($id_token) {
  $token_parts = explode(".", $id_token);

  // First part is header, which we ignore
  // Second part is JWT, which we want to parse
  //error_log("getUserName found id token: ".$token_parts[1]);

  // First, in case it is url-encoded, fix the characters to be 
  // valid base64
  $encoded_token = str_replace('-', '+', $token_parts[1]);
  $encoded_token = str_replace('_', '/', $encoded_token);
  //error_log("After char replace: ".$encoded_token);

  // Next, add padding if it is needed.
  switch (strlen($encoded_token) % 4){
    case 0:
      // No pad characters needed.
      //error_log("No padding needed.");
      break;
    case 2:
      $encoded_token = $encoded_token."==";
      //error_log("Added 2: ".$encoded_token);
      break;
    case 3:
      $encoded_token = $encoded_token."=";
      //error_log("Added 1: ".$encoded_token);
      break;
    default:
      // Invalid base64 string!
      //error_log("Invalid base64 string");
      return null;
  }

  $json_string = base64_decode($encoded_token);
  //error_log("Decoded token: ".$json_string);
  $jwt = json_decode($json_string, true);
  //error_log("Found user name: ".$jwt['name']);
  return $jwt;
}

/**
 * Get the security token needed
 *
 * @param string $username
 * @param string $password
 * @param string $endpoint
 * @return string
 * @throws Exception
 */
function getSecurityToken($username, $password, $endpoint="https://portal.sharepoint.com") {
    $url = "https://login.microsoftonline.com/extSTS.srf";
     
    $tokenXml = getSecurityTokenXml($username, $password, $endpoint);  

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$tokenXml);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $certpath=getWasqlPath();
    curl_setopt($ch, CURLOPT_CAINFO, "{$certpath}/cacert.pem");
    $result = curl_exec($ch);
 
    // catch error
    if($result === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
 
    //close connection
    curl_close($ch);
    // Parse security token from response
    $xml = new DOMDocument();
    $xml->loadXML($result);
    $xpath = new DOMXPath($xml);
    //https://social.technet.microsoft.com/Forums/msonline/en-US/4e304493-7ddd-4721-8f46-cb7875078f8b/problem-logging-in-to-office-365-sharepoint-online-from-webole-hosted-in-the-cloud?forum=onlineservicessharepoint
	$token=array();
    $nodelist= $xpath->query("//wsu:Expires");
    foreach ($nodelist as $n){
		$token['expire'] = $n->nodeValue;
	}
    $nodelist = $xpath->query("//wsse:BinarySecurityToken");
    foreach ($nodelist as $n){
        $token['value'] =  $n->nodeValue;
        break;
    }
    return $token;

}
 
/**
 * Get the XML to request the security token
 *
 * @param string $username
 * @param string $password
 * @param string $endpoint
 * @return type string
 */
function getSecurityTokenXml($username, $password, $endpoint) {
    return <<<TOKEN
    <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"
      xmlns:a="http://www.w3.org/2005/08/addressing"
      xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  <s:Header>
    <a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</a:Action>
    <a:ReplyTo>
      <a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
    </a:ReplyTo>
    <a:To s:mustUnderstand="1">https://login.microsoftonline.com/extSTS.srf</a:To>
    <o:Security s:mustUnderstand="1"
       xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <o:UsernameToken>
        <o:Username>$username</o:Username>
        <o:Password>$password</o:Password>
      </o:UsernameToken>
    </o:Security>
  </s:Header>
  <s:Body>
    <t:RequestSecurityToken xmlns:t="http://schemas.xmlsoap.org/ws/2005/02/trust">
      <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
        <a:EndpointReference>
          <a:Address>$endpoint</a:Address>
        </a:EndpointReference>
      </wsp:AppliesTo>
      <t:KeyType>http://schemas.xmlsoap.org/ws/2005/05/identity/NoProofKey</t:KeyType>
      <t:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</t:RequestType>
      <t:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</t:TokenType>
    </t:RequestSecurityToken>
  </s:Body>
</s:Envelope>
TOKEN;
}
 
/**
 * Get the cookie value from the http header
 *
 * @param string $header
 * @return array
 */
function getCookieValue($header)
{
    $authCookies = array();
    $header_array = explode("\r\n",$header);
    foreach($header_array as $header) {
        $loop = explode(":",$header);
        if($loop[0] == 'Set-Cookie') {
            $authCookies[] = $loop[1];
        }
    }
    unset($authCookies[0]); // No need for first cookie
    return array_values($authCookies);
}
?>
