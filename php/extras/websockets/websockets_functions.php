<?php
//---------- begin function wsSendDBRecord--------------------
/**
* @describe sends a databas record to the websocket
* @param table string - tablename
* @param rec rec - record to send
* @return boolean
* @usage $ext=wsSendDBRecord($table,$rec);
*/
function wsSendDBRecord($table,$rec=array()){
	$fieldinfo=getDBFieldInfo($table);
	//echo printValue($fieldinfo);exit;
	$params=array();
	foreach($rec as $field=>$val){
		if(is_array($val)){continue;}
		if(!strlen($rec[$field])){continue;}
		$params[$field]='B64:'.base64_encode($rec[$field]);
	}
	$params['type']='table';
	//$msg=implode("<br />\n",$lines);
	$msg=json_encode($params);
	//echo $table.$msg;exit;
	$params['source']=isDBStage()?'db_stage':'db_live';
	$params['name']=$table;
	$params['icon']='icon-table w_grey';
	$ok=wsSendMessage($msg,$params);
	return $ok;
}
//---------- begin function wsTailFile--------------------
/**
* @describe tails a file and sends changes to the websocket
* @param file string - filename
* @return boolean
* @usage $ext=wsTailFile($filename);
*/
function wsTailFile($file){
    $size = filesize($file)-100;
    while (true) {
        clearstatcache();
        $currentSize = filesize($file);
        if ($size == $currentSize) {
            usleep(100);
            continue;
        }
        $fh = fopen($file, "r");
        fseek($fh, $size);
        $name=wsGetFileName($file);
        $params=array('source'=>'file','name'=>$name,'icon'=>'icon-file-txt');
        if(preg_match('/error/i',$name)){
			$params['icon'].= ' w_danger';
		}
        while ($d = fgets($fh)) {
            $ok=wsSendMessage($d,$params);
        }
        fclose($fh);
        $size = $currentSize;
    }
}
//---------- begin function wsGetFileName--------------------
/**
* @describe returns the filename of file, removing the path
* @param file string - name and path of file
* @param stripext boolean - strips the extension also - defaults to false
* @return string
* @usage $ext=wsGetFileName($afile);
*/
function wsGetFileName($file='',$stripext=0){
	$file=str_replace("\\",'/',$file);
	$tmp=preg_split('/[\/]/',$file);
	$name=array_pop($tmp);
	if($stripext){
		$stmp=explode('.',$name);
		array_pop($stmp);
		$name=implode('.',$stmp);
    	}
	return $name;
	}
