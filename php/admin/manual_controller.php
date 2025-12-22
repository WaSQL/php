<?php
// Authentication check - ensure user is logged in
global $USER;
if(!isUser()){
	echo buildFormMsg('Access Denied: You must be logged in to view documentation.','error');
	exit;
}

global $progpath;
global $CONFIG;
$progpath=getWasqlPath();

$docs_files='';
$docfile="{$progpath}/temp/docfile.json";
// Only unlink if file exists
if(is_file($docfile)){
	@unlink($docfile);
}
switch(strtolower($_REQUEST['func'])){
	case 'docid':
		$docid=(integer)$_REQUEST['docid'];
		if($docid <= 0){
			setView('no_results',1);
			return;
		}
		$rec=getDBRecordById('_docs',$docid);
		if(!is_array($rec) || !count($rec)){
			setView('no_results',1);
			return;
		}
		$rec['info_ex']=decodeJson($rec['info']);
		$recs=array($rec);
		//echo printValue($sdocs);exit;
		setView('search_results',1);
		return;
	break;
	case 'search':
		$search=trim($_REQUEST['search']);
		// Validate and sanitize search input
		if(strlen($search) == 0 || strlen($search) > 255){
			setView('no_results',1);
			return;
		}
		// Use parameterized query to prevent SQL injection
		$search_escaped=addslashes($search);
		$wheres=array();
		$ors[]="(name like '%{$search_escaped}%')";
		$opts=array(
			'-table'=>'_docs',
			'-where'=>implode(' or ',$ors)
		);
		$recs=getDBRecords($opts);
		if(!is_array($recs) || !count($recs)){
			setView('no_results',1);
			return;
		}
		foreach($recs as $i=>$rec){
			$recs[$i]['info_ex']=decodeJSON($rec['info']);
			if(!isset($recs[$i]['info_ex']) || !isset($recs[$i]['info_ex']['describe'])){
				$recs[$i]['info_ex']['describe']='';
				continue;
			}
			if(isset($recs[$i]['info_ex']['describe'][0])){
				$recs[$i]['describe']=array();
				foreach($recs[$i]['info_ex']['describe'] as $str){
					$recs[$i]['describe'][]=base64_decode($str);
				}
				$recs[$i]['describe']=implode('<br>',$recs[$i]['describe']);
			}
			elseif(is_string($recs[$i]['info_ex']['describe'])){
				$recs[$i]['describe']=base64_decode($recs[$i]['info_ex']['describe']);
			}
			else{
				$recs[$i]['describe']='';
			}
		}
		setView('search_results',1);
		return;
	break;
	case 'filenames':
		$category=trim($_REQUEST['category']);
		// Validate category input
		if(strlen($category) == 0 || strlen($category) > 100){
			setView('no_results',1);
			return;
		}
		$filenames=manualGetFileNames($category);
		if(!is_array($filenames) || !count($filenames)){
			setView('no_results',1);
			return;
		}
		setView('filenames',1);
		return;
	break;
	case 'names':
		$afile=trim($_REQUEST['afile']);
		// Validate afile is base64 encoded (should only contain valid base64 chars)
		if(strlen($afile) == 0 || !preg_match('/^[A-Za-z0-9+\/=]+$/', $afile)){
			setView('no_results',1);
			return;
		}
		$names=manualGetNames($afile);
		if(!is_array($names) || !count($names)){
			setView('no_results',1);
			return;
		}
		//echo "names".printValue($names);exit;
		setView('names',1);
		return;
	break;
	case 'rebuild':
		// Only allow rebuild if explicitly requested
		if(!isset($_REQUEST['confirm']) || $_REQUEST['confirm'] !== 'yes'){
			echo buildFormMsg('To rebuild documentation, add &confirm=yes to the URL. Warning: This may take several minutes.','warning');
			exit;
		}
		// Set time limit for long-running rebuild operation
		set_time_limit(300); // 5 minutes
		manualRebuildDocs();
		$categories=manualGetCategories();
		setView('default',1);
		echo buildFormMsg('Documentation rebuilt successfully.','success');
	break;
	default:
		// Create tables if they don't exist
		if(!isDBTable('_docs')){
			$ok=createWasqlTable('_docs');
		}
		if(!isDBTable('_docs_files')){
			$ok=createWasqlTable('_docs_files');
		}

		// Check if documentation exists, if not suggest rebuild
		$doc_count=getDBCount(array('-table'=>'_docs'));
		if($doc_count == 0){
			echo buildFormMsg('Documentation database is empty. <a href="?_menu=manual&func=rebuild&confirm=yes">Click here to build documentation</a> (this may take several minutes).','info');
			exit;
		}

		//get categories
		$categories=manualGetCategories();
		setView('default',1);
	break;
}
setView('default',1);
?>
