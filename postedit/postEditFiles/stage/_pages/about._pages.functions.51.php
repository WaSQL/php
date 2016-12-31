<?php
function pageNavClass($nav){
	if(stringContains($_SERVER['REQUEST_URI'],$nav)){return ' active';}
	return '';
}
function pageProjectsAwarded(){
	$precs=getDBRecords(array(
		'-table'=>'donorschoose_projects',
		'-where'=>'project_date > DATE_SUB(now(), INTERVAL 6 MONTH)',
		'-index'=>'project_id'
	));
	//October 2016
	$project_ids=implode(',',array_keys($precs));
	$recs=array();
	$arecs=pageGetDonorsChooseProjects($project_ids);
	foreach($arecs as $i=>$rec){
		$arecs[$i]['project_date_utime']=strtotime($precs[$rec['id']]['project_date']);
	}
	$arecs=sortArrayByKeys($arecs,array('project_date_utime'=>SORT_ASC,'amount'=>SORT_DESC));

	//echo printValue($arecs);
	foreach($arecs as $i=>$rec){
		$rec['award']=$precs[$rec['id']]['award'];
		$rec['project_date']=$precs[$rec['id']]['project_date'];
		if(!isset($recs[$rec['project_date']]) || $rec['award'] > 0){
			$recs[$rec['project_date']]=$rec;
		}
	}
	foreach($recs as $i=>$rec){
    	if($rec['award']==0){
        	$recs[$i]['title']='';
        	$recs[$i]['city']='';
        	$recs[$i]['state']='';
        	$recs[$i]['award']='';
		}
	}
	$recs=sortArrayByKeys($recs,array('project_date_utime'=>SORT_DESC));
	//echo printValue($recs);
	return $recs;
}
function pageContributionChartCsv(){
	$recs=pageProjectsAwarded();
	$recs=sortArrayByKeys($recs,array('project_date_utime'=>SORT_ASC));
	//format the data into orders,Apr,May,Jun,Aug...Apr
	$data=array();
	foreach($recs as $rec){
		$subkey=date('M',$rec['project_date_utime']);
    	$data[$subkey]=round($rec['award'],0);
	}
	$lines=array();
	$line=array('label','value');
	$lines[]=implode(',',$line);
	foreach($data as $month=>$orders){
		$line=array($month,$orders);
		$lines[]=implode(',',$line);
	}
	return implode("\n",$lines);
}
function pageDonorsChooseList(){
	//API Reference: http://data.donorschoose.org/docs/overview/
	//https://api.donorschoose.org/common/json_feed.html?id=2197379,2069141&APIKey=DONORSCHOOSE
	$precs=getDBRecords(array(
		'-table'=>'donorschoose_projects',
		'-where'=>'award=0 and project_date > DATE_SUB(now(), INTERVAL 1 MONTH)',
		'-index'=>'project_id'
	));
	//October 2016
	$project_ids=implode(',',array_keys($precs));
	$recs=pageGetDonorsChooseProjects($project_ids);
	foreach($recs as $i=>$rec){
    	$recs[$i]['progressColor']='danger';
    	if($rec['percentFunded']==100){$recs[$i]['progressColor']='success';}
    	elseif($rec['percentFunded'] >=80){$recs[$i]['progressColor']='warning';}
    	$recs[$i]['votes']=getDBCount(array(
			'-table'=>'donorschoose_votes',
			'-where'=>"donorschoose_id={$rec['id']} and year(_cdate)=year(now()) and month(_cdate)=month(now())"
		));
		$recs[$i]['award']=$precs[$rec['id']]['award'];
	}
	return $recs;
}
function pageGetDonorsChooseProjects($project_ids){
	$url='https://api.donorschoose.org/common/json_feed.html';
	//keywords
	$opts=array(
		//'APIKey'	=> 'DONORSCHOOSE',
		'APIKey'	=> 's6u7656s9omn',
		'-method'	=> 'GET',
		'-json'		=> 1,
		'id'		=> $project_ids
	);
	$post=postURL($url,$opts);
	$recs=array();
	if(isset($post['json_array']['proposals'][0])){
		$recs=$post['json_array']['proposals'];
	}
	elseif(isset($post['json_array']['proposals'])){
    	$recs=array($post['json_array']['proposals']);
	}
	return $recs;
}
function pageGetUserVote(){
	$guid=$_SERVER['GUID'];
	return getDBRecord(array(
		'-table'=>'donorschoose_votes',
		'-where'=>"guid='{$guid}' and year(_cdate)=year(now()) and month(_cdate)=month(now())"
	));
}
?>
