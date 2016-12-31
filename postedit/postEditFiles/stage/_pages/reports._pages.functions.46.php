<?php
function reportMonthYear($cid){
	if(isset($_REQUEST['date']) && !stringContains($_REQUEST['date'],'undef')){return $_REQUEST['date'];}
	return date('Y M');
}
function reportSelectCountry($id){
	return 'COUNTRY';
	$opts=array();
	$query=<<<ENDOFQUERY
	SELECT
		count(*) cnt
		,ship_to_country
	FROM "BODSSCHEMA"."ORAODH"
	WHERE ship_to_country !=''
		and status in (2,3,4,5)
		and type in ('I','D','C')
	GROUP BY ship_to_country
	HAVING count(*) > 10
	ORDER BY ship_to_country
ENDOFQUERY;
	$recs=getDBRecords($query);
	foreach($recs as $rec){
		$opts[$rec['ship_to_country']]=$rec['ship_to_country'];
	}
	return buildFormSelect('country',$opts,array('message'=>'--All Countries--','id'=>"country_{$id}",'class'=>'form-control input-sm','onchange'=>"return  reportChangeFilters('{$id}');"));
}
function reportActiveTab($id){
	if($_REQUEST['tab_id']==$id){return ' active';}
	return '';
}
function reportGetUserTabs(){
	global $USER;
	return getDBRecords(array(
		'-table'	=> 'user_tabs',
		'user_id'	=> $USER['_id'],
		'-order'	=> 'list_order,name'
	));
}
function reportAddUserTab($name){
	global $USER;
	$id=addDBRecord(array(
		'-table'	=> 'user_tabs',
		'user_id'	=> $USER['_id'],
		'name'		=> $name
	));
	return reportGetUserTabs();
}
function reportEditUserTab($id,$name){
	global $USER;
	$ok=editDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']} and _id={$id}",
		'name'		=> $name
	));
	return reportGetUserTabs();
}
function reportDelUserTab($id){
	global $USER;
	$id=delDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']} and _id={$id}",
	));
	return reportGetUserTabs();
}
function reportUpdateTabSort($str){
	global $USER;
	$ids=preg_split('/[\:\,]+/',$str);
	$sort=0;
	foreach($ids as $id){
		$id=editDBRecord(array(
			'-table'	=> 'user_tabs',
			'-where'	=> " _id={$id}",
			'list_order'=>$sort
		));
		$sort+=1;
	}
	return reportGetUserTabs();
}
function reportGetUserTabReports($tabid){
	if(!strlen($tabid)){return array();}
	$query=<<<ENDOFQUERY
	SELECT
		kt.defaults
		,kt.data_table
		,k.name
		,k.body
		,k.data
		,k.category
		,kt._id
		,kt.report_id
		,kt.user_tab_id
	FROM
		reports k, report_tabs kt
	WHERE
		k._id=kt.report_id
		and k.active=1
		and kt.user_tab_id={$tabid}
	ORDER BY kt.list_order,k.name
ENDOFQUERY;
	$recs=getDBRecords(array('-query'=>$query));
	return $recs;
}

function reportGetReportTabRec($id){
	$query=<<<ENDOFQUERY
	SELECT
		kt.defaults
		,kt.data_table
		,k.name
		,k.body
		,k.category
		,k.data
		,kt._id
		,kt.report_id
		,kt.user_tab_id
	FROM
		reports k, report_tabs kt
	WHERE
		k._id=kt.report_id
		and k.active=1
		and kt._id={$id}
ENDOFQUERY;
	$rec=getDBRecord(array('-query'=>$query));
	return $rec;
}

function reportAddUserTabReports($tab_id,$report_id){
	global $USER;
	$id=addDBRecord(array(
		'-table'	=> 'report_tabs',
		'report_id'	=> $report_id,
		'user_tab_id'=> $tab_id
	));
	return reportGetUserTabReports($tab_id);
}
function reportSortUserTabReports($str,$tab_id){
	global $USER;
	$ids=preg_split('/[\:\,]+/',$str);
	$sort=0;
	foreach($ids as $id){
		$id=editDBRecord(array(
			'-table'	=> 'report_tabs',
			'-where'	=> " _id={$id}",
			'list_order'=>$sort
		));
		$sort+=1;
	}
	return reportGetUserTabReports($tab_id);
}
function reportDelUserTabReports($tab_id,$report_id){
	global $USER;
	$opts=array(
		'-table'	=> 'report_tabs',
		'-where'	=> "_id={$report_id} and user_tab_id={$tab_id} and user_tab_id in (select _id from user_tabs where user_id={$USER['_id']})"
	);
	//echo printValue($opts);exit;
	$id=delDBRecord($opts);
	return reportGetUserTabReports($tab_id);
}



function pageGetUserReports($tabid){
	global $USER;
	$opts=array(
		'-table'	=> 'reports',
		'active'	=> 1
	);
	return getDBRecords($opts);
}
function pageGetReports($id=0){
	global $USER;
	$id=0;
	$opts=array(
		'-table'	=> 'reports',
		'active'	=> 1
	);
	if($id > 0){
    	$opts['-filter']="_id in (select report_id from report_tabs where _id={$id})";
    //	echo printValue($opts);exit;
    	$recs=getDBRecords($opts);
    	return $recs[0];
	}
	$recs= getDBRecords($opts);
	return $recs;
	//echo printValue($opts).printValue($recs);exit;
}
function pageLoadReport($xrec){
	global $rec;
	$rec=$xrec;
	if(strlen($rec['defaults'])){
        $defaults=json_decode($rec['defaults'],true);
        foreach($defaults as $k=>$v){
			$_REQUEST[$k]=$v;
		}
	}
	$evalstr='<?global $'.'rec;?>'."\n".$rec['body'];
	return evalPHP($evalstr);
}

function pageLoadReportData($xrec){
	global $rec;
	$rec=$xrec;
	$evalstr='<?global $'.'rec;?>'."\n".$rec['data'];
	return evalPHP($evalstr);
}


function homeGetUserReports(){
	global $USER;
	$query=<<<ENDOFQUERY
SELECT
	k.report_id
	,p.permalink
	,p.title
	,k.display_order
	,p.report_group
FROM report_users k, _pages p
WHERE
	k.report_id=p._id
	and user_id={$USER['_id']}
ORDER BY k.display_order
ENDOFQUERY;
	$recs=getDBRecords(array(
		'-query'	=> $query
	));
	foreach($recs as $i=>$rec){
		$recs[$i]['classname']="p{$rec['report_id']}";
	}
	return $recs;
}
?>
