<?php
setView('default');
loadExtrasJs('wd3');
$viewname='';
if(isset($_REQUEST['passthru'][0])){
	if(strtolower($_REQUEST['passthru'][0])=='contribution_chart'){
		setView('contribution_chart',1);
		return;
	}
	if(strtolower($_REQUEST['passthru'][0])=='vote'){
    	$vote=pageGetUserVote();
    	$dcid=0;
    	if(isNum($_REQUEST['vote'][0])){$dcid=$_REQUEST['vote'][0];}
    	elseif(isNum($_REQUEST['vote'])){$dcid=$_REQUEST['vote'];}
    	if(!isset($vote['_id'])){
			$addopts=array(
				'-table'=>'donorschoose_votes',
				'guid'=>$_SERVER['GUID'],
				'email'=>addslashes($_REQUEST['email']),
				'donorschoose_id'=>$dcid
			);
			if(isNum($_REQUEST['opt_in'])){
				$addopts['opt_in']=1;
			}
        	$id=addDBRecord($addopts);
			setView('vote',1);
		}
    	else{
        	setView('voted',1);
		}
    	return;
	}
	$viewname=strtolower($_REQUEST['passthru'][0]);
}
else{setView('boxes');}
/*

subscribing to blog

product subscription


guid varchar(150) NOT NULL
email varchar(255) NOT NULL
vote_month
remind me each month to vote

remind me each month

*/

?>
