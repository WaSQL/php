<?php
/* References:
	FTP functions
*/
//---------- begin function ftpListFiles
/**
* @describe lists files on an FTP server
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	[-remote_dir] string - name of remote directory to list. Defaults to .
* @return array - array of file names
* @usage 
*	<?php
*	$remote_files=ftpListFiles(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp'
*	));
*	?>
*/
function ftpListFiles($params=array()){
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	//default
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	//connect
	$conn_id = ftp_connect($params['-server']);
	//login
	$login = ftp_login($conn_id, $params['-user'], $params['-pass']);
	// get list of files
	$files = ftp_nlist($conn_id, $params['-remote_dir']);
	//close
	ftp_close($conn_id);
	//return
	return $files;
	}
//---------- begin function ftpGetFile
/**
* @describe gets a single FTP file (remote_file) in remote_dir and places it in local_dir
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	-remote_file - name of remote file to get
*	-local_file - name of local file to save as
*	[-local_dir] - specify directory to place local file in
*	[-remote_dir] string - name of remote directory to list. Defaults to .
*	[-move_dir] string - path to move the remote file to after fetching it
*	[-delete] boolean - delete the remote file to after fetching it
* @return filepath string - location of the local file
* @usage 
*	<?php
*	$file=ftpGetFile(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp',
*		'-local_file'=>'myfile.txt'
*	));
*	?>
*/
function ftpGetFile($params=array()){
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['remote_file'])){$params['-remote_file']=$params['remote_file'];}
	if(isset($params['local_dir'])){$params['-local_dir']=$params['local_dir'];}
	if(isset($params['local_file'])){$params['-local_file']=$params['local_file'];}
	if(isset($params['delete'])){$params['-delete']=$params['delete'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	//default
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	//connect
	$conn_id = ftp_connect($params['-server']);
	//login
	$login = ftp_login($conn_id, $params['-user'], $params['-pass']);
	// turn passive mode on
	ftp_pasv($conn_id, true);
	//change directory
	if(!ftp_chdir($conn_id,$params['-remote_dir'])){return "ftpGetFile Error: cannot chdir to {$params['-remote_dir']}";}
	$file=getFileName($params['-remote_file']);
	$mode=FTP_BINARY;
	if(isTextFile($file)){$mode=FTP_ASCII;}
	// try to download $server_file and save to $local_file
	if(isset($params['-local_file'])){
		$local_file="{$params['-local_dir']}/{$params['-local_file']}";
	}
	else{
		$local_file="{$params['-local_dir']}/{$file}";
	}
	if (ftp_get($conn_id, $local_file, $params['-remote_file'], $mode)) {
		if(isset($params['-delete'])){
        	ftp_delete($conn_id, $params['-remote_file']);
		}
		if($params['-move_dir']){
			//move the file
			$move_file="{$params['-move_dir']}/{$params['-remote_file']}";
			ftp_rename($conn_id, $params['-remote_file'], $move_file);
        }
        //close
		ftp_close($conn_id);

		return $local_file;
	}
	else {
		//close
		ftp_close($conn_id);
		return "Error writing {$remote_file} to {$local_file}";
	}
}
//---------- begin function ftpGetFiles
/**
* @describe gets all files in remote_dir and places it in local_dir
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	-local_dir - specify directory to place local file in
*	-remote_dir string - name of remote directory to list. Defaults to .
*	[-move_dir] string - path to move the remote file to after fetching it
*	[-delete] boolean - delete the remote file to after fetching it
* @return filepath string - location of the local file
* @usage 
*	<?php
*	$file=ftpGetFiles(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp',
*		'-local_file'=>'myfile.txt'
*	));
*	?>
*/
function ftpGetFiles($params=array()){
	//info: gets FTP files on remote server in remote dir and places in in local_dir - returns and array
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['remote_file'])){$params['-remote_file']=$params['remote_file'];}
	if(isset($params['local_dir'])){$params['-local_dir']=$params['local_dir'];}
	if(isset($params['local_file'])){$params['-local_file']=$params['local_file'];}
	if(isset($params['delete'])){$params['-delete']=$params['delete'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	//defaults
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	$conn_id = ftp_connect($params['-server']);
	$login = ftp_login($conn_id, $params['-user'], $params['-pass']);
	// turn passive mode on
	ftp_pasv($conn_id, true);
	//change directory
	if(!ftp_chdir($conn_id,$params['-remote_dir'])){return "ftpGetFile Error: cannot chdir to {$params['-remote_dir']}";}
	// get list of files
	$files = ftp_nlist($conn_id, '.');
	$localfiles=array();
	foreach($files as $remote_file){
		//skip files (dirs) that do not have an extension
		if(!preg_match('/\./',$remote_file)){continue;}
		$file=getFileName($remote_file);
		$mode=FTP_BINARY;
		if(isTextFile($file)){$mode=FTP_ASCII;}
		// try to download $server_file and save to $local_file
		$local_file="{$params['-local_dir']}/{$file}";
		if (ftp_get($conn_id, $local_file, $remote_file, $mode)) {
		    $localfiles[]=$local_file;
		    if($params['-move_dir']){
				//move the file
				$move_file="{$params['-move_dir']}/{$remote_file}";
				ftp_rename($conn_id, $remote_file, $move_file);
            }
		}
		else {
			ftp_close($conn_id);
		    return "Error writing {$remote_file} to {$local_file} . File=[{$file}]";
		}
    }
	ftp_close($conn_id);
	return $localfiles;
}
//---------- begin function ftpPutFile
/**
* @describe uploads a single  file to a remote_dir
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	-local_file - name of local file to save as
*	-remote_file - name of remote file to get
*	[-remote_dir] string - name of remote directory to list. Defaults to .
* @return filepath string - location of the remote file
* @usage 
*	<?php
*	$file=ftpPutFile(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp',
*		'-local_file'=>'myfile.txt'
*	));
*	?>
*/
function ftpPutFile($params=array()){
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['remote_file'])){$params['-remote_file']=$params['remote_file'];}
	if(isset($params['local_dir'])){$params['-local_dir']=$params['local_dir'];}
	if(isset($params['local_file'])){$params['-local_file']=$params['local_file'];}
	if(isset($params['delete'])){$params['-delete']=$params['delete'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	//info: gets FTP files on remote server in remote dir and places in in local_dir - returns and array
	$conn_id = ftp_connect($params['-server']);
	$login = ftp_login($conn_id, $params['-user'], $params['-pass']);
	// turn passive mode on
	ftp_pasv($conn_id, true);
	//change directory
	if(isset($params['-remote_dir']) && !ftp_chdir($conn_id,$params['-remote_dir'])){return "ftpPutFile Error: cannot chdir to {$params['-remote_dir']}";}
	//Set mode
	$mode=FTP_BINARY;
	if(isTextFile($params['-local_file'])){$mode=FTP_ASCII;}
	//upload the file
	if(ftp_put($conn_id,$params['-remote_file'],$params['-local_file'],$mode)){
		ftp_close($conn_id);
    	return $params['-remote_file'];
	}
	else{return "ftpPutFile Error: put failed {$params['-remote_file']}";}
	ftp_close($conn_id);
	return null;
}

?>