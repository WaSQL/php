<?php
/*
	PDF2text wrappers
*/
$progpath=dirname(__FILE__);
error_reporting(E_ALL & ~E_NOTICE);
include_once("{$progpath}/pdf/class.pdf2text.php");
//---------- begin function pdfGetText--------------------------------------
/**
* @describe extracts text from a PDF file
* @param file string
*	The full file name and path
* @return txt text
*/
function pdfGetText($file){
    $a = new PDF2Text();
    $a->setFilename($file);
    $a->decodePDF();
    return $a->output();
}
