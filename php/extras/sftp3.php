<?php
/**
 * sftp3.php - SFTP via the vendored phpseclib 3.x (pure PHP, no ext-ssh2, no libssh).
 *
 * WHY THIS EXISTS (read before reaching for something else):
 *   - php/extras/nativeSFTP.php needs ext-ssh2, which is not installed on every server.
 *   - php/extras/sftp.php wraps the phpseclib 0.3.x/PHP-4-era copy in php/extras/phpseclib;
 *     its algorithm list will not negotiate with many current servers.
 *   - php-curl's sftp:// and the OpenSSH CLI both verify host key signatures through
 *     OpenSSL, so on RHEL 9 the DEFAULT crypto policy (rh-allow-sha1-signatures = no)
 *     makes them refuse any server whose only host key is `ssh-rsa` - and no client-side
 *     flag overrides that. Salesforce Marketing Cloud (ftp1.exacttarget.com, a GlobalSCAPE
 *     EFT appliance) is exactly such a server.
 *   phpseclib does its crypto in PHP, so it is not bound by system crypto-policies and
 *   can talk to those endpoints without weakening the host.
 *
 * Deliberately free of WaSQL helper calls so it can also be included from a plain CLI
 * script (see sftp3Test.php) without bootstrapping the framework.
 *
 * Convention: every function returns its result on success, or an ERROR STRING on
 * failure, the same way php/extras/sftp.php behaves.
 *
 * ⚠️ TEST FAILURES WITH sftp3IsError($rtn), NOT is_string($rtn). Some functions return a
 * string ON SUCCESS - sftp3GetFile() hands back the local path (or the file contents when
 * -local_file is omitted) - so is_string() reports those successes as failures. Error
 * strings always start "sftp3<Function> Error: ", which is what sftp3IsError() looks for.
 *
 * PATHS: pass -remote_dir and plain filenames. These functions chdir() first, so names
 * are relative to your login directory, matching what you see in WinSCP. (This is the
 * trap that makes curl's sftp:// fail - there a path is absolute from /.)
 *
 * @usage
 *   loadExtras('sftp3');
 *   $rtn = sftp3PutFile(array(
 *       '-server'      => 'ftp1.exacttarget.com',
 *       '-user'        => '10965440',
 *       '-pass'        => $pass,
 *       '-local_file'  => '/var/www/wasql/php/temp/out.csv',
 *       '-remote_dir'  => 'Triggered_Automations',
 *       '-part'        => 1,
 *       '-fingerprint' => 'SHA256:bFCXgHdgCzL7mYTH8PZoSmYyiq0ryMDkYzk1Z2YfIeE',
 *   ));
 *   if(sftp3IsError($rtn)){ echo $rtn; }   // failures come back as an error string
 */

require_once __DIR__ . '/phpseclib3/autoload.php';

