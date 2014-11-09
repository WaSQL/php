<?php
/*
	Methods (v5.0.0) 	Type 	Default 	Description
	set($name, $value) 	string $name
	string $value 	  	Method provides ability for user to create own custom pseudo-properties (like X-Headers, for example). Example use:
	$mail->set('X-MSMail-Priority', 'Normal');
	addCustomHeader($value) 	string $value 	  	Method provides ability for user to create own custom headers (like X-Priority, for example). Example use:
	$mail->addCustomHeader("X-Priority: 3");
	MsgHTML($message) 	  	  	Evaluates the message and returns modifications for inline images and backgrounds. Sets the IsHTML() method to true, initializes AltBody() to either a text version of the message or default text.
	IsMail() 	boolean 	true 	Sets Mailer to send message using PHP mail() function. (true, false or blank)
	IsSMTP() 	boolean 	  	Sets Mailer to send message using SMTP. If set to true, other options are also available. (true, false or blank)
	IsSendmail() 	boolean 	  	Sets Mailer to send message using the Sendmail program. (true, false or blank)
	IsQmail() 	boolean 	  	Sets Mailer to send message using the qmail MTA. (true, false or blank)
	SetFrom($address, $name = "") 	string $address
	string $name 	  	Adds a "From" address.
	AddAddress($address, $name = "") 	string $address
	string $name 	  	Adds a "To" address.
	AddCC($address, $name = "") 	string $address
	string $name 	  	Adds a "Cc" address. Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
	AddBCC($address, $name = "") 	string $address
	string $name 	  	Adds a "Bcc" address. Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
	AddReplyTo($address, $name = "") 	string $address
	string $name 	  	Adds a "Reply-to" address.
	Send() 	  	  	Creates message and assigns Mailer. If the message is not sent successfully then it returns false. Use the ErrorInfo variable to view description of the error. Returns true on success, false on failure.
	AddAttachment($path, $name = "", $encoding = "base64",
	    $type = "application/octet-stream") 	string $path
	string $name
	string $encoding
	string $type 	  	Adds an attachment from a path on the filesystem. Returns false if the file could not be found or accessed.
	AddEmbeddedImage($path, $cid, $name = "", $encoding = "base64",
	    $type = "application/octet-stream") 	string $path
	string $cid
	string $name
	string $encoding
	string $type 	  	Adds an embedded attachment. This can include images, sounds, and just about any other document. Make sure to set the $type to an image type. For JPEG images use "image/jpeg" and for GIF images use "image/gif". If you use the MsgHTML() method, there is no need to use AddEmbeddedImage() method.
	ClearAddresses() 	  	  	Clears all recipients assigned in the TO array. Returns void.
	ClearCCs() 	  	  	Clears all recipients assigned in the CC array. Returns void.
	ClearBCCs() 	  	  	Clears all recipients assigned in the BCC array. Returns void.
	ClearReplyTos() 	  	  	Clears all recipients assigned in the ReplyTo array. Returns void.
	ClearAllRecipients() 	  	  	Clears all recipients assigned in the TO, CC and BCC array. Returns void.
	ClearAttachments() 	  	  	Clears all previously set filesystem, string, and binary attachments. Returns void.
	ClearCustomHeaders() 	  	  	Clears all custom headers. Returns void.
*/
$progpath=dirname(__FILE__);
include_once "{$progpath}/phpmailer/class.phpmailer.php";
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
*	[smtpuser] - set SMTP username - only valid if smtp is set
*	[priority] - sets X-MSMail-Priority - valid values are Low, Normal, High
*	[encrypt] - sets security type - valid values are TLS, SSL
*	[smtppass] - set SMTP password - only valid if smtp is set
*	[attach] - array of files to attach to message
*	[inline] - array of inline images to embed.  each element must be an array of (afile,cid,name)
*	[x-***] - sets custom pseudo-properties - must begin with X-
* @return 1 on success or error message on failure
* @usage
*	phpmailerSendMail(array('to'=>$to,'from'=>$from,'subject'=>$subject,'message'=>$msg));
*/
function phpmailerSendMail($params=array()){
	/* Required options */
	$reqopts=array('to','from','subject','message');
	foreach($reqopts as $key){
		if(!isset($params[$key]) || strlen($params[$key])==0){return "phpmailerSendMail Error - missing required parameter: ". $key;}
    }
	$mail = new PHPMailer;
	$mail->set('X-WaSQL-Method', 'phpmailerSendMail');
	//custom SMTP?
	if(isset($params['smtp'])){
		//default smtpport to 587
		if(!isNum($params['smtpport'])){
			$mail->Port = $params['smtpport'];
		}
		$mail->IsSMTP();                                      // Set mailer to use SMTP
		$mail->Host = $params['smtp'];                 		  // Specify main and backup server
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
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
		$mail->set('X-MSMail-Priority', $params['priority']);
		}
	//SSL or TLS security?
	if(isset($params['encrypt'])){
		$mail->SMTPSecure = $params['encrypt'];              // tls or ssl
	}
	//headers
	if(isset($params['headers'])){$params['-headers']=$params['headers'];}
	if(is_array($params['-headers'])){
		foreach($params['-headers'] as $header){
			$mail->addCustomHeader($header);
		}
	}
	//From
	$mail->From = $params['from'];
	if(isset($params['fromname'])){
		$mail->FromName = $params['fromname'];
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
				//$path,$name
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
	if(!$mail->Send()){return "phpmailerSendMail Error -". $mail->ErrorInfo;}
	return 1;
}
?>