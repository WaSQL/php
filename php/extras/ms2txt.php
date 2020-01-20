<?PHP
/*
    functions to convert microsoft files (doc,docx,xlsx,pptx) to text
    $txt = ms2txtConvert("test.doc");
    $txt = ms2txtConvert("test.docx");
    $txt = ms2txtConvert("test.xlsx");
    $txt = ms2txtConvert("test.pptx");

    Reference: https://stackoverflow.com/questions/19503653/how-to-extract-text-from-word-file-doc-docx-xlsx-pptx-php
*/
function ms2txtConvert($afile) {
    if(!file_exists($afile)){
        return "Error: file does not exist. {$afile}";
    }
    $info=pathinfo($afile);
    $ext=strtolower($info['extension']);
    switch($ext){
        case 'doc':return ms2txtConvertDoc($afile);break;
        case 'docx':return ms2txtConvertDocx($afile);break;
        case 'xlsx':return ms2txtConvertXlsx($afile);break;
        case 'pptx':return ms2txtConvertPptx($afile);break;
        default:return "Invalid File Type - {$ext}";break;
    }        
}
function ms2txtConvertDoc($afile) {
    $fileHandle = fopen($afile, "r");
    $line = @fread($fileHandle, filesize($afile));   
    $lines = explode(chr(0x0D),$line);
    $outtext = "";
    foreach($lines as $thisline){
        $pos = strpos($thisline, chr(0x00));
        if (($pos !== FALSE)||(strlen($thisline)==0)){
        } 
        else {
            $outtext .= $thisline." ";
        }
    }
    $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
    return $outtext;
}
function ms2txtConvertDocx($afile){
    $striped_content = '';
    $content = '';
    $zip = zip_open($afile);
    if (!$zip || is_numeric($zip)){return false;}
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry) == FALSE){ continue;}
        if (zip_entry_name($zip_entry) != "word/document.xml"){ continue;}
        $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
        zip_entry_close($zip_entry);
    }
    zip_close($zip);
    $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $striped_content = strip_tags($content);
    return $striped_content;
}
function ms2txtConvertXlsx($afile){
    $xml_filename = "xl/sharedStrings.xml"; //content file name
    $zip_handle = new ZipArchive;
    $output_text = "";
    if(true === $zip_handle->open($afile)){
        if(($xml_index = $zip_handle->locateName($xml_filename)) !== false){
            $xml_datas = $zip_handle->getFromIndex($xml_index);
            $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            $output_text = strip_tags($xml_handle->saveXML());
        }
        else{
            $output_text .="";
        }
        $zip_handle->close();
    }
    else{
        $output_text .="";
    }
    return $output_text;
}
function ms2txtConvertPptx($afile){
    $zip_handle = new ZipArchive;
    $output_text = "";
    if(true === $zip_handle->open($afile)){
        $slide_number = 1; //loop through slide files
        while(($xml_index = $zip_handle->locateName("ppt/slides/slide".$slide_number.".xml")) !== false){
            $xml_datas = $zip_handle->getFromIndex($xml_index);
            $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            $output_text .= strip_tags($xml_handle->saveXML());
            $slide_number++;
        }
        if($slide_number == 1){
            $output_text .="";
        }
        $zip_handle->close();
    }
    else{
        $output_text .="";
    }
    return $output_text;
}
