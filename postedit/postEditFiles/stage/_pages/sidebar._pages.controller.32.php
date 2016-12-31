<?php
if(isset($_REQUEST['passthru'][0])){$_REQUEST['func']=$_REQUEST['passthru'][0];}
switch(strtolower($_REQUEST['passthru'][0])){
	case 'subscribe':
		$email=addslashes($_REQUEST['email']);
		if(!isEmail($email)){
        	setView('invalid_email',1);
        	return;
		}
		$rec=getDBRecord(array('-table'=>'email_list','email'=>$email));
		if(isset($rec['_id'])){
        	setView('already_subscribed',1);
        	return;
		}
		$id=addDBRecord(array('-table'=>'email_list','email'=>$email,'active'=>1));
		setView('subscription_success',1);
		return;
	break;
	case 'suggest':
		$email=addslashes($_REQUEST['email']);
		$id=addDBRecord(array(
			'-table'=>'support_issues',
			'description'=>addslashes($_REQUEST['suggestion']),
			'subject'	=> 'New Product Suggestion',
			'status'	=> 2,
			'priority'	=> 1,
			'email'=>$email,
			'active'=>1
		));
		setView('suggest_success',1);
		return;
	break;
}
?>
