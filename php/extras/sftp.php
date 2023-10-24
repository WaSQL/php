<?php
/* References:
	sFTP functions that use phpseclib so that it is pure PHP and will work nearly everywhere
	http://phpseclib.sourceforge.net/sftp/examples.html

	Example #1
		loadExtras('sftp');
		$sftp = new Net_SFTP('192.158.214.215');
		if (!$sftp->login($user, $pass)) {
		    echo('Login Failed');
			exit;
		}
		$sftp->chdir('/example');
		$sftp->put('remotefile.csv', $local_file, NET_SFTP_LOCAL_FILE);
	Example #2
		loadExtras('sftp');
		$key = new Crypt_RSA();
		$sftp = new Net_SFTP('192.158.214.215');
		$key->loadKey(file_get_contents('/var/www/keys/rsafile'));
		if (!$sftp->login($user, $key)) {
		    echo('Login Failed');
		    exit;
		}
		$sftp->chdir('/home/files');
		if(!$sftp->is_dir('/home/files/archive')){
			$sftp->mkdir('/home/files/archive');
		}
		$files=$sftp->rawlist();
		foreach($files as $file){
			// copies filename.remote to filename.local from the SFTP server
			$sftp->get($file['filename'], "/var/www/localfiles/{$file['filename']}");
			$sftp->rename($file['filename'], "/home/files/archive/{$file['filename']}");
		}

*/
$progpath=dirname(__FILE__);
$incpath=get_include_path() . PATH_SEPARATOR . "{$progpath}/phpseclib";
set_include_path($incpath);
include_once('Net/SSH2.php');
include_once('Net/SFTP.php');
include_once('Crypt/RSA.php');

//---------- begin function sftpListFiles
/**
* @describe lists files on an FTP server
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	[-remote_dir] string - name of remote directory to list. Defaults to .
* @return array - array of file names
* @usage
*	$remote_files=ftpListFiles(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp'
*	));
*/
function sftpListFiles($params=array()){
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	//default
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	//login
	$sftp = new Net_SFTP($params['-server']);
	if (!$sftp->login($params['-user'], $params['-pass'])) {
   	 return "sftpListFiles Error: Login Failed";
	}
	// get list of files
	$files = $sftp->nlist($params['-remote_dir']);
	//close
	//$sftp->close();
	//return
	return $files;
	}
