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
			unset($recs[$i]);
		}
	}
	return $recs;
}
?>