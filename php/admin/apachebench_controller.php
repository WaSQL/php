<?php
/*
	https://httpd.apache.org/docs/2.4/programs/ab.html
*/
switch(strtolower($_REQUEST['func'])){
	case 'run':
		$n=(integer)$_REQUEST['requests'];
		$c=(integer)$_REQUEST['concurrency'];
		$url=$_REQUEST['url'];
		$args=array('-w','-q',"-n {$n}","-c {$c}");
		$tpath=getWasqlTempPath();
		$pfile="{$tpath}/apachebench.txt";
		if(strlen($_REQUEST['payload'])){
			setFileContents($pfile,$_REQUEST['payload']);
			$args[]="-p \"{$pfile}\"";
			//set content-type
			$j=json_decode($_REQUEST['payload'],true);
			if(is_array($j)){
				$args[]="-T \"application/json\"";
			}
			else{
				$args[]="-T \"application/x-www-form-urlencoded\"";	
			}

		}
		if(strlen($_REQUEST['headers'])){
			$headers=preg_split('/[\r\n]+/',trim($_REQUEST['headers']));
			foreach($headers as $header){
				$args[]="-H \"{$header}\"";
			}
		}
		if(strlen($_REQUEST['proxy'])){
			$args[]="-X \"{$_REQUEST['proxy']}\"";
		}
		if(strlen($_REQUEST['proxy_auth'])){
			$args[]="-P \"{$_REQUEST['proxy_auth']}\"";
		}
		$args[]=$url;
		$argstr=implode(' ',$args);
		$cmd="ab {$argstr}";
		//echo $cmd;exit;
		$out=cmdResults($cmd);
		unlink($pfile);
		if(isset($out['stderr'])){
			$result=$out['stderr'];
			if(preg_match('/(.+?)Usage:/ism',$result,$m)){
				$result=trim($m[1]);
			}
			$result="ERROR: {$result}";
		}
		else{
			$result=$out['stdout'];
			if(preg_match('/..done(.+)$/ism',$result,$m)){
				$result=trim($m[1]);
				$result=str_replace('<table >','<table class="table table-striped" style="border:1px solid #ccc;">',$result);
				$result=str_replace(' bgcolor=white','',$result);
				$result=str_replace(' colspan=2',' colspan="2"',$result);
			}
		}
		setView('result',1);
	break;
	default:
		setView('default');
	break;
}
?>