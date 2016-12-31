<?php
/* $test=<<<ENDOFJSON
{"version":"1.0","response":{"outputSpeech":{"type":"SSML","ssml":"\r\n\tToday, You have had 38<\/say-as> unique visitors comprising of 41<\/say-as> views.\r\n<\/speak>"},"card":{"type":"Simple","title":null,"content":"\r\n\tToday, You have had 38 unique visitors comprising of 41 views.\r\n"},"reprompt":{"outputSpeech":{"type":"PlainText","text":"Anything else?"}},"shouldEndSession":false}}
ENDOFJSON;
$json=json_decode($test,1);
echo printValue($json);exit; */
loadDBFunctions(array('functions_alexa','functions_common'));
if(isset($_REQUEST['debug_init']) && isNum($_REQUEST['debug_init']) && isDBStage()){
	$logid=$_REQUEST['debug_init'];
	$rec=getDBRecord(array('-table'=>'alexa_log','_id'=>$logid));
	$postdata=$rec['request'];
	global $alexa;
	$alexa=@json_decode($postdata,1);
	//echo printValue($alexa).printValue($rec);exit;
	if(isset($alexa['session']['application']['applicationId'])){
    	$app=getDBRecord(array(
			'-table'	=> '_pages',
			'appid'		=> $alexa['session']['application']['applicationId'],
			'-fields'	=> '_id,name'
		));
		//echo printValue($app);exit;
		if(isset($app['name'])){
        	echo includePage($app['name'],array('post'=>$postdata,'debug_init'=>$_REQUEST['debug_init']));
        	exit;
		}
	}
}

	//check is this is an alexa app
	$postdata = file_get_contents("php://input");
	$postdata=trim($postdata);
	if(strlen($postdata)){
		//setFileContents('/var/www/vhosts/devmavin.com/skillsai.com/phpinput.log',$postdata);
		$alexa=@json_decode($postdata,1);
		if(isset($alexa['session']['application']['applicationId'])){
	    	$app=getDBRecord(array(
				'-table'	=> '_pages',
				'appid'		=> $alexa['session']['application']['applicationId'],
				'-fields'	=> '_id,name'
			));
			if(isset($app['name'])){
	        	echo includePage($app['name'],array('post'=>$postdata));
	        	exit;
			}
		}
		elseif(isset($alexa['result']['resolvedQuery'])){
			//setFileContents('googlehome.txt',printValue($alexa));
			//google home device
			$app=getDBRecord(array(
				'-table'	=> '_pages',
				'name'		=> $_REQUEST['passthru'][0],
				'-fields'	=> '_id,name'
			));

			if(isset($app['name'])){
	        	echo includePage($app['name']);
	        	exit;
			}
			else{
            	$response="I know nothing about {$_REQUEST['passthru'][0]}";
				$json=array(
					'speech'=>$response,
					'displayText'=>$response,
					'source'=>'Skillsai SalesTalk'
				);
				$jsontxt=json_encode($json);
				header('Content-type: application/json');
				echo $jsontxt;
				exit;
			}
		}
	}
	//PROCESS PAGE as usual
	if(isset($_REQUEST[passthru][0])){
		$_REQUEST['func']=$_REQUEST[passthru][0];
	}
	//check for table. create it if needed
	if(!isDBTable('email_list')){
    	createDBTable('email_list',array(
			'active'		=> 'tinyint(1) NOT NULL Default 1',
			'confirm_date'	=> 'date NULL',
			'email'			=> 'varchar(255) NOT NULL UNIQUE'
		));
	}
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
