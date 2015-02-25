<?php
	loadExtras('git');
	loadExtrasJs('codemirror');
	$gitpath=getWasqlPath();
	$config=gitConfigList($gitpath);
	$git=gitStatus($gitpath);
	if(isset($_REQUEST['file'])){
		$name=decodeBase64($_REQUEST['file']);
	}
	if(isset($_REQUEST['files']) && is_array($_REQUEST['files'])){
		$files=array();
    	foreach($_REQUEST['files'] as $i=>$file){
			$name=decodeBase64($file);
			$key='msg_'.sha1($name);
			$files[]=array(
				'name'	=> $name,
				'msg'	=> $_REQUEST[$key],
				'key'	=> $key
			);
		}
	}
	switch(strtolower($_REQUEST['func'])){
        case 'log':
        	setView('log',1);
			$log=gitLog($gitpath,$name);
			return;
		break;
		case 'diff':
			setView('diff',1);
			$diff=gitDiff($gitpath,$name);
			return;
		break;
		case 'pull':
			$pull=gitPull($gitpath);
			echo "Pull".printValue($pull);
		break;
		case 'add':
			if(is_array($files) && count($files)){
				$tmp=array();
				foreach($files as $file){$tmp[]=$file['name'];}
				$add=gitAdd($gitpath,$tmp);
				echo "Add".printValue($add);
			}
		break;
		case 'commit':
			if(is_array($files) && count($files)){
				$commit='';
				foreach($files as $file){
					$file['msg']=trim($file['msg']);
					if(strlen($file['msg'])){
						$log=gitCommit($gitpath,$file['msg'],$file['name']);
						$commit.=nl2br($long);
					}
					else{
                    	$commit.="ERROR: Missing Message for {$file['name']}<br>\n";
					}

				}
				echo "Commit".printValue($commit);
				$git=gitStatus($gitpath);
				setView('default',1);
				return;
			}
		break;
		case 'push':
			$push=gitPush($gitpath);
			echo "Push".printValue($push);
		break;
		case 'commit_push':
			if(is_array($files) && count($files)){
				$commit='';
				foreach($files as $file){
					$file['msg']=trim($file['msg']);
					if(!strlen($file['msg']) && isset($_REQUEST['msg']) && strlen($_REQUEST['msg'])){
                    	$file['msg']=$_REQUEST['msg'];
					}
					if(strlen($file['msg'])){
						$log=gitCommit($gitpath,$file['msg'],$file['name']);
						$commit.=nl2br($long);
					}
					else{
                    	$commit.="ERROR: Missing Message for {$file['name']}<br>\n";
					}

				}
				echo "Commit".printValue($commit);
				$git=gitStatus($gitpath);
			}
			$push=gitPush($gitpath);
			echo "Push".printValue($push);
			$git=gitStatus($gitpath);
			setView('default',1);
		break;
	}
	setView('default',1);
	//echo printValue($status);exit;
	//$status=gitPull('d:/wasql');
	//echo printValue($status);exit;
?>
