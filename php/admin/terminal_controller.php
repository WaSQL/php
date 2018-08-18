<?php
	global $params;
	global $CONFIG;
	loadExtrasJs('shortcuts');
	//load any defaults from CONFIG
	$keys=array('file','ws','sslcert','sslkey','port','host','loglevel','divid');
	foreach($keys as $key){
		if(!isset($params["-{$key}"]) && isset($CONFIG["websocketd_{$key}"])){
			$params["-{$key}"]=$CONFIG["websocketd_{$key}"];
		}	
	}
	//wss requires sslcert and sslkey
	if($params['-ws']=='wss'){
		//check for sslcert
		if(!isset($params['-sslcert'])){
			$error="sslcert must be specified. Either pass in -sslcert or set websocketd_sslcert in config.xml";
			setView('error',1);
			return;
		}
		if(!file_exists($params['-sslcert'])){
			$error="sslcert not found: {$params['-sslcert']} ";
			setView('error',1);
			return;
		}
		//check for sslkey
		if(!isset($params['-sslkey'])){
			$error="sslkey must be specified. Either pass in -sslkey or set websocketd_sslkey in config.xml";
			setView('error',1);
			return;
		}
		if(!file_exists($params['-sslkey'])){
			$error="sslkey not found: {$params['-sslkey']} ";
			setView('error',1);
			return;
		}
	}
	//set defaults if not set
	if(!isset($params['-ws'])){
		$params['-ws']='ws';
	}
	if(!isset($params['-host'])){
		$params['-host']=$_SERVER['SERVER_NAME'];
	}
	if(!isset($params['-port'])){
		$params['-port']=8090;
	}
	if(!isset($params['-divid'])){
		$params['-divid']='terminal_results';
	}
	if(!isset($params['-loglevel'])){
		$params['-loglevel']='error';
	}
	if(!isset($params['-shortcuts'])){
		$params['-shortcuts']=array(
			array('name'=>'ifconfig','cmd'=>'ifconfig'),
			array('name'=>'ipconfig','cmd'=>'ipconfig')
		);
	}
	//check for websocketd file
	//check for sslkey
	if(!isset($params['-file'])){
		$error="file (path to websocketd) must be specified. Either pass in -file or set websocketd_file in config.xml";
		setView('error',1);
		return;
	}
	if(!file_exists($params['-file'])){
		$error="file not found: {$params['-file']} ";
		setView('error',1);
		return;
	}
	//check to see if websocketd is running.  if not start it
	if(isWindows()){
    	$cmd="tasklist | find /I \"websocketd\"";
	}
	else{
		$cmd="ps aux|grep {$process}";
	}
	$out=cmdResults($cmd);
	if(!preg_match('/websocketd/i',$out['stdout'])){
		//not running. lets start it up
    	if(isWindows()){
    		$cmd="{$params['-file']} --port={$params['-port']} --loglevel={$params['-loglevel']} bash";
    		$error="websocketd is not running. Run this command to start it in a cmd prompt : {$cmd} ";
			setView('error',1);
			return;
    	}
    	else{
    		$cmd="{$params['-file']} --port={$params['-port']} --loglevel={$params['-loglevel']} bash > /dev/null 2>&1 &";
    	}
    	$out=cmdResults($cmd);
	}
	setView('default',1);
?>
