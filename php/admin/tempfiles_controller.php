<?php
global $path;
$path=getWasqlPath('/php/temp');
switch(strtolower($_REQUEST['func'])){
	case 'view':
	break;
	case 'clear_tab':
		$ext=$_REQUEST['ext'];
		cleanupDirectory($path,1,'min',$ext);
		$tabs=tempfilesGetTabs();
		setView('default');
		return;
	break;
	case 'clear_checked':
		$ext=$_REQUEST['ext'];
		$names=preg_split('/\;/',$_REQUEST['files']);
		foreach($names as $name){
			$afile="{$path}/{$name}.{$ext}";
			unlink($afile);
		}
		setView('list',1);
	break;
	case 'clear_file':
		$ext=$_REQUEST['ext'];
		$file=$_REQUEST['file'];
		$afile="{$path}/{$file}";
		unlink($afile);
		setView('list',1);
	break;
	case 'view_file':
		$file=$_REQUEST['file'];
		$afile="{$path}/{$file}";
		$content=getFileContents($afile);
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
		$sort='name';
		setView('list',1);
		return;
	break;
	default:
		$tabs=tempfilesGetTabs();
		setView('default');
	break;
}
return;
	$progpath=dirname(__FILE__);
	$tempdir="{$progpath}/temp";
	if(isset($_REQUEST['delete']) && is_array($_REQUEST['delete'])){
    	foreach($_REQUEST['delete'] as $file){
        	$file=decodeBase64($file);
        	unlink("{$tempdir}/{$file}");
        	//echo "unlink {$tempdir}/{$file}<br>";
		}
	}
	elseif(isset($_REQUEST['file'])){
		$files=listFilesEx($tempdir,array('name'=>decodeBase64($_REQUEST['file'])));
		$file=$files[0];
		unset($files);
		switch($file['ext']){
        	case 'php':$behavior='phpeditor';break;
			case 'js':$behavior='jseditor';break;
			case 'css':$behavior='csseditor';break;
			case 'pl':$behavior='perleditor';break;
			case 'rb':$behavior='rubyeditor';break;
			case 'sql':$behavior='sqleditor';break;
			case 'xml':$behavior='xmleditor';break;
			case 'txt':$behavior='txteditor';break;
        	default:
        		$behavior='';
        	break;
		}
		if($file['lines'] < 500){
			$content=getFileContents($file['afile']);
		}
		else{
			$content="NOTE: File is too large to view full contents. Only showing first 500 lines below:\r\n\r\n";
			$content .= getFileContentsPartial($file['afile'],0,500);
			$content .= "\r\n\r\nNOTE: File is too large to view full contents. Only showing first 500 lines above:";
		}
		setView('details',1);
		return;
	}
	setView('default',1);
	$files=listFilesEx($tempdir);
	$exts=array();
	foreach($files as $i=>$file){
		$exts[$file['ext']]+=1;
		$files[$i]['sha_name']=sha1($file['name']);
	}
	ksort($exts);
	foreach($exts as $ext=>$cnt){
		break;
	}
	$sortkey=isset($_REQUEST['sort'])?$_REQUEST['sort']:'name';
	$files=sortArrayByKey($files,$sortkey,SORT_ASC);
?>
