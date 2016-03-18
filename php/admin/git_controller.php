<?php
/*
	Run the following to fix this error: cannot open .git/FETCH_HEAD: Permission denied
		sudo chmod g+w .git/FETCH_HEAD
*/
	loadExtras('git');
	loadExtrasJs('codemirror');
	//allow them to change the git path
	if(isset($_REQUEST['gitpath']) && is_dir($_REQUEST['gitpath'])){
    	$_SESSION['gitpath']=$_REQUEST['gitpath'];
	}
	if(isset($_SESSION['gitpath']) && is_dir($_SESSION['gitpath'])){
    	$gitpath=$_SESSION['gitpath'];
	}
	else{$gitpath=getWasqlPath();}
	$config=gitConfigList($gitpath);
	$git=gitStatus($gitpath);
	//echo printValue($git);exit;
	if(isset($_REQUEST['file'])){
		$name=decodeBase64($_REQUEST['file']);
	}
	if(isset($_REQUEST['sort'])){
		$git['files']=sortArrayByKey($git['files'],$_REQUEST['sort']);
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
			$diff['rows']=array();
			foreach($diff['raw'] as $line){
				$row=array();
				if(preg_match('/^\+/',$line)){
					$row['class']='w_ins';
				}
				elseif(preg_match('/^\-/',$line)){$row['class']='w_del';}
				else{
					$row['class']='';
				}
				$row['line']=encodeHtml($line);
				$diff['rows'][]=$row;
			}
			return;
		break;
		case 'pull':
			$pull=gitPull($gitpath);
			$git['response']=$pull;
		break;
		case 'add':
			if(is_array($files) && count($files)){
				$tmp=array();
				foreach($files as $file){$tmp[]=$file['name'];}
				$add=gitAdd($gitpath,$tmp);
			}
			$git=gitStatus($gitpath);
			setView('default',1);
			return;
		break;
		case 'checkout':
			if(is_array($files) && count($files)){
				$list=array();
				foreach($files as $file){$list[]=$file['name'];}
				$ok=gitCheckout($gitpath,$list);
				$git=gitStatus($gitpath);
				setView('default',1);
				return;
			}
		break;
		case 'commit':
			$response='';
			if(is_array($files) && count($files)){
				$commit='';
				foreach($files as $file){
					$file['msg']=trim($file['msg']);
					if(strlen($file['msg'])){
						$log=gitCommit($gitpath,$file['msg'],$file['name']);
						$response.=nl2br($long);
					}
					else{
                    	$response.="ERROR: Missing Message for {$file['name']}<br>\n";
					}

				}
				$git=gitStatus($gitpath);
				$git['response']=$response;
				setView('default',1);
				return;
			}
		break;
		case 'push':
			$push=gitPush($gitpath);
			$git['response']=$push;
		break;
		case 'commit_push':
			$response='';
			if(is_array($files) && count($files)){
				foreach($files as $file){
					$file['msg']=trim($file['msg']);
					if(!strlen($file['msg']) && isset($_REQUEST['msg']) && strlen($_REQUEST['msg'])){
                    	$file['msg']=$_REQUEST['msg'];
					}
					if(strlen($file['msg'])){
						$log=gitCommit($gitpath,$file['msg'],$file['name']);
						$response.=nl2br($long);
					}
					else{
                    	$response.="ERROR: Missing Message for {$file['name']}<br>\n";
					}

				}
				$git=gitStatus($gitpath);
			}
			$push=gitPush($gitpath);
			$response.= $push;
			$git=gitStatus($gitpath);
			$git['response']=$response;
			setView('default',1);
		break;
	}
	setView('default',1);
	//echo printValue($status);exit;
	//$status=gitPull('d:/wasql');
	//echo printValue($status);exit;
?>
