<?php
/* 
	Excel functions
		https://github.com/AsperaGmbH/xlsx-reader
		https://github.com/AsperaGmbH/xlsx-reader/archive/refs/heads/master.zip

*/
$progpath=dirname(__FILE__);

//check to see if you have the zip. If not go get it.
if(!is_file("{$progpath}/xlsx-reader/master.zip")){
	$xfile='https://github.com/AsperaGmbH/xlsx-reader/archive/refs/heads/master.zip';
	wget($xfile,"{$progpath}/xlsx-reader/master.zip");
	if(!is_file("{$progpath}/xlsx-reader/master.zip")){
		echo "Error: unable to install xlsx-reader automatically.";
		exit;
	}
	loadExtras('zipfile');
	$files=zipExtract("{$progpath}/xlsx-reader/master.zip","{$progpath}/xlsx-reader");
}
//include the php file in lib
$files=listFilesEx("{$progpath}/xlsx-reader/xlsx-reader-master/lib",array('ext'=>'php'));
foreach($files as $file){
	include_once($file['afile']);
}
//---------- begin function xlsxreaderGetRecords like getCSVRecords---------------------------------------
/**
* @describe returns csv file contents as recordsets
* @param file string
*	full path and name of the file to inspect
* @param params array
*	[-function] str - function to send each rec to as it processes the csv file
*	[-maxlen] int - max row length. defaults to 0
*	[-separator] char - defaults to ,
*	[-enclose] char - defaults to "
*	[-fields]  array - an array of fields for the CSV.  If not specified it will use the first line of the file for field names
* 	[-listfields] mixed - comman separated list of fields to return
*	[-start|skiprows] int - line to start on
*	[-maxrows|stop] int - max number of rows to return
*	[-map] array - fieldname map  i.e. ('first name'=>'firstname','fname'=>'firstname'.....)
*	any additional key/values passed in will be added to each rec
* @return array - recordsets
* @usage
*	$recs=xlsxreaderGetRecords($afile);
*/
function xlsxreaderGetRecords($params=array()){
	
	return;
}

?>