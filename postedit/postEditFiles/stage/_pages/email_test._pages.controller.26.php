<?php
switch(strtolower($_REQUEST['passthru'][0])){
	default:
		setView('intro');
	break;
}
//return;
$body=getView('intro');
loadExtras('phpmailer');
$ok=phpmailerSendMail(array(
	'smtp'=>'mail.skillsai.com',
	'smtpuser'=>'no-reply@skillsai.com',
	'smtppass'=>'skillsAI16',
	'encrypt'=>'TLS',
	'to'=>'steve.lloyd@gmail.com',
	'from'=>'no-reply@skillsai.com',
	'subject'=>'Introducing the next generation BI - audible reporting!',
	'message'=>$body
));
echo $ok;
echo '<xmp><pre>'.$body.'</pre></xmp>';exit;

/* loadExtras('amazon');
$ok=amazonSendMail(array(
	'-accesskey'=>'AKIAJUIWGQNLEAGM3YUA',
	'-secretkey'=>'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',
	'to'=>'steve.lloyd@gmail.com',
	'from'=>'info@skillsai.com',
	'subject'=>'Introducing the next generation - audible reporting',
	'message'=>$body
)); */
?>
