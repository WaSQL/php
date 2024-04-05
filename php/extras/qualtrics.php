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
		return "qualtricsListDirectories Error:".printValue($recs);
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
		return "qualtricsListDirectoryContacts Error:".printValue($recs);
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
		return "qualtricsListSMSDistributions Error:".printValue($recs);
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
		return "qualtricsListGroups Error:".printValue($recs);
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
		return "qualtricsListSubscriptions Error:".printValue($recs);
	}
	return $recs;
}
/*
* @reference https://api.qualtrics.com/c1ac8d2e88023-delete-subscription
*/
function qualtricsDeleteSubscription($subscriptionID){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		echo "qualtricsDeleteSubscription ERROR: missing qualtrics_token in config.xml";
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
	return "qualtricsDeleteSubscription Error: Failed to get subscriptions".printValue($post);
	return $post['json_array'];
}
/*
* @reference https://api.qualtrics.com/60922f13f4abe-get-subscription
*/
function qualtricsGetSubscription($subscriptionID){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		return array('status'=>'failed','error'=>"missing qualtrics_token in config.xml");
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
	return "qualtricsGetSubscription Error: Failed to get subscription".printValue($post);
	return $post['json_array'];
}
/*
* @reference https://api.qualtrics.com/9c31e43fef682-create-subscription
*/
function qualtricsCreateSubscription($surveyID,$endpoint){
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		return array('status'=>'failed','error'=>"missing qualtrics_token in config.xml");
	}
	if(strlen($endpoint) > 255){
		return array('status'=>'failed','error'=>"endpoint cannot be longer than 255 characters");
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
	return "qualtricsCreateSubscription Error: Failed to subscribe".printValue($post);
}
/*
* @reference https://api.qualtrics.com/1179a68b7183c-retrieve-a-survey-response
*/
function qualtricsSurveyResponse($surveyID,$responseID){
	//https://sjc1.qualtrics.com/API/v3/surveys/SV_5hDTO04Y8uJo9Zc/responses
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		return array('status'=>'failed','error'=>"missing qualtrics_token in config.xml");
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
	return "qualtricsSurveyResponse Error: Failed to get response".printValue($post);
	return $post['json_array'];
}

function qualtricsSurveyResponses($surveyID){
	//https://api.qualtrics.com/206a07d54ca31-surveys-response-import-export-api
	global $CONFIG;
	if(!isset($CONFIG['qualtrics_token'])){
		return array('status'=>'failed','error'=>"missing qualtrics_token in config.xml");
	}
	$json=<<<ENDOFJSON
{
    "format": "json"
}
ENDOFJSON;
	$post=postJSON(
	    qualtricsBaseURL()."/surveys/{$surveyID}/export-responses",
	    $json,
	    array(
	        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
	    )
	); 
	/*
{
    "result": {
        "progressId": "ES_afJdGCwOyCEhOom",
        "percentComplete": 0.0,
        "status": "inProgress"
    },
    "meta": {
        "requestId": "41859ce6-efcb-44c1-a3ba-c826a59e1f40",
        "httpStatus": "200 - OK"
    }
}

	 */
	if(isset($post['json_array']['result']['progressId'])){
		$progressId=$post['json_array']['result']['progressId'];
		$loops=0;
		while($loops < 10){
			sleep(6);
			$post=postURL(
			    qualtricsBaseURL()."/surveys/{$surveyID}/export-responses/{$progressId}",
			    array(
			        '-method'=>"GET",
			        '-json'=>1,
			        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
			    )
			);
			if(isset($post['json_array']['result']['fileId'])){
				$fileId=$post['json_array']['result']['fileId'];
				$post=postURL(
				    qualtricsBaseURL()."/surveys/{$surveyID}/export-responses/{$fileId}/file",
				    array(
				        '-method'=>"GET",
				        '-headers'=>array("X-API-TOKEN: {$CONFIG['qualtrics_token']}")
				    )
				);
				//this returns a zip file
				$tpath=getWaSQLTempPath();
				$filename="qualtrics_surveyresponses_{$surveyID}.zip";
				$tfile="{$tpath}/{$filename}";
				setFileContents($tfile,$post['body']);
				loadExtras('zipfile');
				$files=zipExtract($tfile);
				foreach($files as $file){
					$json=getFileContents($file);
					$ids=array();
					foreach($json['responses'] as $response){
						$ids[]=array(
							'SurveyID'=>$surveyID,
							'ResponseID'=>$response['responseId']
						);
					}
					return $ids;
				}
			}
		}
	}
	return array();
}
/*
* @reference https://api.qualtrics.com/c9eeb409d7fe2-list-users
*/
function qualtricsListUsers(){
	$url=qualtricsBaseURL().'/users/';
	$recs=qualtricsGetList($url);
	if(isset($recs['curl_info'])){
		echo "qualtricsListUsers Error: Failed to get users list".printValue($recs);exit;
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
		return array('status'=>'failed','error'=>"missing qualtrics_token in config.xml");
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