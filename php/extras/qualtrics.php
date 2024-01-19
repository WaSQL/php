<?php
/*
	NOTES:
		qualtrics_token must be set in the config.xml file for these functions to work
		qualtrics_baseurl can also be set in config.xml. if not set is defaults to https://sjc1.qualtrics.com/API/v3
		add loadExtras('qualtrics') before calling these functions
	Example:
		loadExtras('qualtrics');
		$recs=qualtricsListSubscriptions();
		echo printValue($recs);exit;
	References:
		https://api.qualtrics.com/9bf03a83945b9-list-subscriptions
		https://community.qualtrics.com/qualtrics-api-13/how-to-unsubscribe-from-an-event-via-the-api-20261?postid=46524#post46524

 */

/*
* @reference https://api.qualtrics.com/013df5106e3c7-list-directories-for-a-brand
*/
function qualtricsListDirectories(){
	$url=qualtricsBaseURL().'/directories/';
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsDirectories Error:".printValue($recs);exit;
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/d326cdc7e69ae-list-directory-contacts
*/
function qualtricsListDirectoryContacts($directoryID){
	$url=qualtricsBaseURL()."/directories/{$directoryID}/contacts";
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsDirectoryContacts Error:".printValue($recs);exit;
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/2c09bb20f50cc-list-sms-distribution
*/
function qualtricsListSMSDistributions($surveyID){
	$url=qualtricsBaseURL()."/distributions/sms";
	$recs=qualtricsGetList($url,array('surveyId'=>$surveyID));
	if(isset($recs['curl_info'])){
		echo "qualtricsDistributionsSMS Error:".printValue($recs);exit;
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/0f7e43cc91c22-list-groups
*/
function qualtricsListGroups(){
	$url=qualtricsBaseURL().'/groups/';
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsGroups Error:".printValue($recs);exit;
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/9bf03a83945b9-list-subscriptions
*/
//returns a list of subscriptions: id,scope,topics,publicationUrl,encrypted,successfulCalls
function qualtricsListSubscriptions(){
	$url=qualtricsBaseURL().'/eventsubscriptions/';
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsListSubscriptions Error:".printValue($recs);exit;
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/c1ac8d2e88023-delete-subscription
*/
function qualtricsDeleteSubscription($subscriptionID){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "ERROR: missing qualtrics_token in config.xml";
		exit;
	}
	$post=postURL(
	    qualtricsBaseURL()."/eventsubscriptions/{$subscriptionID}",
	    array(
	        '-method'=>"DELETE",
	        '-json'=>1,
	        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
	    )
	); 
	if(isset($post['json_array']['meta'])){
		return $post['json_array']['meta'];
	}
	echo "Failed to get subscriptions".printValue($post);exit;
	return $post['json_array'];
}
/*
* @reference https://api.qualtrics.com/60922f13f4abe-get-subscription
*/
function qualtricsGetSubscription($subscriptionID){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "ERROR: missing qualtrics_token in config.xml";
		exit;
	}
	$post=postURL(
	    qualtricsBaseURL()."/eventsubscriptions/{$subscriptionID}",
	    array(
	        '-method'=>"GET",
	        '-json'=>1,
	        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
	    )
	); 
	if(isset($post['json_array'])){
		return $post['json_array'];
	}
	echo "Failed to get subscriptions".printValue($post);exit;
	return $post['json_array'];
}
/*
* @reference https://api.qualtrics.com/9c31e43fef682-create-subscription
*/
function qualtricsCreateSubscription($surveyID,$endpoint){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "ERROR: missing qualtrics_token in config.xml";
		exit;
	}
	$arr=array(
		"topics"=>"surveyengine.completedResponse.{$surveyID}",
	    "publicationUrl"=>$endpoint,
	    "encrypt"=>false
	);
	$json=encodeJSON($arr);
	$post=postJSON(
	    qualtricsBaseURL().'/eventsubscriptions/',
	    $json,
	    array(
	        '-method'=>"POST",
	        '-json'=>1,
	        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
	    )
	); 
	if(isset($post['json_array'])){
		return $post['json_array'];
	}
	echo "Failed to subscribe".printValue($post);exit;
}
/*
* @reference https://api.qualtrics.com/1179a68b7183c-retrieve-a-survey-response
*/
function qualtricsSurveyResponse($surveyID,$responseID){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "ERROR: missing qualtrics_token in config.xml";
		exit;
	}
	$post=postURL(
	    qualtricsBaseURL()."/surveys/{$surveyID}/responses/{$responseID}",
	    array(
	        '-method'=>"GET",
	        '-json'=>1,
	        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
	    )
	); 
	if(isset($post['json_array']['result'])){
		return $post['json_array']['result'];
	}
	echo "Failed to get response".printValue($post);exit;
	return $post['json_array'];
}
/*
* @reference https://api.qualtrics.com/c9eeb409d7fe2-list-users
*/
function qualtricsListUsers(){
	$url=qualtricsBaseURL().'/users/';
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsUsers Error: Failed to get users list".printValue($recs);exit;
	}
	return $recs;
}
/*
*  gets the qualtrics base url from config.xml. Defaults to https://sjc1.qualtrics.com/API/v3
*/
function qualtricsBaseURL(){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_baseurl'])){
		return 'https://sjc1.qualtrics.com/API/v3';
	}
	return $CONFIG['qualtrics_baseurl'];
}
/*
*  calls qualtrics and loops through all nextPages to return a list
*/
function qualtricsGetList($url,$opts=array()){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "ERROR: missing qualtrics_token in config.xml";
		exit;
	}
	$recs=array();
	$params=array(
        '-method'=>"GET",
        '-json'=>1,
        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
    );
    foreach($opts as $k=>$v){
    	$params[$k]=$v;
    }
	while(1){
		$post=postURL($url,$params); 
	    if($post['curl_info']['http_code'] != 200){
	    	return $post;
	    }
		if(!isset($post['json_array']['result']['elements'])){
			break;
		}
		foreach($post['json_array']['result']['elements'] as $rec){
			$recs[]=$rec;
		}
		if(!isset($post['json_array']['result']['nextPage']) || !strlen($post['json_array']['result']['nextPage'])){
			break;
		}
		$url=$post['json_array']['result']['nextPage'];
	}
	return $recs;
}
?>