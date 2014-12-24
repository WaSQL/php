<?php
	if(isset($_REQUEST[passthru][0])){
		$_REQUEST['func']=$_REQUEST[passthru][0];
	}
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
		default:
			echo printValue($_REQUEST);exit;
			setView('default',1);
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
