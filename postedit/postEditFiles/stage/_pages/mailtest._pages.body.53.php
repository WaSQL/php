<?php
loadExtras('phpmailer');
$sendopts=array(
	'from' 	=> "wasql@skillsai.com",
	'to' 	=> "steve.lloyd@gmail.com",
	'reply_to'=>'do_not_reply@skillsai.com',
	'subject'=> "Test email using PHP SMTP\r\n\r\n",
	'message'=> "This is a test email message",
	'smtp'	=> "secure.emailsrvr.com",
	'smtpuser'=>"wasql@skilLsai.com",
	'smtppass'=>"Waf44h4123$"
	);
$ok=phpmailerSendMail($sendopts);
echo printValue($ok).printValue($sendopts);
?>