//---------- begin function wsSendMessage---------------------------------------
/**
* Send data to websocket server.
* @param msg  string
* @param params array for host, port, origin
* @return boolean
* @usage $ok=wsSendMessage($message);
*/
function wsSendMessage($message,$params=array()){
	global $CONFIG;
	if(!isset($params['-host'])){
		if(isset($CONFIG['websocket_host'])){
			$params['-host']=$CONFIG['websocket_host'];
		}
		else{$params['-host']='127.0.0.1';}
	}
	if(!isset($params['-port'])){
		if(isset($CONFIG['websocket_port'])){
			$params['-port']=$CONFIG['websocket_port'];
		}
		else{$params['-port']='9300';}
	}
	if(!isset($params['-origin'])){$params['-origin']="http://{$params['-host']}";}
	if(!isset($params['name'])){$params['name']='wsSendMessage';}
	if(!isset($params['source'])){$params['source']='wasql';}
	if(!isset($params['icon'])){$params['icon']='icon-server w_warning';}
	$params['message']=$message;
	$data=array();
	foreach($params as $k=>$v){
    	if(!preg_match('/^\-(origin|port|host)/',$k)){
        	$data[$k]=$v;
		}
	}
	$data =json_encode($data);
	$data =wsHybi10Encode($data);
	$key = wsGenerateKey();
	//create the header needed to talk to the websocket server
	$head = "GET / HTTP/1.1\r\n".
        "Upgrade: websocket\r\n".
        "Connection: Upgrade\r\n".
        "Host: {$params['-host']}:{$params['-port']}\r\n".
        "Origin: {$params['-origin']}\r\n".
        "Sec-WebSocket-Key: {$key}\r\n".
        "Sec-WebSocket-Version: 13\r\n\r\n";
	if($sock = fsockopen($params['-host'], $params['-port'], $errno, $errstr)){
    	fwrite($sock, $head);
    	$header = fread($sock, 2000);
    	$lines=preg_split('/[\r\n]+/',trim($header));
    	$headers=array();
		foreach($lines as $line){
			$parts=preg_split('/\:/',trim($line),2);
			if(count($parts)==2){
				$headers[$parts[0]]=$parts[1];
			}
		}
		if(isset($headers['Sec-WebSocket-Accept']) && strlen($headers['Sec-WebSocket-Accept'])){
			if(!fwrite($sock, $data )){return false;}
			fclose($sock);
			return true;
		}
    	fclose($sock);
    	return false;
	}
	else{
    	echo "Unable to connect";
    	exit;
	}
}
//---------- begin function wsGenerateKey---------------------------------------
/**
* Generate a random string for WebSocket key.
* @return string Random string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsGenerateKey(){
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
    $key = '';
    $chars_length = strlen($chars);
    for ($i = 0; $i < 16; $i++){
		$key .= $chars[mt_rand(0, $chars_length-1)];
	}
    return base64_encode($key);
}
//---------- begin function wsHybi10Decode---------------------------------------
/**
* decode websocket data.
* @return string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsHybi10Decode($data){
    $bytes = $data;
    $dataLength = '';
    $mask = '';
    $coded_data = '';
    $decodedData = '';
    $secondByte = sprintf('%08b', ord($bytes[1]));
    $masked = ($secondByte[0] == '1') ? true : false;
    $dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);
    if($masked === true){
        if($dataLength === 126){
           $mask = substr($bytes, 4, 4);
           $coded_data = substr($bytes, 8);
        }
        elseif($dataLength === 127){
            $mask = substr($bytes, 10, 4);
            $coded_data = substr($bytes, 14);
        }
        else{
            $mask = substr($bytes, 2, 4);
            $coded_data = substr($bytes, 6);        
        }   
        for($i = 0; $i < strlen($coded_data); $i++){
            $decodedData .= $coded_data[$i] ^ $mask[$i % 4];
        }
    }
    else{
        if($dataLength === 126){
           $decodedData = substr($bytes, 4);
        }
        elseif($dataLength === 127){
            $decodedData = substr($bytes, 10);
        }
        else{
            $decodedData = substr($bytes, 2);
        }       
    }
    return $decodedData;
}
//---------- begin function wsHybi10Decode---------------------------------------
/**
* encode websocket data.
* @return string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsHybi10Encode($payload, $type = 'text', $masked = true) {
    $frameHead = array();
    $frame = '';
    $payloadLength = strlen($payload);
    switch ($type) {
        case 'text':
            // first byte indicates FIN, Text-Frame (10000001):
            $frameHead[0] = 129;
        break;
        case 'close':
            // first byte indicates FIN, Close Frame(10001000):
            $frameHead[0] = 136;
        break;
        case 'ping':
            // first byte indicates FIN, Ping frame (10001001):
            $frameHead[0] = 137;
        break;
        case 'pong':
            // first byte indicates FIN, Pong frame (10001010):
            $frameHead[0] = 138;
        break;
    }
    // set mask and payload length (using 1, 3 or 9 bytes)
    if ($payloadLength > 65535) {
        $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 255 : 127;
        for ($i = 0; $i < 8; $i++){
            $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
        }
        // most significant bit MUST be 0 (close connection if frame too big)
        if ($frameHead[2] > 127){
            $this->close(1004);
            return false;
        }
    }
	elseif($payloadLength > 125){
        $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 254 : 126;
        $frameHead[2] = bindec($payloadLengthBin[0]);
        $frameHead[3] = bindec($payloadLengthBin[1]);
    }
	else{
        $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
    }
    // convert frame-head to string:
    foreach (array_keys($frameHead) as $i){
        $frameHead[$i] = chr($frameHead[$i]);
    }
    if($masked === true){
        // generate a random mask:
        $mask = array();
        for ($i = 0; $i < 4; $i++){
            $mask[$i] = chr(rand(0, 255));
        }
        $frameHead = array_merge($frameHead, $mask);
    }
    $frame = implode('', $frameHead);
    // append payload to frame:
    for($i = 0; $i < $payloadLength; $i++){
        $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }
    return $frame;
}