<?php
/*
	PDF2text wrappers
*/
$progpath=dirname(__FILE__);
error_reporting(E_ALL & ~E_NOTICE);
include_once("{$progpath}/pdf/class.pdf2text.php");
include_once("{$progpath}/zipfile.php");
$phppath=preg_replace('/extras*+/i','',$progpath);
$phppath=str_replace("\\","/",$phppath);
include_once("{$phppath}/Array2XML.php");

//---------- begin function convert2Txt--------------------------------------
/**
* @describe extracts text from a various file formats. Supports pdf,doc,rtf,pptx,docx,xlsx,odp,ods,odt,htm,html,and text formats.
*	If the file is an image or an unknown binary file,  it will extract and return the exif data.
*	If the file is a zip file it will return a list of files in the zip file
* @param file string
*	The full file name and path
* @return txt text
*/
function convert2Txt($file){
	$ext=strtolower(getFileExtension($file));
	switch($ext){
    	case 'pdf':return convertPDF2Txt($file);break;
    	case 'doc':
		case 'rtf':
			return convertDoc2Txt($file);
		break;
		case 'pptx':
			return convertPptx2Txt($file);
		break;
    	case 'docx':
    		return convertReadZippedXML($file,'word/document.xml');
		break;
    	case 'xlsx':
    		return convertReadZippedXML($file,'xl/sharedStrings.xml');
		break;
		case 'odp':
    	case 'ods':
    	case 'odt':
			//open office file
    		return convertReadZippedXML($file,'content.xml');
		break;
		case 'htm':
		case 'html':
		case 'txt':
			return trim(removeHtml(getFileContents($file)));
		break;
		case 'zip':
			$lines=zipListFiles($file);
			return implode("\n",$lines);
		break;
    	default:
    		$mimetype=getFileMimeType($file);
    		if(stringContains($mimetype,'text')){
            	return trim(removeHtml(getFileContents($file)));
			}
			//probably a binary file - return any exif data
    		$exif=getFileExif($file);
    		$xml = Array2XML::createXML('root_node_name', $exif);
        	$txt=$xml->saveXML();
    		return trim(removeHtml($txt));
    	break;
	}
}

//---------- begin function convertPDF2Txt--------------------------------------
/**
* @describe extracts text from a PDF file
* @param file string
*	The full file name and path
* @return txt text
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertPDF2Txt($file){
    $a = new PDF2Text();
    $a->setFilename($file);
    $a->decodePDF();
    return $a->output();
}
//---------- begin function convertDoc2Txt--------------------------------------
/**
* @describe extracts text from a Microsoft Doc file
* @param file string
*	The full file name and path
* @return txt text
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertDoc2Txt($file) {
    if($fh = fopen($file, "r")){
    	$headers = fread($fh, 0xA00);
    	$n1 = ( ord($headers[0x21C]) - 1 );
    	$n2 = ( ( ord($headers[0x21D]) - 8 ) * 256 );
    	$n3 = ( ( ord($headers[0x21E]) * 256 ) * 256 );
    	$n4 = ( ( ( ord($headers[0x21F]) * 256 ) * 256 ) * 256 );
    	$textLength = ($n1 + $n2 + $n3 + $n4);
    	$extracted_plaintext = fread($fh, $textLength);
    	$extracted_plaintext = preg_replace("/[^a-z0-9\s\,\.\-\n\r\t\@\/\_\(\)\!]/i","",$extracted_plaintext);
    	$extracted_plaintext = preg_replace("/\t+/"," ",$extracted_plaintext);
    	$lines=preg_split('/[\r\n]+/',trim($extracted_plaintext));
    	$txt='';
    	foreach($lines as $line){
			$len=strlen(trim($line));
        	if($len < 2){continue;}
        	//remove junk words - the longest word in English is 30 letters
        	$words=preg_split('/\ /',$line);
        	foreach($words as $i=>$word){
				if(strlen($word) > 30){unset($words[$i]);}
			}
        	$line=implode(' ',$words);
        	$txt .= $line."\n";
		}
		return $txt;
	}
    return '';
}
//---------- begin function convertDocx2Txt--------------------------------------
/**
* @describe extracts text from a Microsoft docx file
* @param file string
*	The full file name and path
* @return txt text
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertDocx2Txt($file){
    $striped_content = '';
    $content = '';
    $zip = zip_open($file);
    if (!$zip || is_numeric($zip)){return false;}
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry) == FALSE){
			continue;
		}
        if(zip_entry_name($zip_entry) != "word/document.xml"){
			continue;
		}
        $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
        zip_entry_close($zip_entry);
    }
    zip_close($zip);
    $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $striped_content = strip_tags($content);
    return $striped_content;
}

//---------- begin function convertXlsx2Txt--------------------------------------
/**
* @describe extracts text from a Microsoft xlsx file
* @param file string
*	The full file name and path
* @return txt text
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertXlsx2Txt($file){
    $striped_content = '';
    $content = '';
    $zip = zip_open($file);
    if (!$zip || is_numeric($zip)){return false;}
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry) == FALSE){
			continue;
		}
        if(zip_entry_name($zip_entry) != "xl/sharedStrings.xml"){
			continue;
		}
        $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
        zip_entry_close($zip_entry);
    }
    zip_close($zip);
    $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $striped_content = strip_tags($content);
    return $striped_content;
}
/**
* Read content from zipped office XML file
*
* @param string $archiveFile
* @param string $dataFile
* @return string (empty if no content found)
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertReadZippedXML($archiveFile, $dataFile) {
	// Create new ZIP archive
    $zip = new ZipArchive();
    // Open received archive file
    if (true === $zip->open($archiveFile)) {
    	// If done, search for the data file in the archive
    	if (($index = $zip->locateName($dataFile)) !== false) {
    		// If found, read it to the string
    		$data = $zip->getFromIndex($index);
    		// Close archive file
    		$zip->close();
    		// Load XML from a string
    		$doc = new DOMDocument();
    		$doc->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
    		$content = $doc->saveXML();
    		// Insert whitespace to prevent words beeing concatenated when stripping tags
    		$content = str_replace('>', '> ', $content);
    		//collapse multiple spaces into one space
    		$content=preg_replace('/\ +/',' ',$content);
    		// Return data without XML formatting tags
    		return trim(strip_tags($content));
    	}
    	$zip->close();
    }
    return '';
}
//---------- begin function convertPptx2txt--------------------------------------
/**
* @describe extracts text from a Microsoft xlsx file
* @param file string
*	The full file name and path
* @return txt text
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function convertPptx2txt($filename){
	// Create new ZIP archive
    $zip = new ZipArchive;
    $content = '';
	// Open received archive file
    if (true === $zip->open($filename)) {
    	// loop through all slide#.xml files
    	$slide = 1;
    	while (($index = $zip->locateName('ppt/slides/slide' . $slide . '.xml')) !== false) {
    		$data = $zip->getFromIndex($index);
    		$doc = new DOMDocument();
    		$doc->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
    		$xml = $doc->saveXML();
    		// Insert whitespace to prevent words beeing concatenated when stripping tags
    		$xml = str_replace('>', '> ', $xml);
    		// append data without XML formatting tags
    		$content .= strip_tags($xml);
    		$slide++;
    	}
    	$zip->close();
    }
    $content=preg_replace('/\ +/',' ',$content);
	return trim($content);
}