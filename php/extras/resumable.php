<?php
/*
 * 	Documentation:  https://github.com/23/resumable.js/
 *
 * */
global $temp;
global $temp_logfile;
$progpath=dirname(__FILE__);
$phpdir=realpath(preg_replace('/extras$/i','',$progpath));
$temp=realpath("{$phpdir}/temp");
$temp_logfile="{$temp}/resumable.log";
//echo $temp_logfile;
//keep log file to 1MB in size or smaller
if(is_file($temp_logfile) && filesize($temp_logfile) > 1048576){
	file_put_contents($temp_logfile,'');
}
//check if request is GET and the requested chunk exists or not. this makes testChunks work
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(!(isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier'])!='')){
        $_GET['resumableIdentifier']='';
    }
    $temp_dir = "{$temp}/{$_GET['resumableIdentifier']}";
    if(!(isset($_GET['resumableFilename']) && trim($_GET['resumableFilename'])!='')){
        $_GET['resumableFilename']='';
    }
    if(!(isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber'])!='')){
        $_GET['resumableChunkNumber']='';
    }
    $chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
    if(is_file($chunk_file)){
         header("HTTP/1.0 200 Ok");
    }
    else {
		header("HTTP/1.0 404 Not Found");
    }
}

// loop through files and move the chunks to a temporarily created directory
if (!empty($_FILES)) foreach ($_FILES as $file) {
    // check the error status
    if ($file['error'] != 0) {
        resumableLog('error '.$file['error'].' in file '.$_POST['resumableFilename'].json_encode($file));
        continue;
    }
    // init the destination file (format <filename.ext>.part<#chunk>
    // the file is stored in a temporary directory
    if(isset($_POST['resumableIdentifier']) && trim($_POST['resumableIdentifier'])!=''){
		$temp_dir = "{$temp}/{$_POST['resumableIdentifier']}";
    }
    $dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];
    // create the temporary directory
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0777, true);
		resumableLog("making temp dir: {$temp_dir}");
    }

    // move the temporary file
	resumableLog("moving {$file['tmp_name']} to {$dest_file}");
    if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
        resumableLog('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
    } else {
		resumableLog("Success (move_uploaded_file) chunk {$_POST['resumableChunkNumber']} for file {$_POST['resumableFilename']} to {$dest_file} ");
		if(isset($_SERVER['HTTP_HOST'])){
			global $CONFIG;
			include_once("{$phpdir}/common.php");
			include_once("{$phpdir}/config.php");
			include_once("{$phpdir}/database.php");
			if(!isDBTable('resumable')){
				resumableLog("creating resumable table");
				$ok=createDBTable('resumable',array(
					'dirname'=>'varchar(255) NOT NULL UNIQUE',
					'filename'=>'varchar(255) NOT NULL',
					'chunkcount'=>'integer NOT NULL',
					'filesize'=>'integer NOT NULL',
					'processed'=>'tinyint(1) NOT NULL Default 0'
				));
			}
			$rec=getDBRecord(array(
				'-table'=>'resumable',
				'dirname'=>$temp_dir,
				'-fields'=>'_id'
			));
			if(!isset($rec['_id'])){
				$opts=array(
					'-table'=>'resumable',
					'dirname'=>$temp_dir,
					'filename'=>$_POST['resumableFilename'],
					'chunkcount'=>$_POST['resumableTotalChunks'],
					'filesize'=>$_POST['resumableTotalSize'],
					'processed'=>0
				);
				$ok=addDBRecord($opts);
				resumableLog("adding record".json_encode($opts).json_encode(array($ok)));
			}
		}
        // check if all the parts present, and create the final destination file
        createFileFromChunks($temp_dir, $_POST['resumableFilename'],$_POST['resumableChunkSize'], $_POST['resumableTotalSize'],$_POST['resumableTotalChunks']);
    }
}

/**
 *
 * Check if all the parts exist, and
 * gather all the parts of the file together
 * @param string $temp_dir - the temporary directory holding all the parts of the file
 * @param string $fileName - the original file name
 * @param string $chunkSize - each chunk size (in bytes)
 * @param string $totalSize - original file size (in bytes)
 */
function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize,$total_files) {
	global $temp;
    // count all the parts of this file
    $total_files_on_server_size = 0;
    $temp_total = 0;
    foreach(scandir($temp_dir) as $file) {
        $temp_total = $total_files_on_server_size;
        $tempfilesize = filesize($temp_dir.'/'.$file);
        $total_files_on_server_size = $temp_total + $tempfilesize;
    }
    // check that all the parts are present
    // If the Size of all the chunks on the server is equal to the size of the file uploaded.
    if ($total_files_on_server_size >= $totalSize) {
    // create the final destination file
        if (($fp = fopen("{$temp}/{$fileName}", 'w')) !== false) {
            for ($i=1; $i<=$total_files; $i++) {
                fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
                resumableLog('writing chunk '.$i);
            }
            fclose($fp);
        } else {
            resumableLog('cannot create the destination file');
            return false;
        }

        // rename the temporary directory (to avoid access from other
        // concurrent chunks uploads) and than delete it
        if (rename($temp_dir, $temp_dir.'_UNUSED')) {
            rrmdir($temp_dir.'_UNUSED');
        } else {
            rrmdir($temp_dir);
        }
		//mark image record ready to resize
		
    }

}
/**
 *
 * Logging operation - to a file (upload_log.txt) and to the stdout
 * @param string $str - the logging string
 */
function resumableLog($str) {
	global $temp;
	global $temp_logfile;
    // log to the output
    $log_str = date('d.m.Y').": {$str}".PHP_EOL;
    // log to file
    if (($fp = fopen($temp_logfile, 'a+')) !== false) {
        fputs($fp, $log_str);
        fclose($fp);
    }
    else{
		echo $log_str;
	}
}

/**
 *
 * Delete a directory RECURSIVELY
 * @param string $dir - directory path
 * @link http://php.net/manual/en/function.rmdir.php
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
exit;
