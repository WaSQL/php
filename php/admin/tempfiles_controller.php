<?php
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
	$sortkey=isset($_REQUEST['sort'])?$_REQUEST['sort']:name;
	$files=sortArrayByKey($files,$sortkey,SORT_ASC);
?>