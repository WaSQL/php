<?php
function topmenuGetPreBuiltTables(){
	$recs=array(
		array('name'=>'cities','file'=>'all_cities'),	
		array('name'=>'states','file'=>'all_states'),
		array('name'=>'countries','file'=>'all_countries'),
		array('name'=>'colors','file'=>'all_colors'),
	);
	foreach($recs as $i=>$rec){
		if(isDBTable($rec['name'])){
			$recs[$i]['class']='icon-checkbox';
			$recs[$i]['onclick']="if(!confirm('A {$rec['name']} table already exists. Rebuild this table?')){return false;}else{return true;}";
		}
		else{
			$recs[$i]['class']='icon-checkbox-empty';
			$recs[$i]['onclick']="if(!confirm('Create this table - {$rec['name']}?')){return false;}else{return true;}";
		}
	}
	return $recs;
}
?>