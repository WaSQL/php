<?php
/*
	https://github.com/PHPMailer/PHPMailer

	Setting the following in your config.xml is the easiest way to send.
		phpmailer="1"
        smtp="email-smtp.us-east-1.amazonaws.com"
        smtpuser="YOURUSERNAME"
        smtppass="YOURPASSWORD"
        smtpport="465"
        email_from="your@email.com"
        email_encrypt="TLS"
    To user Office 365
    	smtp="smtp.office365.com"
    	email_encrypt="TLS"
    	smtpport="587"
*/
$progpath=dirname(__FILE__);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "{$progpath}/phpmailer/Exception.php";
require "{$progpath}/phpmailer/PHPMailer.php";
require "{$progpath}/phpmailer/SMTP.php";

//---------- begin function phpmailerSendMail--------------------
/**
* @describe sends email using phpmailer
* @param array
*	to - array or comma separated string of emails to send to. To add names pass in an array of arrays with email,name as array parts
*	from - email or array(email,name) of sender
*	subject - subject
*	message - message
*	[message_text] - text version. gets auto created if message is HTML and not set
*	[reply-to] - email or array(email,name) of who to reply to
*	[smtp]	- set SMTP server
*	[smtpport] - set SMTP port - defaults to 25. Set to 587 for tls and 465 for ssl
*	[smtpuser] - set SMTP username - only valid if smtp is set
*	[smtppass] - set SMTP password - only valid if smtp is set
*	[priority] - sets priority - valid values are 3,Low, 2,Normal, 3,High
*	[encrypt] - sets security type - valid values are TLS, SSL. not needed if setting smtpport

*	[attach] - array of files to attach to message
*	[inline] - array of inline images to embed.  each element must be an array of (afile,cid,name)
*	[X-***] - sets custom pseudo-properties - must begin with X-
*	[-timeout] - mail timeout in seconds. defaults to 300
* @return 1 on success or error message on failure
* @usage
*	phpmailerSendMail(array('to'=>$to,'from'=>$from,'subject'=>$subject,'message'=>$msg));
*/
function phpmailerSendMail($params=array()){
	global $CONFIG;
    //check for CONFIG settings?
    $flds=array('smtp','smtpport','smtpuser','smtppass');
    foreach($flds as $fld){
    	if(!isset($params[$fld]) && isset($CONFIG[$fld])){$params[$fld]=$CONFIG[$fld];}
    }
    if(!isset($params['from']) && isset($CONFIG['email_from'])){$params['from']=$CONFIG['email_from'];}
    if(!isset($params['encrypt']) && isset($CONFIG['email_encrypt'])){$params['encrypt']=$CONFIG['email_encrypt'];}
    if(!isset($params['-timeout']) && isset($CONFIG['email_timeout'])){$params['-timeout']=$CONFIG['email_timeout'];}
    //defaults
	if(!isset($params['smtpport'])){$params['smtpport']=25;}
	if(!isset($params['-timeout'])){$params['-timeout']=300;}
	/* Required options */
	$reqopts=array('to','from','subject','message');
	foreach($reqopts as $key){
		if(!isset($params[$key]) || strlen($params[$key])==0){return "phpmailerSendMail Error - missing required parameter: ". $key;}
    }
	//mail object
	$mail = new PHPMailer(true);
	//-debug ?
 	if(isset($params['-debug'])){
		$mail->SMTPDebug = 4;  // verbose debugging enabled
		//send debug info to $_REQUEST['phpmailer_debug']
		$_REQUEST['phpmailer_debug']='<br />';
		$mail->Debugoutput = function($str, $level) {
		    $_REQUEST['phpmailer_debug'] .= "{$level}: {$str}<br />".PHP_EOL;
		};
	}
	//-timeout
	$mail->Timeout = $params['-timeout'];
	$mail->set('X-WaSQL-Method', 'phpmailerSendMail');
	//custom SMTP?
	if(isset($params['smtp'])){
		//set smtp use
		$mail->isSMTP(); 
		//smtpport
		switch($params['smtpport']){
			case 587:
				$mail->SMTPSecure = 'tls';
				$mail->Port = 587;
			break;
			case 465:
				$mail->SMTPSecure = 'ssl';
				$mail->Port = 465;
			break;
			default:
				$mail->Port = $params['smtpport'];
			break;
		}
		//smtp
		$mail->Host = $params['smtp'];                 		  // Specify main and backup server
		//smtpuser and smtppass
		if(isset($params['smtpuser']) || isset($params['smtppass'])){
			$mail->SMTPAuth = true;
		}
		if(isset($params['smtpuser'])){
			$mail->Username = $params['smtpuser'];         // SMTP username
		}
		if(isset($params['smtppass'])){
			$mail->Password = $params['smtppass'];           // SMTP password
		}
	}
	//custom pseudo-properties must begin with X-
	foreach($params as $key=>$val){
    	if(stringBeginsWith($key,'X-')){
        	$mail->set($key,$val);
		}
	}
	//priority?
	if(isset($params['priority'])){
		// For most clients expecting the Priority header:
		// 1 = High, 2 = Medium, 3 = Low
		switch(strtolower($params['priority'])){
			case 1:
			case 'high':
				$mail->Priority = 1;
				// MS Outlook custom header
				$mail->AddCustomHeader("X-MSMail-Priority: High");
				// Not sure if Priority will also set the Importance header:
				$mail->AddCustomHeader("Importance: High");		
			break;
			case 2:
			case 'medium':
				$mail->Priority = 2;
				// MS Outlook custom header
				$mail->AddCustomHeader("X-MSMail-Priority: Medium");
				// Not sure if Priority will also set the Importance header:
				$mail->AddCustomHeader("Importance: Medium");		
			break;
			case 3:
			case 'low':
				$mail->Priority = 3;
				// MS Outlook custom header
				$mail->AddCustomHeader("X-MSMail-Priority: Low");
				// Not sure if Priority will also set the Importance header:
				$mail->AddCustomHeader("Importance: Low");
			break;
		}	
	}
	//SSL or TLS security?
	if(isset($params['encrypt'])){
		switch(strtolower($params['encrypt'])){
			case 'tls':
				$mail->SMTPSecure = 'tls';
				$mail->Port = 587;
			break;
			case 'ssl':
				$mail->SMTPSecure = 'ssl';
				$mail->Port = 465;
			break;
		}

	}
	//headers
	if(isset($params['headers'])){$params['-headers']=$params['headers'];}
	if(is_array($params['-headers'])){
		foreach($params['-headers'] as $header){
			$mail->addCustomHeader($header);
		}
	}
	//From
	if(isset($params['fromname'])){
		$mail->SetFrom($params['from'], $params['fromname']);
	}
	else{
		$mail->SetFrom($params['from']);
	}
	//To
	if(!is_array($params['to'])){$params['to']=preg_split('/[\,\;]+/',$params['to']);}
	foreach($params['to'] as $to){
		if(is_array($to) && isEmail($to[0])){
			$mail->AddAddress($to[0], $to[1]);  // Add a recipient and name
		}
		elseif(isEmail($to)){
			$mail->AddAddress($to);               // Name is optional
		}
	}
	//CC
	if(isset($params['cc'])){
		if(!is_array($params['cc'])){$params['cc']=preg_split('/[\,\;]+/',$params['cc']);}
		foreach($params['cc'] as $cc){
			if(is_array($cc) && isEmail($cc[0])){
				$mail->addCC($cc[0], $cc[1]);  // Add a recipient and name
			}
			elseif(isEmail($cc)){
				$mail->addCC($cc);               // Name is optional
			}
		}
	}
	//BCC
	if(isset($params['bcc'])){
		if(!is_array($params['bcc'])){$params['bcc']=preg_split('/[\,\;]+/',$params['bcc']);}
		foreach($params['bcc'] as $bcc){
			if(is_array($bcc) && isEmail($bcc[0])){
				$mail->addBCC($bcc[0], $bcc[1]);  // Add a recipient and name
			}
			elseif(isEmail($bcc)){
				$mail->addBCC($bcc);               // Name is optional
			}
		}
	}
	//Reply-To
	if(isset($params['reply-to'])){
		if(!is_array($params['reply-to'])){$params['reply-to']=preg_split('/[\,\;]+/',$params['reply-to']);}
		foreach($params['reply-to'] as $to){
			if(is_array($to) && isEmail($to[0])){
				$mail->AddReplyTo($to[0], $to[1]);  // Add a recipient and name
			}
			elseif(isEmail($to)){
				$mail->AddReplyTo($to);               // Name is optional
			}
		}
	}
	//Subject
	$mail->Subject = $params['subject'];
	//Message
	if(isXML($params['message'])){
		$mail->IsHTML(true);
		if(!isset($params['message_text'])){$params['message_text']=removeHtml($params['message']);}
		$mail->Body    = $params['message'];
		$mail->AltBody = $params['message_text'];
	}
	else{
    	$mail->Body    = $params['message'];
	}
	/* Attachments:
			'attach'=>array('/path/filename','path2/filename2');
		or
			'attach'=>array(
				array('/path/filename','name'),
				array('/path2/filename2','name2')
			);
	*/
	if(isset($params['attach'])){
		if(!is_array($params['attach'])){$params['attach']=array($params['attach']);}
		foreach($params['attach'] as $file){
			if(is_array($file)){
				$mail->AddAttachment($file[0],$file[1]);
			}
			else{
				$name=getFileName($file);
				$mail->AddAttachment($file,$name);
			}
		}
	}
	/* Inline Images:
			'inline'=>array('/path/filename','path2/filename2');
		or
			'inline'=>array(
				array('/path/filename','cid1','name'),
				array('/path2/filename2','cid2','name2')
			);
	*/
	if(is_array($params['inline'])){
		foreach($params['inline'] as $inline){
			if(is_array($inline)){
				//$path,$cid,$name
				$mail->AddEmbeddedImage($inline[0],$inline[1],$inline[2]);
			}
			else{
            	$cid=encodeCRC($inline);
				$filename=getFileName($afile);
				//$path,$cid,$name
				$mail->AddEmbeddedImage($inline,$cid,$filename);
			}
		}
	}
	try{
		$mail->send();
		return 1;
	} catch (Exception $e) {
		return $mail->ErrorInfo;
	}
}
?>