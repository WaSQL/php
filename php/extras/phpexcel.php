<?php
/*
	PDF2text wrappers
*/
$progpath=dirname(__FILE__);
if(!is_file("{$progpath}/phpexcel/PHPExcel.php")){
	echo 'PHPExcel Extra error: - you must first download PHPExcel from https://github.com/PHPOffice/PHPExcel and place the PHPExcel.php and the PHPExcel folder in the phpexcel folder under extras.';
	exit;
}
include_once("{$progpath}/phpexcel/PHPExcel.php");
include_once "{$progpath}/phpexcel/PHPExcel/Writer/Excel2007.php";

//---------- begin function phpexcelArrays2xlsx--------------------------------------
/**
* @describe converts a recs array to xlsx.
* @param recs array
* @param options array
*	[-name] string name of the file - defaults to file.xlsx
*	[-autosize] boolean autosize columns - defaults to true
*	[-header_color] string background color of header row - defaults to #d7d7e1
*	[-header_bold] boolean bold header row - defaults to true
* @return binary excel xlsx file
*/
function phpexcelArrays2xlsx($recs,$params=array()){
	if(!isset($params['-name'])){$params['-name']='file.xlsx';}
	if(!isset($params['-autosize'])){$params['-autosize']=true;}
	if(!isset($params['-header'])){$params['-header']=true;}
	if(!isset($params['-header_color'])){$params['-header_color']='#d7d7e1';}
	$params['-header_color']=preg_replace('/^\#/','',$params['-header_color']);
	if(strlen($params['-header_color'])==6){$params['-header_color']='FF'.$params['-header_color'];}
	if(!isset($params['-header_bold'])){$params['-header_bold']=true;}
	$objPHPExcel = new PHPExcel();
	$objPHPExcel->setActiveSheetIndex(0);
	//the first row is the headers
	if($params['-header']){
		$cols=array_keys($recs[0]);
		$objPHPExcel->getActiveSheet()->fromArray($cols, NULL, 'A1');
	}
	//get highest data row
	$row=$objPHPExcel->getActiveSheet()->getHighestDataRow()+1;
	foreach($recs as $rec){
    	$vals=array_values($rec);
    	$objPHPExcel->getActiveSheet()->fromArray($vals, NULL, "A{$row}");
    	$row++;
	}
	//get highest data column
	$maxcol=$objPHPExcel->getActiveSheet()->getHighestDataColumn();
	//autosize the columns that have data?
	if($params['-autosize']){
		foreach (range('A', $maxcol) as $col) {
	        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
	    }
	}
    if(strlen($params['-header_color'])){
	    //set first row background color
	    $objPHPExcel
			->getActiveSheet()
	    	->getStyle("A1:{$maxcol}1")
	    	->getFill()
	    	->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
	    	->getStartColor()
	    	->setARGB($params['-header_color']);
	}
    if($params['-header_bold']){
    	//bold first row
    	$objPHPExcel->getActiveSheet()->getStyle("A1:{$maxcol}1")->getFont()->setBold(true);
	}
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	// set headers
	header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$params['-name'].'"');
	header('Cache-Control: max-age=0');
	// Write file to the browser
	$objWriter->save('php://output');
	exit;
}

