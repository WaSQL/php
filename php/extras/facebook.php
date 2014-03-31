<?php
/* facebook functions
	http://apiwiki.facebook.com/facebook-API-Documentation
*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/facebook/facebook.php");
//-----------------------
function facebookUpdateStatus($params=array()){
	//info: update your facebook status - requires authentication
	if(!isset($params['-api'])){return "No API";}
	if(!isset($params['-secret'])){return "No Secret";}
	if(!isset($params['status'])){return "No status message";}
	$_GET['auth_token']=$params['-auth'];
	$_GET['api_key']=$params['-api'];
	$facebook = new Facebook($params['-api'],$params['-secret']);
	//$fb_user = $facebook->require_login();
	$facebook->api_client->users_setStatus($params['status'],'','',1);
	}
?>