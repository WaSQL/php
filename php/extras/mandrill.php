<?php
/*
	References:
	https://mandrillapp.com/api/docs/messages.php.html
	https://mandrill.zendesk.com/hc/en-us/articles/205582487-How-do-I-use-merge-tags-to-add-dynamic-content-
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
	if(isset($params['from_email'])){$params['from']=$params['from_email'];}
	if(!isset($params['from'])){return 'mandrillSendMail Error: missing from';}
	if(!isset($params['subject'])){return 'mandrillSendMail Error: missing subject';}
	if(isset($params['html'])){$params['message']=$params['html'];}
	if(!isset($params['message'])){return 'mandrillSendMail Error: missing message';}
	if(isEmail($params['to'])){
    	$params['to']=array(
	            array(
	                'email' => $params['to'],
	                'name' => isset($params['to_name'])?$params['to_name']:'',
	                'type' => 'to'
	            )
	        );
	}
	try {
	    $mandrill = new Mandrill($params['-apikey']);
	    $message = array(
	        'html' => isset($params['html'])?$params['html']:$params['message'],
	        'text' => isset($params['text'])?$params['text']:removeHtml($params['message']),
	        'subject' => $params['subject'],
	        'from_email' => $params['from'],
	        'from_name' => isset($params['from_name'])?$params['from_name']:'',
	        'to' => $params['to'],
	        'headers' => array('Reply-To' => $params['from']),
	        'important' => isset($params['important'])?$params['important']:true,
	        'track_opens' => isset($params['track_opens'])?$params['track_opens']:true,
	        'track_clicks' => isset($params['track_clicks'])?$params['track_clicks']:true,
	        'auto_text' => isset($params['auto_text'])?$params['auto_text']:true,
	        'auto_html' => isset($params['auto_html'])?$params['auto_html']:true,
	        'inline_css' => isset($params['inline_css'])?$params['inline_css']:true,
	        'url_strip_qs' => isset($params['url_strip_qs'])?$params['url_strip_qs']:null,
	        'preserve_recipients' => isset($params['preserve_recipients'])?$params['preserve_recipients']:false,
	        'view_content_link' => isset($params['view_content_link'])?$params['view_content_link']:null,
	        'tracking_domain' => isset($params['tracking_domain'])?$params['tracking_domain']:null,
	        'signing_domain' => isset($params['signing_domain'])?$params['signing_domain']:null,
	        'return_path_domain' => isset($params['return_path_domain'])?$params['return_path_domain']:null,
	        'merge' => isset($params['merge'])?$params['merge']:true,
	    );
	    if(isset($params['tags'])){
			if(!is_array($params['tags'])){$params['tags']=array($params['tags']);}
        	$message['tags'] = $params['tags'];
		}
		if(isset($params['merge_vars'])){
        	$message['merge_vars'] = $params['merge_vars'];
		}
		if(isset($params['global_merge_vars'])){
        	$message['global_merge_vars'] = $params['global_merge_vars'];
		}
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
	    $async = isset($params['async'])?$params['async']:true;
	    $ip_pool = isset($params['ip_pool'])?$params['ip_pool']:'Main Pool';
	    //put send_at in the past to send now
	    $send_at = isset($params['send_at'])?date('Y-m-d H:i:s',strtotime($params['send_at'])):date('Y-m-d H:i:s',strtotime('yesterday'));
	    $result = $mandrill->messages->send($message, $async, $ip_pool, $schedule);
	    return $result;
	    if(isset($result[0]['status']) && strtolower($result[0]['status'])=='sent'){
        	return 1;
		}
	    return $result;
	} catch(Mandrill_Error $e) {
	    // Mandrill errors are thrown as exceptions
	    return 'mandrillSendMail error: ' . get_class($e) . ' - ' . $e->getMessage();
	}
}
?>