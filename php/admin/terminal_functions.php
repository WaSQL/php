<?php
function terminalTableCheck(){
	global $USER;
	if(!isUser()){return false;}
	if(!isDBTable('_terminal')){
		return createWasqlTable('_terminal');
	}
	else{
		$query=<<<ENDOFQUERY
DELETE FROM _terminal 
WHERE _cuser = {$USER['_id']} 
AND _id < (
   SELECT _id FROM (
       SELECT _id FROM _terminal 
       WHERE _cuser = {$USER['_id']}
       ORDER BY _id DESC 
       LIMIT 1 OFFSET 49
   ) AS subquery
)
ENDOFQUERY;
		$ok=executeSQL($query);
	}
	return false;
}
function terminalAddHistory($cmd,$result){
	if(!strlen($cmd)){return false;}
	$ok=addDBRecord(array(
		'-table'=>'_terminal',
		'cmd'=>$cmd,
		'-upsert'=>'ignore'
	));
}
function terminalGetHistory(){
	global $USER;
	if(!isUser()){return array();}
	return getDBRecords(array(
		'-table'=>'_terminal',
		'_cuser'=>$USER['_id'],
		'-order'=>'_edate DESC,_cdate DESC',
		'-limit'=>50,
		'-nocache'=>1
	));
}
	
?>
