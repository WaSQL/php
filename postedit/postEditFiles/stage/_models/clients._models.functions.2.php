<?php
/* Available functions: Each function should return the array that was passed in.

	//addDBRecord triggers
	function clientsAddBefore($params=array()){}
	function clientsAddSuccess($params=array()){}
	function clientsAddFailure($params=array()){}

	//editDBRecord triggers
	function clientsEditBefore($params=array()){}
	function clientsEditSuccess($params=array()){}
	function clientsEditFailure($params=array()){}

	//delDBRecord triggers
	function clientsDeleteBefore($params=array()){}
	function clientsDeleteSuccess($params=array()){}
	function clientsDeleteFailure($params=array()){}

	//getDBRecord, listDBRecords, and getDBRecords trigger
	function clientsGetRecord($rec=array()){}
*/
function clientsAddSuccess($params=array()){
	//create a apikey from the name and id using the apiseed as salt
	$apikey="{$params['-record']['_id']}-".encrypt("{$params['-record']['_id']}:{$params['-record']['name']}",$params['-record']['apiseed']);
	$apikey=encodeBase64($apikey);
	$ok=executeSQL("update clients set apikey='{$apikey}' where _id={$params['-record']['_id']}");
}
function clientsEditSuccess($params=array()){
	foreach($params['-records'] as $rec){
		//create a apikey from the name and id using the apiseed as salt
		$apikey="{$rec['_id']}-".encrypt("{$rec['_id']}:{$rec['name']}",$rec['apiseed']);
		$apikey=encodeBase64($apikey);
		if($apikey != $rec['apikey']){
			$apikey=str_replace("'","''",$apikey);
			$ok=executeSQL("update clients set apikey='{$apikey}' where _id={$rec['_id']}");
		}
	}
}
?>
