<?php
function gitFileInfo(){
	global $git;
	$git['status']=gitCommand('status -sb');
	$lines=preg_split('/[\r\n]+/',trim($git['status']));
	//$git['lines']=$lines;
	$git['b64sha']=array();
	foreach($lines as $line){
		$line=trim($line);
		$x=substr($line,0,1);
		$line=preg_replace('/^.{2,2}/','',$line);
		$parts=preg_split('/\s+/',ltrim($line));
		switch(strtoupper($x)){
			case '#':
				$xparts=preg_split('/\.\.\./',$parts[0]);
				$git['branch']=$parts[0];
			break;
			case ' ':$status='unmodified';break;
			case 'M':$status='modified';break;
			case 'A':$status='added';break;
			case 'D':$status='deleted';break;
			case 'R':$status='renamed';break;
			case 'C':$status='copied';break;
			case 'U':$status='updated but unmerged';break;
			case '?':$status='new';break;
			default:$status="unknown-{$x}";break;
		}
		if(strtoupper($x)=='#'){continue;}
		$file=$parts[0];
		$afile="{$_SESSION['git_path']}/{$file}";
		$afile=str_replace("/","\\",$afile);
		$rec=array(
			'name'=>$file,
			'afile'=>$afile,
			'status'=>$status,
			'sha'=>sha1($afile),
			'b64'=>encodeBase64($file)
		);
		$git['b64sha'][$rec['b64']]=$rec['sha'];
		if(file_exists($afile)){
			$age=time()-filemtime($afile);
			$rec['lines']=getFileLineCount($afile);
			$rec['age']=$age;
			$rec['age_verbose']=verboseTime($age);
		}
		$git['files'][]=$rec;
	}
	return;
}
function gitGetPath(){
	$recs=getDBRecords(array(
		'-table'=>'_settings',
		'-where'=>"user_id=0 and key_name like 'wasql_git%'",
		'-index'=>'key_name',
		'-fields'=>'_id,key_name,key_value'
	));
	if(!isset($recs['wasql_git']['key_value']) || $recs['wasql_git']['key_value'] != 1){
		return 'not_enabled';
	}
	if(!isset($recs['wasql_git_path']['key_value']) || !is_dir($recs['wasql_git_path']['key_value'])){
		return 'invalid_path';
	}
	return $recs['wasql_git_path']['key_value'];
}
function gitCommand($args,$lines=0){
	$out=cmdResults('git',$args,$_SESSION['git_path']);
	if(isset($out['stderr']) && strlen($out['stderr'])){
		if($lines==1){
			$lines=preg_split('/[\r\n]+/',trim($out['stderr']));
			return $lines;
		}
		return $out['stderr'];
	}
	if($lines==1){
		$lines=preg_split('/[\r\n]+/',trim($out['stdout']));
		return $lines;
	}
	return $out['stdout'];
}
?>
