<?php
/* twitter functions
	http://apiwiki.twitter.com/Twitter-API-Documentation
*/
$progpath=dirname(__FILE__);
//-----------------------
function twitterUpdateStatus($params=array()){
	//info: update your twitter status - requires authentication
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	if(!isset($params['status'])){return "No status message";}
	$url='http://twitter.com/statuses/update.xml';
	$opts=array(
		'status'		=> $params['status'],
		'-auth'			=> "{$params['-user']}:{$params['-pass']}"
		);
	//return printValue($opts);
	$rtn=postURL($url,$opts);
	return $rtn;
	try {
		$xml = new SimpleXmlElement($rtn['body']);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
	return "Unknown Error";
	}
//-----------------------
function twitterPublicTimeline($params=array()){
	//info: updates from everyone - does not require authentication
	$url='http://twitter.com/statuses/public_timeline.rss';
	$opts=array(
		'-method'	=> "GET",
		);
	//return printValue($opts);
	$rtn=postURL($url,$opts);
	try {
		$xml = new SimpleXmlElement($rtn['body']);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
	return "Unknown Error";
	}
//-----------------------
function twitterFriendsTimeline($params=array()){
	//info: updates from one person - requires authentication
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	$url='http://twitter.com/statuses/friends_timeline.xml';
	$opts=array(
		'-method'	=> "GET",
		'-auth'		=> "{$params['-user']}:{$params['-pass']}"
		);
	//return printValue($opts);
	$rtn=postURL($url,$opts);
	try {
		$xml = new SimpleXmlElement($rtn['body']);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
	return "Unknown Error";
	}
//-----------------------
function twitterFriendsStatus($params=array()){
	//info: lists status of friends I am following
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	$url='http://twitter.com/statuses/friends.xml';
	$opts=array(
		'-method'	=> "GET",
		'-auth'		=> "{$params['-user']}:{$params['-pass']}"
		);
	//return printValue($opts);
	$rtn=postURL($url,$opts);
	try {
		$xml = new SimpleXmlElement($rtn['body']);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
	return "Unknown Error";
	}
//-----------------------
function twitterFollowersStatus($params=array()){
	//info: lists status of followers
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	$url='http://twitter.com/statuses/followers.xml';
	$opts=array(
		'-method'	=> "GET",
		'-auth'		=> "{$params['-user']}:{$params['-pass']}"
		);
	//return printValue($opts);
	$rtn=postURL($url,$opts);
	try {
		$xml = new SimpleXmlElement($rtn['body']);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
	return "Unknown Error";
	}
?>