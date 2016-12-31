<?php
loadDBFunctions('functions_common');
global $USER;
if(!isset($USER['client_id']) && isset($_REQUEST['passthru'][0]) && isNum($_REQUEST['passthru'][0])){
	$USER['client_id']=$_REQUEST['passthru'][0];
}
$client=commonGetClient();
if(!isset($client['_id'])){
	echo "Invalid client";
	exit;
}
//woo
if(isset($_REQUEST['type'])){
	$jsonstr = file_get_contents("php://input");
	//setFileContents('/var/www/wasql_stage/php/temp/woo.txt',$jsonstr);
	$json=json_decode($jsonstr,true);
	if(isset($json['key_permissions'])){
		//success
		$dsrec=getDBRecord(array(
			'-table'	=> 'clients_datasources',
			'name'		=> 'woo',
			'client_id'	=> $client['_id']
		));
		if(isset($dsrec['_id'])){
			$id=editDBRecord(array(
				'-table'	=> 'clients_datasources',
				'-where'	=> "_id={$dsrec['_id']}",
				'jdoc'		=> $jsonstr,
				'code'		=> decodeBase64($_REQUEST['passthru'][1]),
				'scope'		=> $json['key_permissions']
			));
		}
		else{
			$id=addDBRecord(array(
				'-table'	=> 'clients_datasources',
				'name'		=> 'woo',
				'client_id'	=> $client['_id'],
				'jdoc'		=> $jsonstr,
				'code'		=> decodeBase64($_REQUEST['passthru'][1]),
				'scope'		=> $json['key_permissions']
			));
		}
	}
	echo "thanks";
	exit;
}

if(isset($_REQUEST['code']) && isset($_REQUEST['scope'])){
	$dsrec=getDBRecord(array(
		'-table'	=> 'clients_datasources',
		'name'		=> 'stripe',
		'client_id'	=> $client['_id']
	));
	$url='https://connect.stripe.com/oauth/token';
	$params=array(
      	'client_secret' => 'sk_test_kQxGYORSgl5fbMMnMpJSox6A',
      	'code' => $_REQUEST['code'],
      	'grant_type'   => 'authorization_code',
      	'-json'=>1
  	);
	$post=postURL($url,$params);
	if(isset($post['json_array']['error_description'])){
		//echo $post['json_array']['error_description'];
		if(isset($dsrec['_id'])){
        	$ok=delDBRecord(array(
				'-table'	=> 'clients_datasources',
				'-where'	=> "_id={$dsrec['_id']}"
			));
			$url="http://localhost:5263/endStripe/{$client['_id']}";
			header('Location: /account/sources');
			echo "Your stripe account is no longer registered.";
		}
		else{
			header('Location: /account/sources');
			echo $post['json_array']['error_description'];
		}
		exit;
	}
	if(isset($post['json_array']['access_token'])){
		$client=commonGetClient();
    	$jsonstr=json_encode($post['json_array']);
	    $id=addDBRecord(array(
			'-table'	=> 'clients_datasources',
			'name'		=> 'stripe',
			'client_id'	=> $client['_id'],
			'jdoc'		=> $jsonstr,
			'code'		=> $_REQUEST['code'],
			'scope'		=> $_REQUEST['scope']

		));
		//invoke the swim module
		$url="http://localhost:5263/initStripe/{$client['_id']}";
		$post=postURL($url,array('-method'=>'GET'));
		header('Location: /account/sources');
		echo $id." Success!";
		exit;
	}
	echo 'POST'.printValue($post);exit;
}
if(strlen($postdata) && preg_match('/\{(.+?)\}/is',$postdata,$m)){
	$jsonstr='{'.$m[1].'}';
	$json=json_decode($jsonstr,true);
	echo printValue($json);exit;
}
echo 'FAILED'.printValue($postdata);exit;


if(isset($_GET)) {
  // print_r($_GET);
	echo $_GET['scope'];
	echo $_GET['code'];

  // set post fields
  $post = [
      'client_secret' => 'sk_test_kQxGYORSgl5fbMMnMpJSox6A',
      'code' => $_GET['code'],
      'grant_type'   => 'authorization_code',
  ];

  $ch = curl_init('https://connect.stripe.com/oauth/token');
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

  // execute!
  $response = curl_exec($ch);

  // close the connection, release resources used
  curl_close($ch);

  var_dump($response);
  //read_only ac_9f3Mxb7Y91afQ325vRb3QMkjqSrrG1BD
  // {
  // "access_token": "sk_test_byvng8V4pQfPkxxQp4K5vAYu",
  // "livemode": false,
  // "refresh_token": "rt_9f3MvkYMYOsUUp5foGLoRaU9AmSqMHwecI1VzlLGWmbtYZvR",
  // "token_type": "bearer",
  // "stripe_publishable_key": "pk_test_klgKrtvTQEFiLDbK6lhG0kkB",
  // "stripe_user_id": "acct_1994cVIgxqajals0",
  // "scope": "read_only"
  //}bool(true)
}
//http://localhost:5263/initStripe/{clientId}
/* example of a request where the user denies us access
https://stripe.com/connect/default/oauth/test?error=access_denied&error_description=The%20user%20denied%20your%20request
*/


 // curl https://connect.stripe.com/oauth/token \
//    -d client_secret=sk_test_QkMYubvvRe2XYTyNwy2cViRu \
//    -d code=AUTHORIZATION_CODE \
//    -d grant_type=authorization_code


?>
