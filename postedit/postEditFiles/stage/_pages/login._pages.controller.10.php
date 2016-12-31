<?php
/*
	More login providers to integrate later
		https://developer.paypal.com/docs/integration/direct/identity/log-in-with-paypal/
			https://developer.paypal.com/developer/applications/
			account:slloyd@timequest.org
			clientID:Aei3Uaqn3WtsdH2h4AbW47dZFxooBLh1NE8v1hHAJ6S_FxPkp5FTD2cTtcpayBA2cwVz_ZZASPJNI0nW
			secret:EK19GCAKaho5P9MU6pFmlvW7pgiobRSXbhREHMSDPUCZxbDLzrXL2weBgdrXZpPf4ZYGzv-seIaIcHA2
		https://developer.linkedin.com/docs/signin-with-linkedin#!
		http://www.infotuts.com/login-with-google-plus-in-your-website-php/
		https://developers.facebook.com/docs/facebook-login
			http://stackoverflow.com/questions/9810335/how-to-change-facebook-login-button-with-my-custom-image

*/
loadExtrasJs('wd3');
$profile=array();
if(isset($_REQUEST['redirect']) && stringBeginsWith($_REQUEST['redirect'],'/')){
	$_SESSION['redirect']=$_REQUEST['redirect'];
}
if(!isset($_SESSION['redirect'])){
	$_SESSION['redirect']='/account';
}
if(isset($_REQUEST['passthru'][0])){
	switch(strtolower($_REQUEST['passthru'][0])){
    	case 'register':
    		setView('register',1);
    		return;
    	break;
    	case 'amazon':
    		if(isset($_REQUEST['access_token'])){
				$profile=pageLoginWithAmazon($_REQUEST['access_token']);
			}
    	break;
    	case 'googlehome':
    		//https://docs.api.ai/docs/actions-on-google-integration
    		$ok=appendFileContents('googlehome_debug.txt',printValue($_REQUEST));
    		if(isset($_REQUEST['code'])){
				//Handling the response and exchanging the code
				$url='https://accounts.google.com/o/oauth2/token';
				$opts=array(
					'code'			=> $_REQUEST['code'],
					'client_id'		=> encodeUrl('757864376102-qtnklnn99d906qp2l000dr6uh8hgt3d1.apps.googleusercontent.com'),
					'client_secret'	=> encodeUrl('2O9kMX33NChQ2_x0fQAO7yT9'),
					'redirect_uri'	=> 'https://stage.skillsai.com/login/googlehome',
					'grant_type'	=> 'authorization_code',
					'-headers'		=> array('Content-type: application/x-www-form-urlencoded'),
					'-json'			=> 1
				);
				appendFileContents('googlehome_debug.txt',"URL:{$url}]n");
				$post=postURL($url,$opts);
				appendFileContents('googlehome_debug.txt',printValue($post));
				//we now have $post['json_array']['access_token']
				/*
					Thank you for reaching out!
					When you have an Access token which you generated, you may redirect the user to a URL like the following:
					https://oauth-redirect.googleusercontent.com/r/YOUR_PROJECT_ID#access_token=ACCESS_TOKEN&token_type=bearer&state=STATE_STRING
					After Google has obtained an access token for your service, Google will attach the token to subsequent calls to your service's APIs.
				*/
				if(isset($post['json_array']['access_token'])){
					//redirect to google.com
					$url="https://oauth-redirect.googleusercontent.com/r/serene-bastion-153203#";
					$url .= 'access_token='.$post['json_array']['access_token'];
					//$url .= '&id_token='.encodeUrl($post['json_array']['id_token']);
					$url .= "&token_type=Bearer&state=".$_REQUEST['state'];
					//echo $url;exit;
					appendFileContents('googlehome_debug.txt',"URL:{$url}\n");
					header("Location: {$url}");
					exit;
					//get user profile
					$url='https://www.googleapis.com/oauth2/v1/userinfo';
					$opts=array('-method'=>'GET','-json'=>1,'access_token'=>$post['json_array']['access_token']);
					appendFileContents('googlehome_debug.txt',"URL:{$url}\n");
					$post=postURL($url,$opts);
					appendFileContents('googlehome_debug.txt',printValue($post));
				}
				//echo printValue($post['body']);exit;
				$profile=pageLoginWithGoogleHome($post['access_token']);
			}
			else{
				$url='https://accounts.google.com/o/oauth2/v2/auth?';
				$url .= 'response_type=code';
				$url .= '&client_id='.encodeUrl('757864376102-qtnklnn99d906qp2l000dr6uh8hgt3d1.apps.googleusercontent.com');
				$url .= '&redirect_uri='.encodeUrl('https://stage.skillsai.com/login/googlehome');
				$url .= '&scope=email%20profile';
				$url .= '&state=linkme';
				appendFileContents('googlehome_debug.txt',"URL:{$url}]n");
				header("Location: {$url}");
				exit;
			}
    	break;
    	case 'email':
    		$profile=pageLoginWithEmail($_REQUEST);
    	break;
	}
}

//Login with Amazon?

//if they have a profile_id log them in.
if(isset($profile['_id'])){
	global $USER;
	$json=json_decode($profile['jdoc'],1);
	foreach($json as $k=>$v){
    	$profile[$k]=$v;
	}
	//echo printValue($profile);exit;
	$rec=getDBRecord(array('-table'=>'_users','-where'=>"profile_id={$profile['_id']} or email='{$profile['email']}'"));
	if(isset($rec['_id'])){
		if($rec['profile_id'] ==0){
        	$ok=executeSQL("update _users set profile_id={$profile['_id']} where _id={$rec['_id']}");
		}
		$USER=$rec;
		$guid=getGUID();
		setUserInfo($guid);
	}
	else{
    	//new person create a new client and user record
    	$opts=array(
			'-table'		=> 'clients',
			'name'			=> $profile['email'],
			'active'		=> 1,
			'apiseed'		=> sha1($profile['email']),
			'account_status'=> 'test',
		);
		$client_id=addDBRecord($opts);

		$opts=array(
			'-table'		=> '_users',
			'profile_id'	=> $profile['_id'],
			'email'			=> $profile['email'],
			'utype'			=> 2,
			'username'		=> $profile['email'],
			'password'		=> sha1($profile['email']),
			'profile_id'	=> $profile['_id'],
			'client_id'		=> $client_id
		);
		$id=addDBRecord($opts);
		if(isNum($id)){
			$USER=getDBRecord(array('-table'=>'_users','_id'=>$id));
			$guid=getGUID();
			setUserInfo($guid);
		}

	}

}
?>
