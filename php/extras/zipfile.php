<?php
/* References:
	http://stackoverflow.com/questions/14712925/php-zlib-how-to-dynamically-create-an-in-memory-zip-files-from-string-variables
*/
//load the common functions library
$progpath=dirname(__FILE__);
include_once("{$progpath}/zipfile/CreateZipFile.inc.php");
//---------- begin function zipCreate ----------
/**
* @describe creates a zip file and returns the name
* @param files array - array of files to include in the zip file
* @param zipname string - name of the zipfile - defaults to zipfile.zip
* @return boolean
* @usage
*	<?php
*	$files=array('/var/www/temp/file1.php','/var/www/temp/file2.txt','/var/www/temp/file3.png');
*	$bool=zipCreate($files,'myfiles.zip');
*	?>
*/
function zipCreate($files=array(),$zipname='zipfile.zip'){
	$zip = new CreateZipFile;
	foreach($files as $file){
		$zip->addFile(getFileContents($file), getFileName($file));
	}
	$fd=fopen($zipname, "wb");
	$out=fwrite($fd,$zip->getZippedfile());
	fclose($fd);
	return true;
}
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
			$size=zip_entry_filesize($zip_entry);
			if($size==0){
				$apath="{$newpath}/{$entryname}";
				if(!is_dir($apath)){buildDir($apath);}
				continue;
			}
			else{
				$entrydir=getFilePath($entryname);
				$apath = "{$newpath}/{$entrydir}";
				$filename=getFileName($entryname);
				$afile = "{$apath}/{$filename}";
			}
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
                    echo "Failed to write<br>Entry Name: {$entryname}<br />Entry Dir: {$entrydir}<br />Apath: {$apath}<br />Afile:{$afile}<br />Size:{$size}<br /> <br />".PHP_EOL;exit;
                }
                zip_entry_close($zip_entry);
            }
        }
        zip_close($zip);
    }
    return $rtn;
}
//---------- begin function zipPushFile ----------
/**
* @describe pushes a file within a zipfile
* @param zipfile string - path and zipfile
* @param filename string - filename inside of zip file to push
* @usage
*	<?php
*	zipPushFile('/var/www/temp/myfiles.zip','sample.png');
*	?>
*/
function zipPushFile($zip_file, $file_name) {
	if (file_exists($zip_file)) {
		$zip = zip_open($zip_file);
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				if (zip_entry_name($zip_entry) == $file_name) {
					$ctype=getFileContentType($file_name);
					$size=zip_entry_filesize($zip_entry);
					header("Content-Type: {$ctype}");
					header("Content-length: {$size}");
					echo zip_entry_read($zip_entry, $size);
					zip_entry_close($zip_entry);
					exit;
				}
			}
		}
		zip_close($zip);
		echo "zipPushFile Error: {$file_name} does not exist in {$zip_file}".PHP_EOL;
	}
	else{
		echo "zipPushFile Error: {$zip_file} does not exist".PHP_EOL;
	}
	exit;
}
//---------- begin function zipGetFileContents ----------
/**
* @describe return the contents of a file within a zipfile
* @param zipfile string - path and zipfile
* @param filename string - filename inside of zip file to push
* @return mixed
* @usage
*	<?=zipGetFileContents('/var/www/temp/myfiles.zip','description.txt');?>
*/
function zipGetFileContents($zip_file, $file_name) {
	$content='';
	if (file_exists($zip_file)) {
		$zip = zip_open($zip_file);
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				if (zip_entry_name($zip_entry) == $file_name) {
					$ctype=getFileContentType($file_name);
					$size=zip_entry_filesize($zip_entry);
					$content=zip_entry_read($zip_entry, $size);
					zip_entry_close($zip_entry);
					break;
				}
			}
		}
		zip_close($zip);
	}
	return $content;
}

//---------- begin function zipGetFileThumbnail ----------
/**
* @describe return the first image within a zipfile as a thumbnail
* @param zipfile string - path and zipfile
* @param filename string - filename inside of zip file to push
* @return raw
* @usage
*	<?=zipGetFileThumbnail('/var/www/temp/myfiles.zip');?>
*/
function zipGetFileThumbnail($zip_file) {
	$content='';
	if (file_exists($zip_file)) {
		$zip = zip_open($zip_file);
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				$name=zip_entry_name($zip_entry);
				if (preg_match('/\.(jpg|png|jpeg|gif)$/i',$name)) {
					$ctype=getFileContentType($file_name);
					$size=zip_entry_filesize($zip_entry);
					$content=zip_entry_read($zip_entry, $size);
					zip_entry_close($zip_entry);
					break;
				}
			}
		}
		zip_close($zip);
	}
	header('Content-Description: File Transfer');
	header("Content-Type: {$ctype}");
    header('Content-Disposition: attachment; filename='.basename($name));
    header("Accept-Ranges: bytes");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header('ETag: ' . md5(time()));
	//Note: caching on https will make it so it will fail in IE
    if (isset($_SERVER['HTTPS'])) {
		header('Pragma: ');
		header('Cache-Control: ');
		}
    else{
    	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    	header('Pragma: public');
		}
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
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
            $file=preg_replace('/\/+/',$slash,$file);
            $file=preg_replace('/\\+/',$slash,$file);
            $list[]=$file;
        }
        zip_close($zip);
    }
    return $list;
}
?>
