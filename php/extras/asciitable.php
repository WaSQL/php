<?php
/* asciitable - convert arrays to ascii:
	https://github.com/pgooch/PHP-Ascii-Tables
*/
//load the common functions library

$progpath=dirname(__FILE__);
require_once("{$progpath}/asciitable/ascii_table.php");
//---------- begin function asciitableCreate
/**
* @describe returns an ASCII table rendition of the recs
* @param params array - multi-dimensional record sets
* @param [title] - title of to display above the table
* @return string - ascii table to display
* @usage
*	loadExtras('asciitable');
*	$recs=getDBRecords(array('-table'=>'states','-limit'=>10));
*	echo '<pre>'.asciitableCreate($recs,'States').'</pre>';
*/
function asciitableCreate($arr=array(),$title=''){
	$ascii_table = new ascii_table();
	return $ascii_table->make_table($arr,$title,true,true);
}
?>