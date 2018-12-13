<?php
	if(isset($_REQUEST['requests'])){

		$n=(integer)$_REQUEST['requests'];
		$c=(integer)$_REQUEST['concurrency'];
		$url=$_REQUEST['url'];
		$cmd="ab -w -q -n {$n} -c {$c} {$url}";
		//echo $cmd;exit;
		$out=cmdResults($cmd);
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
	}
	else{
		setView('default');
	}
?>