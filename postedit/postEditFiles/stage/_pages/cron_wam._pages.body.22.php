<?php
/*
	READ THE WAM TABLE
		host, port=80, notify_email, params
	ping - returns milliseconds it took to resond or fals on failure - ping('www.skillsai.com');
		Possible states
			down - does not ping
			slow - takes longer than x seconds to respond
			
			submit to skillsai chart portal
				response time
				response code
				response message if down
				ping datetime
			run every 5 minutes
			
			chart based on response time



	customer

*/
//$skapi_url='https://stage.skillsai.com/skapi/wam/MS1tSTIrbkpxZGtLdmF0bUN6bjY0PQ==';

$recs=wamGetClientRecords();
//echo "clients".printValue($recs);exit;
foreach($recs as $rec){
	if(stringContains($rec['url'],'skillsai.com')){continue;}
	$p=postURL($rec['url'],array('-method'=>"GET",'-follow'=>1));
	//echo printValue($p);exit;
	$wam=$p['curl_info'];
	$wam['server']=$p['headers']['server'];
	$wam['logdate']=date('Y-m-d H:i:s',strtotime($p['headers']['date']));
	$wam['url']=$p['url'];
	if($wam['http_code'] != 200){
    	$wam['body']=$p['body'];
	}
	unset($wam['request_header']);
	unset($wam['local_port']);
	unset($wam['local_ip']);
	foreach($wam as $k=>$v){
    	if(is_array($v)){
			if(!count($v)){unset($wam[$k]);}
		}
    	elseif(!strlen($v)){unset($wam[$k]);}
    	elseif(isNum($v) && $v < 0){unset($wam[$k]);}
	}
	$wam['client_id']=$rec['_id'];
	$wam['-table']='clientdata_wam_log';
	$id=addDBRecord($wam);
	echo "{$rec['url']} -> {$id} <br />\n";
	continue;
	$skapi_url="https://www.skillsai.com/skapi/wam/{$rec['apikey']}/";
	$ok=postJSON($skapi_url,$json);
	echo $skapi_url.printValue($ok['body'])."<br />\n";

}
?>
