<?php
/*
	Name: SalesTalk for Google Home
	AppId: amzn1.echo-sdk-ams.app.c37ff67c-05a2-4834-90a8-e49741379d6f
	Created: by slloyd on 2016-12-14

*/
loadDBFunctions(array('functions_common'));
global $PAGE;
global $google;
$headers=getallheaders();
$postdata=file_get_contents("php://input");
//setFileContents('googlehome.txt',$postdata);
$google=@json_decode($postdata,true);
//setFileContents('googlehome.txt',printValue($google));
$slots=$google['result']['parameters'];
$slots['report']=preg_replace('/[^a-z0-9\_\-\ ]/','',$slots['report']);
$addopts=array(
	'-table'	=> 'alexa_log',
	'request'	=> $postdata,
	'appid'		=> $google['id'],
	'slots'		=> json_encode($slots),
	'timestamp'	=> strtotime($google['timestamp']),
	'skill'		=> 'skillsai_googlehome',
	'request_header'	=> json_encode($headers),
	'request_url'		=> $_SERVER['HTTP_REFERER']
);
//convert slots to a swim and then to json

$swim=array('name'=>$slots['report']);
//setFileContents('googlehome.txt',printValue($slots));
if(isset($slots['date-period'][0])){
	list($swim['from_date'],$swim['to_date'])=preg_split('/\/+/',trim($slots['date-period'][0]),2);
}
elseif(isset($slots['date-period'])){
	/*  2015-01-01/2015-12-31  */
	list($swim['from_date'],$swim['to_date'])=preg_split('/\/+/',trim($slots['date-period']),2);
}
if(isset($slots['geo-city'])){
	$swim['city']=$slots['geo-city'];
}
//if both from and to dates are in the future, change year
$future=0;
if(strtotime($swim['from_date']) > strtotime(date('Y-m-d'))){$future++;}
if(strtotime($swim['to_date']) > strtotime(date('Y-m-d'))){$future++;}
if($future==2){
	$y=date('Y',strtotime($swim['from_date']));
	$y-=1;
	$swim['from_date']=date("{$y}-m-d",strtotime($swim['from_date']));
	$swim['to_date']=date("{$y}-m-d",strtotime($swim['to_date']));
}

if(isset($slots['geo-state-us'])){
	$swim['state']=$slots['geo-state-us'];
}
if(strlen($slots['report_type'])){
	$report_type=$slots['report_type'];
}
else{$report_type='speech';}
$swim['client_id']=2;
$addopts['swim']=json_encode($swim);
//setFileContents('googlehome.txt',printValue($swim));
$swim['logid']=addDBRecord($addopts);
$xrec=array('databack'=>$report_type,'name'=>$slots['report'],'logid'=>$swim['logid'],'filters'=>array());
foreach($swim as $k=>$v){
	if($k=='logid'){continue;}
	$xrec['filters'][$k]=$v;
}
setFileContents('googlehome.txt',printValue($xrec));
$response=commonLoadReportData($xrec);
$ok=pageResponse($response);

?>
