<?php
/*
	https://httpd.apache.org/docs/2.4/programs/ab.html
*/
switch(strtolower($_REQUEST['func'])){
	case 'run':
		// Validate and sanitize inputs
		$n=(integer)$_REQUEST['requests'];
		$c=(integer)$_REQUEST['concurrency'];

		// Validate numeric inputs
		if($n <= 0 || $n > 100000){
			$result="ERROR: Total requests must be between 1 and 100000";
			setView('result',1);
			break;
		}
		if($c <= 0 || $c > 1000){
			$result="ERROR: Concurrent requests must be between 1 and 1000";
			setView('result',1);
			break;
		}

		// Validate URL
		$url=trim($_REQUEST['url']);
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			$result="ERROR: Invalid URL format";
			setView('result',1);
			break;
		}

		// Parse URL to validate scheme
		$urlParts=parse_url($url);
		if(!isset($urlParts['scheme']) || !in_array(strtolower($urlParts['scheme']), array('http','https'))){
			$result="ERROR: URL must use http or https protocol";
			setView('result',1);
			break;
		}

		// Build command arguments using escapeshellarg for security
		$args=array('-w','-q');
		$args[]='-n '.escapeshellarg($n);
		$args[]='-c '.escapeshellarg($c);

		$tpath=getWasqlTempPath();
		$pfile="{$tpath}/apachebench.txt";

		if(strlen($_REQUEST['payload'])){
			setFileContents($pfile,$_REQUEST['payload']);
			$args[]='-p '.escapeshellarg($pfile);
			//set content-type
			$j=json_decode($_REQUEST['payload'],true);
			if(is_array($j)){
				$args[]='-T '.escapeshellarg('application/json');
			}
			else{
				$args[]='-T '.escapeshellarg('application/x-www-form-urlencoded');
			}
		}

		if(strlen($_REQUEST['headers'])){
			$headers=preg_split('/[\r\n]+/',trim($_REQUEST['headers']));
			foreach($headers as $header){
				$header=trim($header);
				if(strlen($header)){
					// Validate header format
					if(preg_match('/^[a-zA-Z0-9\-]+:\s*.+$/',$header)){
						$args[]='-H '.escapeshellarg($header);
					}
				}
			}
		}

		if(strlen($_REQUEST['basic_auth'])){
			$basicAuth=trim($_REQUEST['basic_auth']);
			// Validate basic auth format (username:password)
			if(preg_match('/^[^:]+:.+$/',$basicAuth)){
				$args[]='-A '.escapeshellarg($basicAuth);
			}
		}

		if(strlen($_REQUEST['proxy'])){
			$proxy=trim($_REQUEST['proxy']);
			// Validate proxy format (server:port)
			if(preg_match('/^[a-zA-Z0-9\.\-]+:\d+$/',$proxy)){
				$args[]='-X '.escapeshellarg($proxy);
			}
		}

		if(strlen($_REQUEST['proxy_auth'])){
			$proxyAuth=trim($_REQUEST['proxy_auth']);
			// Validate proxy auth format (username:password)
			if(preg_match('/^[^:]+:.+$/',$proxyAuth)){
				$args[]='-P '.escapeshellarg($proxyAuth);
			}
		}

		$args[]=escapeshellarg($url);
		$argstr=implode(' ',$args);
		$cmd="ab {$argstr}";

		$out=cmdResults($cmd);

		// Clean up temp file if it exists
		if(is_file($pfile)){
			unlink($pfile);
		}

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