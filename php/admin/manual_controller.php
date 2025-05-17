<?php
global $progpath;
global $CONFIG;
$progpath=getWasqlPath();

$docs_files='';
$docfile="{$progpath}/temp/docfile.json";
unlink($docfile);
switch(strtolower($_REQUEST['func'])){
	case 'docid':
		$docid=(integer)$_REQUEST['docid'];
		$rec=getDBRecordById('_docs',$docid);
		$rec['info_ex']=decodeJson($rec['info']);
		$recs=array($rec);
		//echo printValue($sdocs);exit;
		setView('search_results',1);
		return;
	break;
	case 'search':
		$search=$_REQUEST['search'];
		$wheres=array();
		$ors[]="(name like '%{$search}%')";
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
		$category=$_REQUEST['category'];
		$filenames=manualGetFileNames($category);
		setView('filenames',1);
		return;
	break;
	case 'names':
		$afile=$_REQUEST['afile'];
		$names=manualGetNames($afile);
		//echo "names".printValue($names);exit;
		setView('names',1);
		return;
	break;
	default:
		if(!isDBTable('_docs')){
			$ok=createWasqlTable('_docs');
		}
		if(!isDBTable('_docs_files')){
			$ok=createWasqlTable('_docs_files');
		}
		//php
		$path=getWasqlPath('php');
		$files=listFilesEx($path,array('ext'=>'php'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		//php/extras
		$path=getWasqlPath('php/extras');
		$files=listFilesEx($path,array('ext'=>'php'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		//python
		$path=getWasqlPath('python');
		$files=listFilesEx($path,array('ext'=>'py'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		//python/extras
		$path=getWasqlPath('python/extras');
		$files=listFilesEx($path,array('ext'=>'py'));
		foreach($files as $file){
			$ok=manualParseFile($file['afile']);
		}
		//js
		$path=getWasqlPath('wfiles/js');
		$files=listFilesEx($path,array('ext'=>'js'));
		foreach($files as $file){
			if(stringEndsWith($file['name'],'min.js')){continue;}
			$ok=manualParseFile($file['afile']);
		}
		//js/extras
		$path=getWasqlPath('wfiles/js/extras');
		$files=listFilesEx($path,array('ext'=>'js'));
		foreach($files as $file){
			if(stringEndsWith($file['name'],'min.js')){continue;}
			$ok=manualParseFile($file['afile']);
		}
		//get functions in pages
		$recs=getDBRecords(array(
			'-table'=>'_pages',
			'-where'=>"length(functions) > 20",
			'-fields'=>'_id,name'
		));
		foreach($recs as $rec){
			$afile="_pages:{$rec['_id']}:functions:{$rec['name']}";
			$ok=manualParseFile($afile);
		}
		$recs=getDBRecords(array(
			'-table'=>'_pages',
			'-where'=>"body like '<?php%'",
			'-fields'=>'_id,name'
		));
		foreach($recs as $rec){
			$afile="_pages:{$rec['_id']}:body:{$rec['name']}";
			$ok=manualParseFile($afile);
		}
		//get functions in templates
		$recs=getDBRecords(array(
			'-table'=>'_templates',
			'-where'=>"length(functions) > 20",
			'-fields'=>'_id,name'
		));
		foreach($recs as $rec){
			$afile="_templates:{$rec['_id']}:functions:{$rec['name']}";
			$ok=manualParseFile($afile);
		}
		//get categories
		$categories=manualGetCategories();
		setView('default',1);
	break;
}
setView('default',1);
?>
