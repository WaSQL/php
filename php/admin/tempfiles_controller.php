<?php
//Access control check
if(!isAdmin()){
	echo '<div class="w_bold w_danger">Access Denied: Admin privileges required</div>';
	exit;
}

global $path;
$path=getWasqlPath('/php/temp');

switch(strtolower($_REQUEST['func'])){
	case 'view':
	break;
	case 'clear_tab':
		$ext=$_REQUEST['ext'];
		if(!tempfilesValidateExtension($ext)){
			echo '<div class="w_bold w_danger">Invalid extension</div>';
			exit;
		}
		cleanupDirectory($path,1,'min',$ext);
		$tabs=tempfilesGetTabs();
		setView('default');
		return;
	break;
	case 'clear_checked':
		$ext=$_REQUEST['ext'];
		if(!tempfilesValidateExtension($ext)){
			echo '<div class="w_bold w_danger">Invalid extension</div>';
			exit;
		}
		$names=preg_split('/\;/',$_REQUEST['files']);
		foreach($names as $name){
			if(!tempfilesValidateFileName($name)){
				continue; //Skip invalid filenames
			}
			$afile="{$path}/{$name}.{$ext}";
			//Double-check the file is within the temp directory
			$realPath=realpath($afile);
			if($realPath && strpos($realPath,realpath($path))===0 && file_exists($afile)){
				unlink($afile);
			}
		}
		setView('list',1);
	break;
	case 'clear_file':
		$ext=$_REQUEST['ext'];
		$file=$_REQUEST['file'];
		if(!tempfilesValidateExtension($ext)){
			echo '<div class="w_bold w_danger">Invalid extension</div>';
			exit;
		}
		if(!tempfilesValidateFileName($file)){
			echo '<div class="w_bold w_danger">Invalid filename</div>';
			exit;
		}
		$afile="{$path}/{$file}";
		//Validate the file is within the temp directory
		$realPath=realpath($afile);
		if(!$realPath || strpos($realPath,realpath($path))!==0){
			echo '<div class="w_bold w_danger">Invalid file path</div>';
			exit;
		}
		if($ext != 'log'){
			//matching logfile?
			$logfile=preg_replace('/\.'.$ext.'$/','.log',$afile);
			$logRealPath=realpath($logfile);
			if(file_exists($logfile) && $logRealPath && strpos($logRealPath,realpath($path))===0){
				unlink($logfile);
			}
		}
		if(file_exists($afile)){
			unlink($afile);
		}
		setView('list',1);
	break;
	case 'view_file':
		$file=$_REQUEST['file'];
		if(!tempfilesValidateFileName($file)){
			echo '<div class="w_bold w_danger">Invalid filename</div>';
			exit;
		}
		$afile="{$path}/{$file}";
		//Validate the file is within the temp directory
		$realPath=realpath($afile);
		if(!$realPath || strpos($realPath,realpath($path))!==0){
			echo '<div class="w_bold w_danger">Invalid file path</div>';
			exit;
		}
		if(!file_exists($afile)){
			echo '<div class="w_bold w_danger">File not found</div>';
			exit;
		}
		if(filesize($afile) > 2000000){
			$content=tailFile($afile,300);
		}
		else{
			$content=getFileContents($afile);
		}
		setView('view_file',1);
		return;
	break;
	case 'clear_all':
		cleanDir($path);
		$tabs=tempfilesGetTabs();
		setView('default');
		return;
	break;
	case 'list':
		$ext=$_REQUEST['ext'];
		if(!tempfilesValidateExtension($ext)){
			echo '<div class="w_bold w_danger">Invalid extension</div>';
			exit;
		}
		$sort='name';
		if(isset($_REQUEST['sort']) && strlen($_REQUEST['sort'])){
			//Validate sort parameter to prevent injection
			$allowedSorts=array('name','username','db','size','_cdate_age_verbose','_adate_age_verbose',
				'name desc','username desc','db desc','size desc','_cdate_age_verbose desc','_adate_age_verbose desc',
				'name asc','username asc','db asc','size asc','_cdate_age_verbose asc','_adate_age_verbose asc');
			if(in_array(strtolower($_REQUEST['sort']),$allowedSorts)){
				$sort=$_REQUEST['sort'];
			}
		}
		setView('list',1);
		return;
	break;
	default:
		$tabs=tempfilesGetTabs();
		setView('default');
	break;
}
?>
