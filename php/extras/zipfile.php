<?php
/* References:
	http://stackoverflow.com/questions/14712925/php-zlib-how-to-dynamically-create-an-in-memory-zip-files-from-string-variables
*/
//load the common functions library
$progpath=dirname(__FILE__);
//include_once("{$progpath}/zipfile/CreateZipFile.inc.php");
//---------- begin function zipCreate ----------
/**
* @describe creates a zip file and returns the name
* @param files array - array of files to include in the zip file
* @param zipname string - name of the zipfile - defaults to zipfile.zip
* @return boolean
* @usage
*	$files=array('/var/www/temp/file1.php','/var/www/temp/file2.txt','/var/www/temp/file3.png');
*	$bool=zipCreate($files,'myfiles.zip');
*/
function zipCreate($files=array(),$zipfile='zipfile.zip'){
	if(file_exists($zipfile)){unlink($zipfile);}
	$zip = new ZipArchive;
	if ($zip->open($zipfile,ZIPARCHIVE::CREATE) == TRUE) {
		foreach($files as $file){
			//The 2nd parameter in addFile is the name of the file inside the zip
			$filename=getFileName($file);
			$zip->addFile($file,$filename);
		}
		$zip->close();
		return true;
	}
	return false;
}
//---------- begin function zipPushData ----------
/**
* @describe creates and pushes a zip file to the browser
* @param files array - array of files to include in the zip file
* @param zipname string - name of the zipfile - defaults to zipfile.zip
* @return file - pushes zipfile to browser and exits
* @usage
*	$files=array('/var/www/temp/file1.php','/var/www/temp/file2.txt','/var/www/temp/file3.png');
*	zipPushData($files,'myfiles.zip');
*/
function zipPushData($files=array(),$zipfile='zipfile.zip'){
	$zip = new ZipArchive;
	if ($zip->open($zipfile) == TRUE) {
		foreach($files as $file){
			//The 2nd parameter in addFile is the name of the file inside the zip
			$filename=getFileName($file);
			$zip->addFile($file,$filename);
		}
		$zip->close();
		return true;
	}
	return false;
}
//---------- begin function zipExtract ----------
/**
* @describe
*	extracts $zipfile to new directory with the same name as the file in the same path unless $newpath is specified
* @param zipfile string - path and zipfile to extract
* @param newpath string - name of new path.  default is to create a new dir with the same name as the zipfile
* @return array - an array of files written (full path)
* @usage
*	$files=zipExtract('/var/www/temp/myfiles.zip','myfiles');
*/
function zipExtract( $zipfile,$newpath=''){
	$zippath=getFilePath($zipfile);
    $zipname=getFileName($zipfile,1);
    $slash=isWindows()?"\\":'/';
    $rtn=array();
    //default new path to same path zipfile is located in if not specified
    if(!strlen($newpath)){$newpath="{$zippath}/{$zipname}";}
	$zip = new ZipArchive;
	$files=array();
	if ($zip->open($zipfile) == TRUE) {
 		for ($i = 0; $i < $zip->numFiles; $i++) {
     		$file= $zip->statIndex($i);
     		if($file['size']==0){
				$apath="{$newpath}/{$file['name']}";
				if(!is_dir($apath)){buildDir($apath);}
				continue;
			}
			else{
				$entrydir=getFilePath($file['name']);
				$apath = "{$newpath}/{$entrydir}";
				$filename=getFileName($file['name']);
				$afile = "{$apath}/{$filename}";
			}
            $afile=preg_replace('/\/+/',$slash,$afile);
            $afile=preg_replace('/\\+/',$slash,$afile);
            //build the directory if it does not exist
            if(!is_dir($apath)){buildDir($apath);}
            //open the file and write it
			if(is_file($afile)){unlink($afile);}
            if ($fd = @fopen($afile, 'w+')){
            	fwrite($fd, $zip->getFromIndex($i));
                fclose($fd);
                if(is_file($afile)){$files[]=$afile;}
            }
        }
        @$zip->close();
	}
	return $files;
}
//---------- begin function zipPushFile ----------
/**
* @describe pushes a file within a zipfile
* @param zipfile string - path and zipfile
* @param filename string - filename inside of zip file to push
* @usage
*	zipPushFile('/var/www/temp/myfiles.zip','sample.png');
*/
function zipPushFile($zipfile, $filename) {
	$content='';
	$zip = new ZipArchive;
	if ($zip->open($zipfile) == TRUE) {
 		for ($i = 0; $i < $zip->numFiles; $i++) {
     		$file= $zip->statIndex($i);
     		if ($file['name']==$filename) {
     			$ctype=getFileContentType($file['name']);
				$size=$file['size'];
				header("Content-Type: {$ctype}");
				header("Content-length: {$size}");
				echo $zip->getFromIndex($i);
				$zip->close();
				exit;
     		}
 		}
 		@$zip->close();
 	}
	return $content;
}
//---------- begin function zipGetFileContents ----------
/**
* @describe return the contents of a file within a zipfile
* @param zipfile string - path and zipfile
* @param filename string - filename inside of zip file to push
* @return mixed
* @usage
*	zipGetFileContents('/var/www/temp/myfiles.zip','description.txt');
*/
function zipGetFileContents($zipfile, $filename) {
	$content='';
	$zip = new ZipArchive;
	if ($zip->open($zipfile) == TRUE) {
 		for ($i = 0; $i < $zip->numFiles; $i++) {
     		$file= $zip->statIndex($i);
     		if ($file['name']==$filename) {
				$content=$zip->getFromIndex($i);
     		}
 		}
 		@$zip->close();
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
*	zipGetFileThumbnail('/var/www/temp/myfiles.zip');
*/
function zipGetFileThumbnail($zipfile) {
	$content='';
	$zip = new ZipArchive;
	if ($zip->open($zipfile) == TRUE) {
 		for ($i = 0; $i < $zip->numFiles; $i++) {
     		$file= $zip->statIndex($i);
     		if (preg_match('/\.(jpg|png|jpeg|gif|svg)$/i',$file['name'])) {
     			$ctype=getFileContentType($file['name']);
				$size=$file['size'];
				$content=$zip->getFromIndex($i);
     		}
 		}
 		@$zip->close();
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
*	$files=zipListFiles('/var/www/temp/myfiles.zip');
*/
function zipListFiles( $zipfile){
	$slash=isWindows()?"\\":'/';
	$zipfile=preg_replace('/\/+/',$slash,$zipfile);
    $zipfile=preg_replace('/\\+/',$slash,$zipfile);
    echo "zipfile:{$zipfile}<br>".PHP_EOL;
	if(!file_exists($zipfile)){return false;}
	$zip = new ZipArchive;
	$files=array();
	if ($zip->open($zipfile,ZipArchive::RDONLY) == true) {
		echo "zip:".printValue($zip);
		$filecnt=$zip->numFiles;
		echo "Filecnt:{$filecnt}<br>".PHP_EOL;
 		for ($i = 0; $i < $filecnt; $i++) {
     		$files[]= $zip->getNameIndex($i);
 		}
 		if($filecnt > 0){$zip->close();}
	}
	return $files;
}
?>
