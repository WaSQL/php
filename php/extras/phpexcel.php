<?php
/*
	PDF2text wrappers
*/

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
	$progpath=dirname(__FILE__);
	if(!is_file("{$progpath}/phpexcel/PHPExcel.php")){
		echo 'PHPExcel Extra error: - you must first download PHPExcel from https://github.com/PHPOffice/PHPExcel and place the PHPExcel.php and the PHPExcel folder in the phpexcel folder under extras.';
		exit;
	}
	include_once("{$progpath}/phpexcel/PHPExcel.php");
	include_once "{$progpath}/phpexcel/PHPExcel/Writer/Excel2007.php";
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
/**
 * Set $throttle to limit number of rows converted;
 * Leave blank to process entire file.
 * In demo $throttle declared in index.php
  * Set $cleanup to 1 for debugging or to leave unpacked files on server;
 * Set to 0 or "" to delete unpacked files in production environment
  * Set $unpack to 1 if files are already unpacked files on server;
 * Set to 0 or "" to unpack files in production environment
 */
function phpexcelXlsx2csv($file,$throttle='',$cleanup='',$unpack=0){
	$progpath=dirname(__FILE__);
	if(!is_file("{$progpath}/phpexcel/pclzip.lib.php")){
		echo 'PHPExcel Extra error: - pclzip.lib.php is missing';
		exit;
	}
	if(!is_file($file)){return 'NO FILE';}
	$binpath="{$progpath}/phpexcel";
	if(!is_dir($binpath)){mkdir($binpath, 0770);};
 	$newcsvfile  = "{$binpath}/".getFileName($file,1).'.csv';
	require_once "{$progpath}/phpexcel/pclzip.lib.php";
 	$archive = new PclZip($file);
 	$list = $archive->extract(PCLZIP_OPT_PATH, "bin");
	$strings = array();
	$dir = getcwd();
	$filename = $dir."\bin\xl\sharedstrings.xml";
	$z = new XMLReader;
	$z->open($filename);
	$doc = new DOMDocument;
	$csvfile = fopen($newcsvfile,"w");
	while ($z->read() && $z->name !== 'si'){}
	ob_start();
	while ($z->name === 'si'){
    	// either one should work
    	$node = new SimpleXMLElement($z->readOuterXML());
   		// $node = simplexml_import_dom($doc->importNode($z->expand(), true));
    	$result = xmlObjToArr($node);
		$count = count($result['text']) ;
   		if(isset($result['children']['t'][0]['text'])){
   			$val = $result['children']['t'][0]['text'];
  			$strings[]=$val;
    	}
    	$z->next('si');
    	$result=NULL;
    }
	ob_end_flush();
	$z->close($filename);
	$dir = getcwd();
	$filename = $dir."\bin\xl\worksheets\sheet1.xml";
	$z = new XMLReader;
	$z->open($filename);
	$doc = new DOMDocument;
	$rowCount="0";
	$doc = new DOMDocument;
	$sheet = array();
	$nums = array("0","1","2","3","4","5","6","7","8","9");
	while ($z->read() && $z->name !== 'row'){}
	ob_start();
	while ($z->name === 'row'){
    	$thisrow=array();
		$node = new SimpleXMLElement($z->readOuterXML());
		$result = xmlObjToArr($node);
		$cells = $result['children']['c'];
		$rowNo = $result['attributes']['r'];
		$colAlpha = "A";
		foreach($cells as $cell){
			if(array_key_exists('v',$cell['children'])){
				$cellno = str_replace($nums,"",$cell['attributes']['r']);
				for($col = $colAlpha; $col != $cellno; $col++) {
	 				$thisrow[]=" ";
	 				$colAlpha++;
	   			}
		  		if(array_key_exists('t',$cell['attributes'])&&$cell['attributes']['t']='s'){
		    		$val = $cell['children']['v'][0]['text'];
		    		$string = $strings[$val] ;
		    		$thisrow[]=$string;
		      	}
		    	else {
		    		$thisrow[]=$cell['children']['v'][0]['text'];
		      	}
		    }
			else{$thisrow[]="";};
			$colAlpha++;
		}
		$rowLength=count($thisrow);
		$rowCount++;
		$emptyRow=array();
		while($rowCount<$rowNo){
	  		for($c=0;$c<$rowLength;$c++) {
	    		$emptyRow[]="";
	  		}
			if(!empty($emptyRow)){
	    		my_fputcsv($csvfile,$emptyRow);
	    	};
	    $rowCount++;
	  	}
		my_fputcsv($csvfile,$thisrow);
		if($rowCount<$throttle||$throttle==""||$throttle=="0"){
			$z->next('row');
	    }
	    else{break;}
		$result=NULL; 
	}
	$z->close($filename);
	ob_end_flush();
  	cleanUp("bin/");
  	return $filename
}
/**
 * convert xml objects to array
 * function from http://php.net/manual/pt_BR/book.simplexml.php
 * as posted by xaviered at gmail dot com 17-May-2012 07:00
 * NOTE: return array() ('name'=>$name) commented out; not needed to parse xlsx
 */
