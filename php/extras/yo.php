<?php
/* 
	Yo API functions
	see http://dev.justyo.co/yo/dashboard.html

*/
$progpath=dirname(__FILE__);

//---------- begin function yoAll ----------
/**
* @describe Send A Yo To All Subscribers
* @param array 
*	app_token  Your Yo app_token
*	[link] - optional link to send subscribers to

* @return true
* @usage
*	yoAll(array(
*		'app_token'	=> 'YOURYOAPPTOKEN',
*		'link'	=> 'http://www.yoursite.com',
*	));
*/
function yoAll($params=array()){
	//auth tokens are required
	$required=array('app_token');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "yoAll Error: Missing required param '{$key}'";
		}
	}
	$postopts=array(
		'app_token'	=> $params['app_key'],
		'-method'	=> 'POST',
		'-ssl'		=> 0,
		'-json'		=> 1
	);
	if(isset($params['link'])){$postopts['link']=$params['link'];}
	$url='http://api.justyo.co/yoall/';
	$post=postURL($url,$postopts);
	return;
}
//---------- begin function yoOne ----------
/**
* @describe Send A Yo To One Subscriber
* @param array 
*	app_token  Your Yo app_token
*	username	username of user to send to
*	[link] - optional link to send subscriber to

* @return true
* @usage
*	yoOne(array(
*		'app_token'	=> 'YOURYOAPPTOKEN',
*		'username'	=> 'theirusername',
*		'link'	=> 'http://www.yoursite.com',
*	));
*/
function yoOne($params=array()){
	//auth tokens are required
	$required=array('app_token','username');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "yoOne Error: Missing required param '{$key}'";
		}
	}
	$postopts=array(
		'app_token'	=> $params['app_key'],
		'username'	=> $params['username'],
		'-method'	=> 'POST',
		'-ssl'		=> 0,
		'-json'		=> 1
	);
	if(isset($params['link'])){$postopts['link']=$params['link'];}
	$url='http://api.justyo.co/yo/';
	$post=postURL($url,$postopts);
	return;
}
//---------- begin function yoCount ----------
/**
* @describe returns subscriber count
* @param array 
*	app_token  Your Yo app_token

* @return true
* @usage
*	$subscriber_count=yoCount(array(
*		'app_token'	=> 'YOURYOAPPTOKEN',
*	));
*/
function yoCount($params=array()){
	//auth tokens are required
	$required=array('app_token');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "yoCount Error: Missing required param '{$key}'";
		}
	}
	$postopts=array(
		'app_token'	=> $params['app_key'],
		'-method'	=> 'GET',
		'-ssl'		=> 0,
		'-json'		=> 1
	);
	$url='http://api.justyo.co/subscribers_count/';
	$post=postURL($url,$postopts);
	if(isset($post['json_array']['result'])){
    	return $post['json_array']['result'];
	}
	return $post;
	return;
}
?>