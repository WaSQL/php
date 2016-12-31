<?php
/* Available functions: Each function should return the array that was passed in.

	//addDBRecord triggers
	function support_issuesAddBefore($params=array()){}
	function support_issuesAddSuccess($params=array()){}
	function support_issuesAddFailure($params=array()){}

	//editDBRecord triggers
	function support_issuesEditBefore($params=array()){}
	function support_issuesEditSuccess($params=array()){}
	function support_issuesEditFailure($params=array()){}

	//delDBRecord triggers
	function support_issuesDeleteBefore($params=array()){}
	function support_issuesDeleteSuccess($params=array()){}
	function support_issuesDeleteFailure($params=array()){}

	//getDBRecord, listDBRecords, and getDBRecords trigger
	function support_issuesGetRecord($rec=array()){}
*/
function support_issuesAddSuccess($params=array()){
	//update ticket number
	$ticket=date('YW').$params['-record']['_id'];
	$ok=executeSQL("update support_issues set ticket={$ticket} where _id={$params['-record']['_id']}");
	//add to freshdesk
	loadDBFunctions('functions_freshdesk');
	$freshfields=array('description','email','priority','status','subject');
	$fresh=array();
	foreach($freshfields as $field){
    	$fresh[$field]=$params['-record'][$field];
	}
	$frec=freshdeskCreateTicket($fresh);
	if(isset($frec['id'])){
		$ok=executeSQL("update support_issues set freshdesk_id={$frec['id']} where _id={$params['-record']['_id']}");
	}
	else{
    	$ok=editDBRecord(array(
			'-table'	=> 'support_issues',
			'-where'	=> "_id={$params['-record']['_id']}",
			'freshdesk_error'=>printValue($frec)
		));
	}
}
?>
