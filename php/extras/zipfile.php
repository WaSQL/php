<?php
/* References:
	http://stackoverflow.com/questions/14712925/php-zlib-how-to-dynamically-create-an-in-memory-zip-files-from-string-variables
*/
//load the common functions library
$progpath=dirname(__FILE__);
include_once("{$progpath}/zipfile/CreateZipFile.inc.php");
//---------- begin function zipPushData ----------
/**
* @describe creates and pushes a zip file to the browser
* @param files array - array of files to include in the zip file
* @param zipname string - name of the zipfile - defaults to zipfile.zip
* @return file - pushes zipfile to browser and exits
* @usage
*	<?php
*	$files=array('/var/www/temp/file1.php','/var/www/temp/file2.txt','/var/www/temp/file3.png');
*	zipPushData($files,'myfiles.zip');
*	?>
*/
function zipPushData($files=array(),$zipname='zipfile.zip'){
	$zip = new CreateZipFile;
	foreach($files as $filename=>$data){
		$zip->addFile($data, $filename);
	}
	//$zip->addFile('anyfiledata2', 'anyfolder/anyfilename.anyext');
	//$zip->addDirectory('anyemptydirwouldbecreatedinthiszipfile');
	header('Content-disposition: attachment; filename='.$zipname.'');
	header('Content-type: application/octetstream');
	echo $zip->getZippedfile();
	exit();
}
//---------- begin function zipExtract ----------
/**
* @describe 
*	extracts $zipfile to new directory with the same name as the file in the same path unless $newpath is specified
* @param zipfile string - path and zipfile to extract
* @param newpath string - name of new path.  default is to create a new dir with the same name as the zipfile
* @return array - an array of files written (full path)
* @usage
*	<?php
*	$files=zipExtract('/var/www/temp/myfiles.zip','myfiles');
*	?>
*/
function zipExtract( $zipfile,$newpath=''){
	//info:
	//info:returns an array of files written (full path)
    $zippath=getFilePath($zipfile);
    $zipname=getFileName($zipfile,1);
    $slash=isWindows()?"\\":'/';
    $rtn=array();
    //default new path to same path zipfile is located in if not specified
    if(!strlen($newpath)){$newpath="{$zippath}/{$zipname}";}
    $zip = zip_open($zipfile);
    if(is_resource($zip)){
		//loop through the files in the zipfile
        while ($zip_entry = zip_read($zip)){
			$entryname=zip_entry_name($zip_entry);
			$entrydir=getFilePath($entryname);
            $apath = "{$newpath}/{$entrydir}";
            $afile = "{$apath}/{$entryname}";
            $afile=preg_replace('/\/+/',$slash,$afile);
            $afile=preg_replace('/\\+/',$slash,$afile);
            //build the directory if it does not exist
            if(!is_dir($apath)){buildDir($apath);}
            //open the file and write it
            if (zip_entry_open($zip, $zip_entry, "r")){
				if(is_file($afile)){unlink($afile);}
                if ($fd = @fopen($afile, 'w+')){
                    fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                    fclose($fd);
                    if(is_file($afile)){$rtn[]=$afile;}
                }
                else {
                    // probably an empty directory
                }
                zip_entry_close($zip_entry);
            }
        }
        zip_close($zip);
    }
    return $rtn;
}
//---------- begin function zipListFiles ----------
/**
* @describe returns a list of files found in zip file
* @param zipfile string - path and zipfile to extract
* @return array - an array of files
* @usage
*	<?php
*	$files=zipListFiles('/var/www/temp/myfiles.zip');
*	?>
*/
function zipListFiles( $zipfile){
	//info:
	//info:returns an array of files written (full path)
    $zippath=getFilePath($zipfile);
    $zipname=getFileName($zipfile,1);
    $slash=isWindows()?"\\":'/';
    $rtn=array();
    //default new path to same path zipfile is located in if not specified
    if(!strlen($newpath)){$newpath="{$zippath}/{$zipname}";}
    $zip = zip_open($zipfile);
    $list=array();
    if(is_resource($zip)){
		//loop through the files in the zipfile
        while ($zip_entry = zip_read($zip)){
			$file=zip_entry_name($zip_entry);
            $file=preg_replace('/\/+/',$slash,$afile);
            $file=preg_replace('/\\+/',$slash,$afile);
            $list[]=$file;
        }
        zip_close($zip);
    }
    return $rtn;
}
?>