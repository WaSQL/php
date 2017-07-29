<?php
/*
	Run the following to fix this error: cannot open .git/FETCH_HEAD: Permission denied
		sudo chmod g+w .git/FETCH_HEAD
*/
	//get path
	if(!isset($_SESSION['git_path']) || !strlen($_SESSION['git_path'])){
		$p=gitGetPath();
		switch(strtolower($p)){
			case 'not_enabled':
			case 'invalid_path':
				setView($p,1);
				return;
			break;
			default:
				$_SESSION['git_path']=$p;
			break;
		}
	}
	global $git;
	if(!isset($_REQUEST['func'])){$_REQUEST['func']='';}
	switch(strtolower($_REQUEST['func'])){
		case 'pull':
			$recs=gitCommand('pull -v',1);
			setView('git_details',1);
			return;
		break;
		case 'add':
			$recs=array();
			if(isset($_REQUEST['files'][0])){
				foreach($_REQUEST['files'] as $bfile){
					$file=addslashes(base64_decode($bfile));
					$recs[]=gitCommand("add \"{$file}\"");
				}
			}
			setView('git_details',1);
			return;
		break;
		case 'remove':
			$recs=array();
			if(isset($_REQUEST['files'][0])){
				foreach($_REQUEST['files'] as $bfile){
					$file=addslashes(base64_decode($bfile));
					$recs[]=gitCommand("rm \"{$file}\"");
				}
			}
			gitFileInfo();
			setView('default',1);
			return;
		break;
		case 'revert':
			if(isset($_REQUEST['files'][0])){
				foreach($_REQUEST['files'] as $bfile){
					$file=addslashes(base64_decode($bfile));
					$git['details'][]=gitCommand("checkout \"{$file}\"");
				}
			}
			setView('git_details',1);
			return;
		break;
		case 'commit_push':
			gitFileInfo();
			$recs=array();
			if(isset($_REQUEST['files'][0])){
				$push=0;
				foreach($_REQUEST['files'] as $bfile){
					$file=addslashes(base64_decode($bfile));
					$sha=$git['b64sha'][$bfile];
					$msg='';
					if(isset($_REQUEST["msg_{$sha}"]) && strlen(trim($_REQUEST["msg_{$sha}"]))){$msg=$_REQUEST["msg_{$sha}"];}
					elseif(isset($_REQUEST['msg']) && strlen(trim($_REQUEST['msg']))){$msg=$_REQUEST['msg'];}
					if(strlen($msg)){
						$recs[]=gitCommand("commit -m \"{$msg}\" \"{$file}\"");
						$push++;
					}
					else{
						$recs[]="MISSING MESSAGE for \"{$file}\" - NOT PUSHED";
					}
				}
				//echo printValue($git['details']);exit;
				if($push > 0){
					$recs[]=gitCommand("push");
				}
			}
			setView('git_details',1);
			return;
		break;
		case 'status':
			$git['status']=gitCommand('status -s');
			setView('git_status',1);
			return;
		break;
		case 'config':
			$git['config']=gitCommand('config -l');
			setView('git_config',1);
			return;
		break;
		case 'diff':
			$file=addslashes(base64_decode($_REQUEST['file']));
			$lines=gitCommand("diff \"{$file}\"",1);
			$recs=array();
			foreach($lines as $line){
				$row=array();
				if(preg_match('/^\+/',$line)){
					$row['class']='w_ins';
				}
				elseif(preg_match('/^\-/',$line)){$row['class']='w_del';}
				else{
					$row['class']='';
				}
				$row['line']=encodeHtml($line);
				$recs[]=$row;
			}
			setView('git_diff',1);
			return;
		break;
		case 'log':
			$file=addslashes(base64_decode($_REQUEST['file']));
			$recs=gitCommand("log --max-count 10 \"{$file}\"",1);
			setView('git_log',1);
			return;
		break;
        default:
			gitFileInfo();
			setView('default',1);
        break;
	}
?>