function phpexcelXmlObjToArr($obj) {
	$namespace = $obj->getDocNamespaces(true);
    $namespace[NULL] = NULL;
    $children = array();
    $attributes = array();
    $name = strtolower((string)$obj->getName());
    $text = trim((string)$obj);
    if( strlen($text) <= 0 ) {
    	$text = NULL;
    }
    // get info for all namespaces
    if(is_object($obj)) {
    	foreach( $namespace as $ns=>$nsUrl ) {
    		// atributes
            $objAttributes = $obj->attributes($ns, true);
            foreach( $objAttributes as $attributeName => $attributeValue ) {
            	$attribName = strtolower(trim((string)$attributeName));
                $attribVal = trim((string)$attributeValue);
                if (!empty($ns)) {
                	$attribName = $ns . ':' . $attribName;
                }
                $attributes[$attribName] = $attribVal;
            }
            // children
            $objChildren = $obj->children($ns, true);
            foreach( $objChildren as $childName=>$child ) {
                $childName = strtolower((string)$childName);
                if( !empty($ns) ) {
                    $childName = $ns.':'.$childName;
                }
                $children[$childName][] = xmlObjToArr($child);
            }
        }
    }
    return array(
        // name not needed for xlsx
        // 'name'=>$name,
        'text'=>$text,
        'attributes'=>$attributes,
        'children'=>$children
	);
}
/**
 * write array to csv file
 * enhanced fputcsv found at http://php.net/manual/en/function.fputcsv.php
 * posted by Hiroto Kagotani 28-Apr-2012 03:13
 * used in lieu of native PHP fputcsv() resolves PHP backslash doublequote bug
 * !!!!!! To resolve issues with escaped characters breaking converted CSV, try this:
 * Kagotani: "It is compatible to fputcsv() except for the additional 5th argument $escape, 
 * which has the same meaning as that of fgetcsv().  
 * If you set it to '"' (double quote), every double quote is escaped by itself."
 */
function phpexcelFputcsv($handle, $fields, $delimiter = ',', $enclosure = '"', $escape = '\\') {
	$first = 1;
	foreach ($fields as $field) {
    	if ($first == 0){ fwrite($handle, ",");}
		$f = str_replace($enclosure, $enclosure.$enclosure, $field);
    	if ($enclosure != $escape) {
      		$f = str_replace($escape.$enclosure, $escape, $f);
    	}
    	if (strpbrk($f, " \t\n\r".$delimiter.$enclosure.$escape) || strchr($f, "\000")) {
      		fwrite($handle, $enclosure.$f.$enclosure);
    	} 
		else {
      		fwrite($handle, $f);
    	}
    	$first = 0;
	}
  	fwrite($handle, "\n");
}
/**
 * Delete unpacked files from server
 */ 
function phpexcelCleanUp($dir) {
	$tempdir = opendir($dir);
    while(false !== ($file = readdir($tempdir))) {
    	if($file != "." && $file != "..") {
        	if(is_dir($dir.$file)) {
            	chdir('.');
            	cleanUp($dir.$file.'/');
            	rmdir($dir.$file);
            }
            else{
                unlink($dir.$file);
			}
        }
    }
	closedir($tempdir);
}