//---------- begin function sftp3Connect
/**
* @describe connects and authenticates, returning a ready phpseclib SFTP object
* @param params array
*	-server string - sftp server to connect to
*	-user string - username
*	-pass string - password
*	[-port] int - defaults to 22
*	[-timeout] int - seconds, defaults to 30
*	[-fingerprint] string - expected host key fingerprint, e.g. "SHA256:bFCX..." (base64 form
*		printed by ssh/ssh-keygen -l). Verified BEFORE the password is sent.
*	[-hostkey] string - full expected host key ("ssh-rsa AAAAB3..."), an alternative to -fingerprint
*	[-algorithms] array - passed to setPreferredAlgorithms(), e.g. array('hostkey'=>array('ssh-rsa'))
*	[-remote_dir] string - chdir here after login
* @return object phpseclib3\Net\SFTP on success, error string on failure
*/
function sftp3Connect($params=array()){
	$params=sftp3FixParams($params);
	foreach(array('-server','-user','-pass') as $k){
		if(!isset($params[$k]) || $params[$k]===''){
			return "sftp3Connect Error: {$k} is required";
		}
	}
	$port=isset($params['-port'])?(int)$params['-port']:22;
	$timeout=isset($params['-timeout'])?(int)$params['-timeout']:30;
	try{
		$sftp=new \phpseclib3\Net\SFTP($params['-server'],$port,$timeout);
		if(isset($params['-algorithms']) && is_array($params['-algorithms'])){
			$sftp->setPreferredAlgorithms($params['-algorithms']);
		}
		//verify the host key BEFORE handing over credentials. this also forces the handshake.
		if(isset($params['-fingerprint']) || isset($params['-hostkey'])){
			$hostkey=$sftp->getServerPublicHostKey();
			if($hostkey===false){
				return "sftp3Connect Error: could not read the server host key";
			}
			if(isset($params['-hostkey']) && trim($params['-hostkey'])!==''){
				if(trim($hostkey)!==trim($params['-hostkey'])){
					return "sftp3Connect Error: host key mismatch - server presented a different key than -hostkey";
				}
			}
			if(isset($params['-fingerprint']) && trim($params['-fingerprint'])!==''){
				$got=sftp3HostKeyFingerprint($hostkey);
				$want=trim($params['-fingerprint']);
				//accept with or without the "SHA256:" prefix, and with or without base64 padding
				$norm=function($s){return rtrim(preg_replace('/^SHA256:/i','',trim($s)),'=');};
				if($norm($got)!==$norm($want)){
					return "sftp3Connect Error: host key fingerprint mismatch - expected {$want}, got {$got}";
				}
			}
		}
		if(!$sftp->login($params['-user'],$params['-pass'])){
			return 'sftp3Connect Error: login failed for '.$params['-user'].'@'.$params['-server'];
		}
		if(isset($params['-remote_dir']) && trim($params['-remote_dir'])!=='' && $params['-remote_dir']!=='.'){
			if(!$sftp->chdir($params['-remote_dir'])){
				return "sftp3Connect Error: cannot chdir to {$params['-remote_dir']} - ".sftp3LastError($sftp);
			}
		}
		return $sftp;
	}
	catch(\Throwable $e){
		return 'sftp3Connect Error: '.$e->getMessage();
	}
}
//---------- begin function sftp3PutFile
/**
* @describe uploads a local file (or a string of data) to an SFTP server
* @param params array
*	all of the sftp3Connect params, plus:
*	-local_file string - path of the local file to send (or use -data)
*	-data string - literal contents to write (alternative to -local_file)
*	[-remote_file] string - name to write. Defaults to the basename of -local_file
*	[-part] boolean - upload as <remote_file>.part then rename, so a polling consumer
*		never sees a partial file. Recommended for production drops.
*	[-mkdir] boolean - create -remote_dir if it does not exist
*	[-sftp] object - reuse a connection from sftp3Connect() instead of opening one
* @return boolean true on success, error string on failure
* @usage $rtn=sftp3PutFile(array('-server'=>$h,'-user'=>$u,'-pass'=>$p,'-local_file'=>$f));
*/
function sftp3PutFile($params=array()){
	$params=sftp3FixParams($params);
	$hasData=isset($params['-data']);
	if(!$hasData){
		if(!isset($params['-local_file'])){
			return 'sftp3PutFile Error: -local_file or -data is required';
		}
		if(!is_file($params['-local_file'])){
			return "sftp3PutFile Error: local file not found: {$params['-local_file']}";
		}
	}
	$remote=isset($params['-remote_file'])?$params['-remote_file']:'';
	if($remote===''){
		$remote=$hasData?'':basename($params['-local_file']);
	}
	if($remote===''){
		return 'sftp3PutFile Error: -remote_file is required when sending -data';
	}
	//connect (or reuse)
	$sftp=sftp3Resolve($params,'sftp3PutFile');
	if(is_string($sftp)){return $sftp;}
	try{
		if(!empty($params['-mkdir']) && isset($params['-remote_dir']) && trim($params['-remote_dir'])!==''){
			//harmless if it already exists; only report if the follow-up chdir fails
			$sftp->mkdir($params['-remote_dir'],-1,true);
			if(!$sftp->chdir($params['-remote_dir'])){
				return "sftp3PutFile Error: cannot chdir to {$params['-remote_dir']} - ".sftp3LastError($sftp);
			}
		}
		$target=!empty($params['-part'])?$remote.'.part':$remote;
		if($hasData){
			$ok=$sftp->put($target,$params['-data']);
		}
		else{
			$ok=$sftp->put($target,$params['-local_file'],\phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
		}
		if(!$ok){
			return "sftp3PutFile Error: upload of {$target} failed - ".sftp3LastError($sftp);
		}
		if(!empty($params['-part'])){
			//some servers refuse a rename onto an existing name - clear it first, ignoring failure.
			//2nd arg false: phpseclib's delete() is RECURSIVE by default, which would take out a
			//whole directory tree if this name ever pointed at a directory.
			@$sftp->delete($remote,false);
			if(!$sftp->rename($target,$remote)){
				return "sftp3PutFile Error: uploaded {$target} but rename to {$remote} failed - ".sftp3LastError($sftp);
			}
		}
		return true;
	}
	catch(\Throwable $e){
		return 'sftp3PutFile Error: '.$e->getMessage();
	}
}
//---------- begin function sftp3GetFile
/**
* @describe downloads a single remote file
* @param params array
*	all of the sftp3Connect params, plus:
*	-remote_file string - name of the remote file to fetch
*	[-local_file] string - where to save it. Omit to return the contents as a string.
*	[-delete] boolean - delete the remote file after a successful fetch
*	[-move_dir] string - move the remote file here after a successful fetch
*	[-sftp] object - reuse a connection from sftp3Connect()
* @return string local path (or the file contents if -local_file is omitted), error string on failure
*/
function sftp3GetFile($params=array()){
	$params=sftp3FixParams($params);
	if(!isset($params['-remote_file']) || $params['-remote_file']===''){
		return 'sftp3GetFile Error: -remote_file is required';
	}
	$sftp=sftp3Resolve($params,'sftp3GetFile');
	if(is_string($sftp)){return $sftp;}
	try{
		$remote=$params['-remote_file'];
		if(isset($params['-local_file'])){
			if(!$sftp->get($remote,$params['-local_file'])){
				return "sftp3GetFile Error: cannot fetch {$remote} - ".sftp3LastError($sftp);
			}
			$rtn=$params['-local_file'];
		}
		else{
			$data=$sftp->get($remote);
			if($data===false){
				return "sftp3GetFile Error: cannot fetch {$remote} - ".sftp3LastError($sftp);
			}
			$rtn=$data;
		}
		if(!empty($params['-delete'])){
			$sftp->delete($remote,false);   //never recurse - see note in sftp3PutFile
		}
		elseif(isset($params['-move_dir']) && trim($params['-move_dir'])!==''){
			$sftp->rename($remote,rtrim($params['-move_dir'],'/').'/'.basename($remote));
		}
		return $rtn;
	}
	catch(\Throwable $e){
		return 'sftp3GetFile Error: '.$e->getMessage();
	}
}
//---------- begin function sftp3ListFiles
/**
* @describe lists filenames in a remote directory
* @param params array
*	all of the sftp3Connect params, plus:
*	[-remote_dir] string - directory to list. Defaults to the login directory.
*	[-raw] boolean - return rawlist() detail (size/mtime/type) instead of names
*	[-sftp] object - reuse a connection from sftp3Connect()
* @return array of filenames (or detail arrays when -raw), error string on failure
*/
function sftp3ListFiles($params=array()){
	$params=sftp3FixParams($params);
	$sftp=sftp3Resolve($params,'sftp3ListFiles');
	if(is_string($sftp)){return $sftp;}
	try{
		$list=!empty($params['-raw'])?$sftp->rawlist('.'):$sftp->nlist('.');
		if($list===false){
			return 'sftp3ListFiles Error: cannot list directory - '.sftp3LastError($sftp);
		}
		if(!empty($params['-raw'])){return $list;}
		$rtn=array();
		foreach($list as $name){
			if($name==='.' || $name==='..'){continue;}
			$rtn[]=$name;
		}
		sort($rtn);
		return $rtn;
	}
	catch(\Throwable $e){
		return 'sftp3ListFiles Error: '.$e->getMessage();
	}
}
//---------- begin function sftp3DeleteFile
/**
* @describe deletes a remote file
* @param params array
*	all of the sftp3Connect params, plus:
*	-remote_file string - name of the remote file to delete
*	[-sftp] object - reuse a connection from sftp3Connect()
* @return boolean true on success, error string on failure
*/
function sftp3DeleteFile($params=array()){
	$params=sftp3FixParams($params);
	if(!isset($params['-remote_file']) || $params['-remote_file']===''){
		return 'sftp3DeleteFile Error: -remote_file is required';
	}
	$sftp=sftp3Resolve($params,'sftp3DeleteFile');
	if(is_string($sftp)){return $sftp;}
	try{
		//2nd arg false - never recurse; this function deletes a FILE (see note in sftp3PutFile)
		if(!$sftp->delete($params['-remote_file'],false)){
			return "sftp3DeleteFile Error: cannot delete {$params['-remote_file']} - ".sftp3LastError($sftp);
		}
		return true;
	}
	catch(\Throwable $e){
		return 'sftp3DeleteFile Error: '.$e->getMessage();
	}
}
//---------- begin function sftp3IsError
/**
* @describe reports whether a value returned by an sftp3* function is an error
* @param rtn mixed - whatever an sftp3* function returned
* @return boolean true when rtn is an sftp3 error string
* @usage
*	$rtn=sftp3GetFile($params);
*	if(sftp3IsError($rtn)){ echo $rtn; }
*	//NOTE: use this rather than is_string() - sftp3GetFile returns the local path (a string)
*	//on SUCCESS, so is_string() would report a successful download as a failure.
*/
function sftp3IsError($rtn){
	return is_string($rtn) && preg_match('/^sftp3[A-Za-z]* Error: /',$rtn)===1;
}
//---------- begin function sftp3HostKeyFingerprint
/**
* @describe converts a host key ("ssh-rsa AAAAB3...") to the SHA256 fingerprint form
*	that ssh and ssh-keygen -l print, so it can be compared to what you saw on connect
* @param hostkey string - as returned by phpseclib's getServerPublicHostKey()
* @return string e.g. "SHA256:bFCXgHdgCzL7mYTH8PZoSmYyiq0ryMDkYzk1Z2YfIeE"
*/
function sftp3HostKeyFingerprint($hostkey){
	$parts=preg_split('/\s+/',trim((string)$hostkey));
	$blob=count($parts)>1?$parts[1]:$parts[0];
	$raw=base64_decode($blob,true);
	if($raw===false){return '';}
	return 'SHA256:'.rtrim(base64_encode(hash('sha256',$raw,true)),'=');
}
//---------- begin function sftp3FixParams
/**
* @describe normalizes dashless keys to the dashed form, matching php/extras/sftp.php
* @param params array
* @return array
*/
function sftp3FixParams($params=array()){
	if(!is_array($params)){return array();}
	foreach($params as $k=>$v){
		if(substr($k,0,1)!=='-'){
			$params['-'.$k]=$v;
		}
	}
	return $params;
}
//---------- begin function sftp3Resolve
/**
* @describe returns the caller-supplied -sftp connection, or opens a new one
* @param params array
* @param caller string - function name, used to prefix any error
* @return object phpseclib3\Net\SFTP, or error string
*/
function sftp3Resolve($params,$caller){
	if(isset($params['-sftp']) && is_object($params['-sftp'])){
		return $params['-sftp'];
	}
	$sftp=sftp3Connect($params);
	if(is_string($sftp)){
		return str_replace('sftp3Connect Error:',"{$caller} Error:",$sftp);
	}
	return $sftp;
}
//---------- begin function sftp3LastError
/**
* @describe best-effort detail for a failed SFTP operation
* @param sftp object
* @return string
*/
function sftp3LastError($sftp){
	$msg='';
	try{
		$msg=(string)$sftp->getLastSFTPError();
		if($msg===''){
			$errors=$sftp->getErrors();
			if(is_array($errors) && count($errors)){$msg=(string)end($errors);}
		}
	}
	catch(\Throwable $e){}
	return $msg===''?'no detail reported by the server':$msg;
}
