<?php
global $progpath;
$docfile="{$progpath}/temp/docfile.json";
switch(strtolower($_REQUEST['func'])){
	case 'search':
		$docs=json_decode(getFileContents($docfile),true);
		if(!is_array($docs)){
			echo "failed to decode docs";exit;
		}
		$search=$_REQUEST['search'];
		$sdocs=array();
		//echo "HERE".printValue($docs);exit;
		foreach($docs as $file=>$functions){
			if(!isset($functions[0])){
				echo "HERE".printValue($functions);exit;
			}
			foreach($functions as $function){
				$found=0;
				foreach($function as $k=>$v){
					if(is_array($v)){
						foreach($v as $vv){
							if(stringContains(removeHtml($vv),$search)){
								$found++;
								continue;
							}
						}
					}
					else{
						if(stringContains(removeHtml($v),$search)){
							$found++;
							continue;
						}
					}
				}
				if($found){
					$sdocs[]=$function;
				}
			}
		}
		setView('search',1);
		return;
	break;
	default:
		global $docs;
		$docs=array();
		$files=listFilesEx($progpath,array('ext'=>'php'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		$files=listFilesEx("{$progpath}/extras",array('ext'=>'php'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		//echo printValue($docs);exit;
		$content=json_encode($docs,JSON_UNESCAPED_UNICODE);
		setFileContents($docfile,$content);
		setView('default',1);
	break;
}
setView('default',1);
?>