//---------- begin function sftpGetFile
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
*	$file=ftpGetFile(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp',
*		'-local_file'=>'myfile.txt'
*	));
*/
function sftpGetFile($params=array()){
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
	if(!isset($params['-local_dir'])){
		$progpath=dirname(__FILE__);
		$progpath=str_replace('extras','temp');
		$params['-local_dir']=$progpath;
	}
	//login
	$sftp = new Net_SFTP($params['-server']);
	if (!$sftp->login($params['-user'], $params['-pass'])) {
   	 return "sftpGetFile Error: Login Failed";
	}
	//change directory
	if(!$sftp->chdir($params['-remote_dir'])){return "sftpGetFile Error: cannot chdir to {$params['-remote_dir']}";}
	$file=getFileName($params['-remote_file']);
	// try to download $server_file and save to $local_file
	if(isset($params['-local_file'])){
		$local_file="{$params['-local_dir']}/{$params['-local_file']}";
	}
	else{
		$local_file="{$params['-local_dir']}/{$file}";
	}
	if ($sftp->get($params['-remote_file'],$local_file)) {
		if(isset($params['-delete'])){
        	$sftp->delete($params['-remote_file']);
		}
		if($params['-move_dir']){
			//move the file
			$move_file="{$params['-move_dir']}/{$params['-remote_file']}";
			$sftp->rename($params['-remote_file'], $move_file);
        }
        //close
		//$sftp->close();

		return $local_file;
	}
	else {
		//close
		//$sftp->close();
		return "Error writing {$remote_file} to {$local_file}";
	}
}
//---------- begin function sftpGetFileContents
/**
* @describe gets a single FTP file contents
* @param params array
*	-server string - ftp server to connect to
*	-user string - username
*	-pass string - password
*	-remote_file - name of remote file to get
*	[-remote_dir] string - name of remote directory to list. Defaults to .
*	[-move_dir] string - path to move the remote file to after fetching it
*	[-delete] boolean - delete the remote file to after fetching it
* @return string - contents of the remote file
* @usage 
*	$file=sftpGetFileContents(array(
*		'-server'	=> 'ftp.myserver.com',
*		'-user'		=> 'myusername',
*		'-pass'		=> 'mypassword',
*		'-remote_dir'=>'/var/home/temp',
*		'-remote_file'=>'myfile.txt'
*	));
*/
function sftpGetFileContents($params=array()){
	//backward compatibility
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['remote_file'])){$params['-remote_file']=$params['remote_file'];}
	if(isset($params['delete'])){$params['-delete']=$params['delete'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	//default
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	$progpath=dirname(__FILE__);
	$progpath=str_replace('extras','temp',$progpath);
	$params['-local_dir']=$progpath;
	$params['-local_file']=sha1($params['remote_file']).'.tmp';
	//echo printValue($params);exit;
	//login
	$sftp = new Net_SFTP($params['-server']);
	if (!$sftp->login($params['-user'], $params['-pass'])) {
   	 return "sftpGetFileContents Error: Login Failed";
	}
	//change directory
	if(!$sftp->chdir($params['-remote_dir'])){return "sftpGetFileContents Error: cannot chdir to {$params['-remote_dir']}";}
	$file=getFileName($params['-remote_file']);
	// try to download $server_file and save to $local_file
	if(isset($params['-local_file'])){
		$local_file="{$params['-local_dir']}/{$params['-local_file']}";
	}
	else{
		$local_file="{$params['-local_dir']}/{$file}";
	}
	if ($sftp->get($params['-remote_file'],$local_file)) {
		if(isset($params['-delete'])){
        	$sftp->delete($params['-remote_file']);
		}
		if($params['-move_dir']){
			//move the file
			$move_file="{$params['-move_dir']}/{$params['-remote_file']}";
			$sftp->rename($params['-remote_file'], $move_file);
        }
        //close
		//$sftp->close();

		$contents=getFileContents($local_file);
		unlink($local_file);
		return $contents;
	}
	else {
		//close
		//$sftp->close();
		return "Error writing {$remote_file} to {$local_file}";
	}
}
//---------- begin function sftpGetFiles
function sftpGetFiles($params=array()){
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	if(isset($params['local_file'])){$params['-local_file']=$params['local_file'];}
	//info: gets FTP files on remote server in remote dir and places in in local_dir - returns and array
	if(!isset($params['-remote_dir'])){$params['-remote_dir']='.';}
	//login
	$sftp = new Net_SFTP($params['-server']);
	if (!$sftp->login($params['-user'], $params['-pass'])) {
   	 return "sftpGetFiles Error: Login Failed";
	}
	//change directory
	if(!$sftp->chdir($params['-remote_dir'])){return "sftpGetFiles Error: cannot chdir to {$params['-remote_dir']}";}
	// get list of files
	$files = $sftp->nlist('.');
	$localfiles=array();
	foreach($files as $remote_file){
		//skip files (dirs) that do not have an extension
		if(!preg_match('/\./',$remote_file)){continue;}
		$file=getFileName($remote_file);
		// try to download $server_file and save to $local_file
		$local_file="{$params['-local_dir']}/{$file}";
		if ($sftp->get($remote_file,$local_file)) {
		    $localfiles[]=$local_file;
		    if($params['-move_dir']){
				//move the file
				$move_file="{$params['-move_dir']}/{$remote_file}";
				$sftp->rename($remote_file, $move_file);
            }
		}
		else {
			$sftp->close();
		    return "Error writing {$remote_file} to {$local_file} . File=[{$file}]";
		}
    }
	//$sftp->close();
	return $localfiles;
}
//---------- begin function sftpPutFile
function sftpPutFile($params=array()){
	if(isset($params['server'])){$params['-server']=$params['server'];}
	if(isset($params['user'])){$params['-user']=$params['user'];}
	if(isset($params['pass'])){$params['-pass']=$params['pass'];}
	if(isset($params['remote_dir'])){$params['-remote_dir']=$params['remote_dir'];}
	if(isset($params['move_dir'])){$params['-move_dir']=$params['move_dir'];}
	if(isset($params['local_file'])){$params['-local_file']=$params['local_file'];}
	//info: gets FTP files on remote server in remote dir and places in in local_dir - returns and array
	//login
	$sftp = new Net_SFTP($params['-server']);
	if (!$sftp->login($params['-user'], $params['-pass'])) {
   	 return "sftpPutFile Error: Login Failed";
	}
	//change directory
	if(isset($params['-remote_dir']) && !$sftp->chdir($params['-remote_dir'])){return "sftpPutFile Error: cannot chdir to {$params['-remote_dir']}";}
	//Set mode
	//upload the file
	if($sftp->put($params['-remote_file'],$params['-local_file'],$mode)){
		$sftp->close();
    	return $params['-remote_file'];
	}
	else{return "ftpPutFile Error: put failed {$params['-remote_file']}";}
	//$sftp->close();
	return null;
}

?>