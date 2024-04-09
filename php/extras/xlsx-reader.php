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
use Aspera\Spreadsheet\XLSX\Reader;
use Aspera\Spreadsheet\XLSX\ReaderConfiguration;
use Aspera\Spreadsheet\XLSX\ReaderSkipConfiguration;
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
function xlsxreaderGetRecords($afile,$params=array()){
	$reader_configuration = new ReaderConfiguration();
	$reader_configuration->setSkipEmptyRows(ReaderSkipConfiguration::SKIP_EMPTY);
	// NOTE: TODO: There may be a way to re-format the weird dates in the Russian XLSX file directly in xlsx-reader, but it was poorly documented and I couldn't figure it out. The following was my attemt at using the various functions and properties of those classes.
	/*
	$reader_configuration->setReturnUnformatted(true); // Use this when providing your own datetime format
	$reader_configuration->setReturnDateTimeObjects(false);
	// Set the custom datetime format used by the file so xls-reader knows how to interpret it
	$reader_configuration->setCustomFormats(array(
		20 => 'dd.mm.yyyy G:i:s',
	));
	$reader_configuration->setForceDateTimeFormat('m-d-Y H:i:s'); // Output MySQL timestamp format
	*/
	$reader = new Reader($reader_configuration);
	$reader->open($afile);
	// Read specified rows from specified sheets into recordset array
	$recs = [];
	$read_sheets = isset($params['sheets']) ? $params['sheets'] : 0;
	if (!is_array($read_sheets)) {
		$read_sheets = [$read_sheets];
	}
	$sheets = $reader->getSheets();
	foreach ($read_sheets as $index) {
		// Make sure sheet index is an integer
		$index = intval($index);
	  	$reader->changeSheet($index);
		$header = null;
		$sheet_recs = [];
	  	foreach ($reader as $row_number => $row) {
			// uncommonCronLog("Reading row {$row_number}");
			// Skip row if less than start_row parameter
			if (isset($params['start_row']) && $row_number < intval($params['start_row'])) continue;
			// Stop reading rows if greater than end_row
			if (isset($params['end_row']) && $row_number >= intval($params['end_row'])) break;
			// Stop reading rows if greater than num_rows
			if (isset($params['num_rows']) && $row_number >= intval($params['start_row'])+intval($params['num_rows'])) break;
			// Get header row
			// If we spec a header row and this is the first row (natural or start_row) of the sheet
			if (
				isset($params['has_header']) && $params['has_header']
				&& (
					$row_number == 1
					|| (
						isset($params['start_row']) &&
						$row_number == intval($params['start_row'])
					)
				)
			) {
				$header = $row;
				// TODO: Parse header values to be lowercase letters or digits only with only underscores; dump all special characters except dash; replace dash with underscore
				$header = xlsxreaderFormatFieldNames($header);
				// uncommonCronLog("Header found: \$header after formatting = \n".print_r($header, true));
				continue;
			}
			// Add row to records
 			if (!empty($header)) {
				// Dump any array elements with empty keys
				$row = array_combine($header, $row);
				$row = array_filter($row, function($k) {return $k != '';}, ARRAY_FILTER_USE_KEY);


				$sheet_recs[] = $row;
			}
			else {
				$sheet_recs[] = $row;
			}
	  }
	$recs = array_merge($recs, $sheet_recs);
	}
	$reader->close();
	return $recs;
}
function xlsxreaderFormatFieldNames($fields = []) {
	$fs = $fields;
	foreach ($fs as &$f) {
		$f_formatted = $f;
		$f_formatted = trim($f_formatted);
		// NOTE: The following may require you to set a locale if you expect field names in a specific language/script
		setlocale(LC_ALL, 'en_US');
		$f_formatted = iconv('UTF-8', 'ASCII//TRANSLIT', $f_formatted); // Convert from UTF-8 to ASCII with transliteration to replace non-ASCII characters with their nearest representation in ASCII
		$f_formatted = strtolower($f_formatted);
		$f_formatted = preg_replace('/\?/', 'X', $f_formatted); // Replace any non-transliteratable characters represented by "?" with "X" which should be noticable since we're lowercasing everything else
		$f_formatted = preg_replace('/[\s-]/', '_', $f_formatted); // Replace whitespace and dashes with underscores
		$f_formatted = preg_replace('/[^A-Za-z0-9$_]/', '', $f_formatted); // Remove anything that's not a Unicode letter or numeric digit or underscore
		// Make sure after the transformation that this name doesn't collide with another existing field name
		$i = null;
		while(!empty($f_formatted) && in_array($f_formatted.(empty($i) ? '' : '_').strval($i), $fs)) {
			$i = intval($i)+1;
		}
		$f = $f_formatted.(empty($i) ? '' : '_').strval($i);
	}
	return $fs;
}
?>