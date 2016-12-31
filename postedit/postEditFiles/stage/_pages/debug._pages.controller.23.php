<?php
setView('default',1);
/* $recs=getDBRecords(array('-table'=>'dictionary_categories'));
foreach($recs as $rec){
	$rec['word']=strtolower($rec['word']);
	$rec['category']=strtolower($rec['category']);
	$rec['word']=preg_replace('/[^a-z\-]+/i','',$rec['word']);
	$query="update wordnet set category='{$rec['category']}' where word='{$rec['word']}'";
	$ok=executeSQL($query);
	echo "{$ok}-{$query}<br>\n";
}
exit; */
if(isset($_REQUEST['passthru'][0])){
	$_REQUEST['func']=$_REQUEST['passthru'][0];
}
switch(strtolower($_REQUEST['func'])){
	case 'json':
		echo printValue(json_decode($_REQUEST['json'],1));
		exit;
	break;
}
?>
