<?php
/*
	References:
	https://mandrillapp.com/api/docs/messages.php.html
*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/Mandrill/Mandrill.php");
//---------- begin function mandrillSendMail--------------------
/**
* @describe mandrill sendMail replacement to send mail using your Mandrill account
* @param params array
*	-apikey - your Mandrill API key.
*	to - the email address to send the email to.  For multiple recepients, separate emails by commas or semi-colons
*	from - the email address to send the email from
*	[cc] - optional email address to carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[bcc] - optional email address to blind carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[reply-to] - the email address to set as the reply-to address
*	subject - the subject of the email
*	message - the body of the email
*	[attach] - an array of files (full path required) to attach to the message
* @return str value
*	returns the error message or 1 on success
* @usage 
*	loadExtras('mandrill');
*	$errmsg=mandrillSendMail(array(
*		'-apikey'	=> 'YOUR_MANDRILL_API_KEY',
*		'to'		=> 'john@doe.com',
*		'from'		=> 'jane@doe.com',
*		'subject'	=> 'When will you be home?',
*		'message'	=> 'Here is the document you requested',
*		'attach'	=> array('/var/www/doument.doc')
*	));
*/
function mandrillSendMail($params=array()){
	if(!isset($params['-apikey'])){return 'mandrillSendMail Error: missing apikey';}
	if(!isset($params['to'])){return 'mandrillSendMail Error: missing to';}
	if(!isset($params['from'])){return 'mandrillSendMail Error: missing from';}
	if(!isset($params['subject'])){return 'mandrillSendMail Error: missing subject';}
	if(!isset($params['message'])){return 'mandrillSendMail Error: missing message';}
	try {
	    $mandrill = new Mandrill($params['-apikey']);
	    $message = array(
	        'html' => $params['message'],
	        'text' => removeHtml($params['message']),
	        'subject' => $params['subject'],
	        'from_email' => $params['from'],
	        'from_name' => '',
	        'to' => array(
	            array(
	                'email' => $params['to'],
	                'name' => '',
	                'type' => 'to'
	            )
	        ),
	        'headers' => array('Reply-To' => $params['from']),
	        'important' => true,
	        'track_opens' => null,
	        'track_clicks' => null,
	        'auto_text' => null,
	        'auto_html' => null,
	        'inline_css' => null,
	        'url_strip_qs' => null,
	        'preserve_recipients' => null,
	        'view_content_link' => null,
	        'tracking_domain' => null,
	        'signing_domain' => null,
	        'return_path_domain' => null,
	        'merge' => false,
	    );
	    //attachments
	    if(isset($params['-attach'])){
        	if(!is_array($params['-attach'])){$params['-attach']=array($params['-attach']);}
        	foreach($params['-attach'] as $afile){
            	if(!is_file($afile)){$afile="{$_SERVER['DOCUMENT_ROOT']}/{$afile}";}
            	if(!is_file($afile)){return "mandrillSendMail Error: missing attachment - {$afile}";}
            	if(!isset($message['attachments'])){$message['attachments']=array();}
            	$attach=array(
					'type'		=> getFileMimeType($afile),
					'name'		=> getFileName($afile),
					'content'	=> base64_encode(file_get_contents($afile))
				);
				$message['attachments'][]=$attach;
			}
		}
		//embedded images
		if(isset($params['-images'])){
        	if(!is_array($params['-images'])){$params['-images']=array($params['-images']);}
        	foreach($params['-images'] as $afile){
            	if(!is_file($afile)){$afile="{$_SERVER['DOCUMENT_ROOT']}/{$afile}";}
            	if(!is_file($afile)){return "mandrillSendMail Error: missing image - {$afile}";}
            	if(!isset($message['images'])){$message['images']=array();}
            	$attach=array(
					'type'		=> getFileMimeType($afile),
					'name'		=> getFileName($afile),
					'content'	=> base64_encode(file_get_contents($afile))
				);
				$message['images'][]=$attach;
			}
		}
	    $async = false;
	    $ip_pool = 'Main Pool';
	    //put send_at in the past to send now
	    $send_at = date('Y-m-d H:i:s',strtotime('yesterday'));
	    $result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
	    if(isset($result[0]['status']) && strtolower($result[0]['status'])=='sent'){
        	return 1;
		}
	    return printValue($result);
	} catch(Mandrill_Error $e) {
	    // Mandrill errors are thrown as exceptions
	    return 'mandrillSendMail error: ' . get_class($e) . ' - ' . $e->getMessage();
	}
}
?>