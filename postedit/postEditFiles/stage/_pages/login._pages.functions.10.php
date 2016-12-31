<?php
//login with Email
function pageLoginWithEmail($params){
	$rec=getDBRecord(array('-table'=>'_users','-fields'=>'_id,password,profile_id','username'=>addslashes($params['username'])));
	if(!isset($rec['password'])){return 0;}
	if(userIsEncryptedPW($rec['password'])){
		$params['password']=userEncryptPW(addslashes($params['password']));
	}
	if($params['password']!=$rec['password']){return 0;}
	$profile=getDBRecord(array(
		'-table'=>'clientdata_profiles',
		'_id'=>$rec['profile_id']
	));
	if(!isset($profile['_id'])){
		return 0;
	}
  	else{
		return $profile;
	}
	return 0;
}
//login with Amazon
function pageLoginWithAmazon($access_token){
	loadDBFunctions('functions_alexa');
    $ajson=alexaGetAmazonProfile($access_token);
	$ama=json_decode($ajson,1);
	//check for valid json
	if(!isset($ama['user_id'])){return 0;}
	$profile=getDBRecord(array(
		'-table'=>'clientdata_profiles',
		'account'=>$ama['user_id']
	));
	if(!isset($profile['_id'])){
		$copts=array(
			'-table'=>'clientdata_profiles',
			'jdoc'=>$ajson
		);
		if(isset($ama['postal_code'])){
            $zone=alexaGetTimeZone($ama['postal_code']);
            $zarr=json_decode($zone,1);
            foreach($zarr as $zk=>$zv){
                $ama[$zk]=$zv;
                $alexa['profile'][$zk]=$zv;
			}
		$copts['jdoc']=json_encode($ama);
		}
		$ajson=$copts['jdoc'];
		$id=addDBRecord($copts);
		if(!isNum($id)){return 0;}
		return getDBRecord(array('-table'=>'clientdata_profiles','_id'=>$id));
	}
  	else{
		$ajson=$profile['jdoc'];
		//update their profile if it has changed
		if($ama['postal_code'] != $profile['postal_code']){
			$copts=array(
				'-table'=>'clientdata_profiles',
				'-where'=>"_id={$profile['_id']}",
				'jdoc'=>$ajson
			);
			if(isset($ama['postal_code'])){
	            $zone=alexaGetTimeZone($ama['postal_code']);
	            $zarr=json_decode($zone,1);
	            foreach($zarr as $zk=>$zv){
	               	$ama[$zk]=$zv;
				}
				$copts['jdoc']=json_encode($ama);
			}
			$ok=editDBRecord($copts);
			$profile=getDBRecord(array('-table'=>'clientdata_profiles','_id'=>$profile['_id']));
		}
		return $profile;
	}
	return 0;
}
function pageLoginWithGoogleHome($access_token){
	//https://developers.google.com/identity/protocols/OAuth2UserAgent
}
?>
