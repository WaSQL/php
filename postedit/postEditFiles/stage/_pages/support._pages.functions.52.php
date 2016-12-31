<?php
function pageNavClass($nav){
	if(stringContains($_SERVER['REQUEST_URI'],$nav)){return ' active';}
	return '';
}
function pageGetFAQs($category,$subcategory){
	$opts=array(
		'-table'=>'faq',
		'category'=>$category,
		'subcategory'=>$subcategory,
		'active'=>1
	);
	return getDBRecords($opts);
}
function pageGetFAQGroups(){
	$query=<<<ENDOFQUERY
	SELECT
		_id
		,category
		,subcategory
		,question
		,answer
	FROM faq
	WHERE active=1
	ORDER BY
		category
		,subcategory
		,question
ENDOFQUERY;
	$recs=getDBRecords($query);
	$groups=array();
	foreach($recs as $rec){
    	$groups[$rec['category']][$rec['subcategory']][]=$rec;
	}
	//echo printValue($groups);
	return $groups;
}
function pageAddEdit($id=0){
	$opts=array(
		'-table'=>'support_issues',
		'-fields'=>'email,subject:priority,description',
		'description_'=>'width:100%;',
		'-save'	=> 'Submit Ticket',
		'-onsubmit' => "return ajaxSubmitForm(this,'results')",
		'-action'	=> "/t/1/support/submit"
	);
	if($id != 0){$opts['_id']=$id;}
	return addEditDBForm($opts);
}
function pageSearchResults($search){
	$opts=array(
		'-table'=>'support_issues',
		'-order'=>'_id desc',
		'-limit'=>100,
		'-relate'=>1
	);
	if(isNum($search)){$opts['ticket']=$search;}
	else{$opts['email']=$search;}
	$recs=getDBRecords($opts);
	if(!is_array($recs)){return array();}
	return $recs;
}
?>
