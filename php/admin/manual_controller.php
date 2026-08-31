<?php
// Authentication check - ensure user is logged in
global $USER;
if(!isUser()){
	echo '<div class="w_error">Access Denied: You must be logged in to view documentation.</div>';
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
		$docid=(int)$_REQUEST['docid'];
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
		manualPrepareDoc($rec);
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
		$ors=array();
		$ors[]="(name like '%{$search_escaped}%')";
		$opts=array(
			'-table'=>'_docs',
			'-where'=>implode(' or ',$ors),
			'-order'=>'name,category'
		);
		$recs=getDBRecords($opts);
		if(!is_array($recs) || !count($recs)){
			setView('no_results',1);
			return;
		}
		foreach($recs as $i=>$rec){
			$recs[$i]['info_ex']=decodeJSON($rec['info']);
			manualPrepareDoc($recs[$i]);
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
		// The Rebuild button confirms client-side and sends confirm=yes; the guard keeps
		// a stray GET (prefetch, crawler) from kicking off a multi-minute parse.
		if(!isset($_REQUEST['confirm']) || $_REQUEST['confirm'] !== 'yes'){
			echo '<div class="manual-flash is-warning"><span class="icon-warning"></span> Add <code>&amp;confirm=yes</code> to rebuild the documentation index.</div>';
			return;
		}
		if(!isDBTable('_docs')){$ok=createWasqlTable('_docs');}
		if(!isDBTable('_docs_files')){$ok=createWasqlTable('_docs_files');}
		set_time_limit(600);
		$manual_rebuild_started=microtime(true);
		manualRebuildDocs();
		$manual_stats=manualStats();
		$manual_stats['seconds']=round(microtime(true)-$manual_rebuild_started,1);
		setView('flash',1);
		return;
	break;
	default:
		// Create tables if they don't exist
		if(!isDBTable('_docs')){
			$ok=createWasqlTable('_docs');
		}
		if(!isDBTable('_docs_files')){
			$ok=createWasqlTable('_docs_files');
		}

		$manual_stats=manualStats();
		$categories=manualGetCategories();
	break;
}
setView('default',1);
?>
