<?php

	if(isset($_REQUEST['passthru'][0])){
		$_REQUEST['func']=$_REQUEST['passthru'][0];
	}
	//check for table. create it if needed
	if(!isDBTable('email_list')){
    	createDBTable('email_list',array(
			'active'		=> 'tinyint(1) NOT NULL Default 1',
			'confirm_date'	=> 'date NULL',
			'email'			=> 'varchar(255) NOT NULL UNIQUE'
		));
	}
	if(!isset($_REQUEST['func'])){$_REQUEST['func']='';}
	//based on func show different views
	switch(strtolower($_REQUEST['func'])){
    	case 'signup':
    		$rec=pageAddSignup($_REQUEST['email']);
    		if(isset($rec['_id'])){
				setView('thankyou',1);
				$sendopts=array(
					'-format'	=>'email',
					'to'		=>'subscribe@'.$_SERVER['HTTP_HOST'],
					'from'		=> $_REQUEST['email'],
					'subject'	=> "New Subscription: {$_REQUEST['email']}"
				);
				$confirm=array(
					'-format'	=>'email',
					'from'		=>'no-reply@'.$_SERVER['HTTP_HOST'],
					'to'		=> $_REQUEST['email'],
					'subject'	=> 'RE:Subscription Request'
				);
			}
			else{
            	setView('signup_error',1);
			}
			return;
		break;
		case 'confirm':
		case 'subscribe':
			$rec=pageConfirmSignup($_REQUEST[passthru][1]);
			if(isset($rec['_id'])){
            	setView(array('default','confirm'),1);
			}
			else{
            	setView(array('default','confirm_error'),1);
			}
		break;
		case 'unsubscribe':
			$rec=pageUnsubscribeSignup($_REQUEST[passthru][1]);
			if(isset($rec['_id'])){
            	setView(array('default','unsubscribe'),1);
			}
			else{
            	setView(array('default','unsubscribe_error'),1);
			}
		break;
		default:
			setView('default',1);
		break;
	}
?>
