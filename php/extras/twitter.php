<?php
/* twitter functions
	http://apiwiki.twitter.com/Twitter-API-Documentation
*/
$progpath=dirname(__FILE__);
//---------- begin function twitterTweets-------------------
/**
* @describe scrapes tweets from a twitter url - no auth required
* @param url string
* @param cnt integer  number of tweets to scrape
* @param hrs integer  number of hours to cache.  Set to 0 to disable cache. Defaults to 3 hours
* @return array
* @usage $tweets=twitterTweets('https://twitter.com/@myurl',3);
*/
function twitterTweets($url,$cnt=3,$cache=3) {
	$page=getStoredValue("return twitterFetch('{$url}');",0,$cache);
	if(!strlen($page)) {
		return '';
	}
	preg_match_all('/\<p class="(.+?)\"(.+?)\>(.+?)\<\/p\>/ism',$page,$ptags,PREG_SET_ORDER);
	$tweets=array();
	foreach($ptags as $ptag){
        $htm=$ptag[0];
        if(!preg_match('/tweet/',$ptag[1])){continue;}
        //echo printValue($ptag);
        $txt=preg_replace('/\<a(.+?)\<\/a\>/','',$htm);
		$tag=array('txt'=>strip_tags($txt));
        if(preg_match('/a href\=\"(http|https)(\:\/\/)(.+?)\"/',$htm,$m)){
			$tag['url']=$m[1].$m[2].$m[3];
		}
		$tweets[]=$tag;
		if(count($tweets) == $cnt){return $tweets;}
	}
	return $tweets;
}
function twitterFetch($url){
	$ch = curl_init($url);
	curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0"); // mask as firefox 3
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);  //disable ssl certificate validation
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($ch,CURLOPT_FAILONERROR,1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);	// allow redirects
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);	// return into a variable
	$page = curl_exec($ch);
	if(!$page) {
		return '';
	}
	return $page;
}
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