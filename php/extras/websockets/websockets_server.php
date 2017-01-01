<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server class
$progpath=dirname(__FILE__);
require_once("{$progpath}/class.websockets.php");
global $logfile;
$logfile="{$progpath}/websockets_server.log";
if(is_file($logfile)){unlink($logfile);}
// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))

$Server->wsStartServer('0.0.0.0', 9300);


//---------- begin function wsOnMessage
/**
* @describe - when a client send data to the server
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	global $logfile;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}
	$json=@json_decode($message,true);
	foreach($json as $k=>$v){
		if(preg_match('/^B64\:(.+)$/i',$v,$m)){
	    	$json[$k]=base64_decode($m[1]);
		}
	}
	//only keep the last 9MB of logs
	if(is_file($logfile) && filesize($logfile) > 9000000){unlink($logfile);}
	//log
	file_put_contents($logfile,printValue($json),FILE_APPEND);
	//echo "json".printValue($json);
	//handle custom command messages
	if(preg_match('/^\/(.+)$/',$json['message'],$m)){
		$parts=preg_split('/\ +/',$m[1],2);
		$command=$parts[0];
		$cmd_msg=$parts[1];
		//echo "command:".printValue($parts);
		switch(strtolower($command)){
			case 'filter':
				if(preg_match('/^(.+?)\ (eq|ne|bw|ew|ct|nc|gt|lt|gte|lte)\ (.+)$/i',trim($cmd_msg),$m)){
					$Server->wsClients[$clientID]['filters'][]=array(
						'key'=>strtolower($m[1]),
						'op'	=> $m[2],
						'val'	=> $m[3]
					);
					$cnt=count($Server->wsClients[$clientID]['filters']);
					$msg="{$cnt} filters set.";
					$msg.='<ol>';
                	foreach($Server->wsClients[$clientID]['filters'] as $i=>$filter){
                    	$msg .= "<li>{$filter['key']} {$filter['op']} {$filter['val']}</li>";
					}
					$msg .= '</ol>';
					$Server->wsSend($clientID,$msg);
				}
				elseif(strtolower($cmd_msg)=='list'){
                	$msg='<ol>';
                	foreach($Server->wsClients[$clientID]['filters'] as $i=>$filter){
                    	$msg .= "<li>{$filter['key']} {$filter['op']} {$filter['val']}</li>";
					}
					$msg .= '</ol>';
                	$Server->wsSend($clientID,$msg);
				}
				elseif(strtolower($cmd_msg)=='clear'){
                	unset($Server->wsClients[$clientID]['filters']);
                	$Server->wsSend($clientID,"filters cleared");
				}
				else{
                	$Server->wsSend($clientID, "invalid filter '{$cmd_msg}'.  specify {key} {eq,ne,bw,ew,ct,nc,gt,lt,gte,lte} {value}. i.e. name=bob" );
				}
				return;
			break;
			case 'fields':
				$Server->wsClients[$clientID]['fields']=preg_split('/\,/',$cmd_msg);
				$Server->wsSend($clientID, "fields to display set to {$cmd_msg}");
				return;
			break;
	    	case 'who':
	    		$msg='';
	    		foreach ( $Server->wsClients as $id => $client ){
					if(isset($Server->wsClients[$id]['name'])){
						$name=$Server->wsClients[$id]['name'];
                    	$msg.= " - {$name}<br />";
					}
					else{
						$ip = long2ip( $client[6] );
						$msg.= " - Visitor {$clientID} ({$ip})<br />";
					}
	
				}
				$Server->wsSend($clientID, $msg);
				return;
	    	break;
			case 'name':
			case 'icon':
			case 'color':
			case 'class':
				$key=strtolower($command);
	    		$Server->wsClients[$clientID][$key]=$cmd_msg;
				$Server->wsSend($clientID, " - {$key} set to {$cmd_msg}");
				return;
	    	break;
	    	case 'help':
	    	default:
				$msg='Help<br>';
				$msg.=' - /who will show you who is on<br />';
				$msg.=' - /filter {key} {eq,ne,bw,ew,ct,nc,gt,lt,gte,lte} {value} will filter your messages<br />';
				$msg.=' - /filter clear will clear your filters<br />';
				$msg.=' - /filter list will list your filters<br />';
				$msg.=' - /name {name} will set your name<br />';
				$msg.=' - /icon {icon} will set your icon<br />';
				$msg.=' - /color {color} will set your color<br />';
				$msg.=' - /class {class} will set your class<br />';
				$msg.=' - /help will show this help message<br />';
				$Server->wsSend($clientID, $msg);
				return;
	    	break;
		}
	}
	//The speaker is the only person in the room. Don't let them feel lonely.
	if (sizeof($Server->wsClients) == 1){
		//$Server->wsSend($clientID, "You are the first one here.");
	}
	else{
		//Send the message to everyone but the person who said it
		//if the message is a json string, parse the user out:
		//icon
		if(isset($Server->wsClients[$clientID]['icon'])){
			$json['icon']=$Server->wsClients[$clientID]['icon'];
		}
		elseif(isset($Server->wsClients[$clientID]['color'])){
			$json['color']=$Server->wsClients[$clientID]['color'];
		}
		elseif(isset($Server->wsClients[$clientID]['class'])){
			$json['class']=$Server->wsClients[$clientID]['class'];
		}
		//name
		if(isset($Server->wsClients[$clientID]['name'])){
            $json['name']=$Server->wsClients[$clientID]['name'];
		}
		elseif(!isset($json['name'])){$json['name']="Visitor {$clientID} ({$ip})";}
		//message
		if(!isset($json['message'])){$json['message']=$message;}
		//prefix
		$prefix="{$json['name']}:";
		if(isset($json['icon'])){
            $prefix='<span class="'.$json['icon'].'"></span> '.$prefix;
		}
		elseif(isset($json['color'])){
            $prefix='<span class="icon-user" style="color:'.$json['color'].'"></span> '.$prefix;
		}
		elseif(isset($json['class'])){
            $prefix='<span class="icon-user '.$json['class'].'"></span> '.$prefix;
		}
		//prepend the time
		$msgdate=date('F j, Y, g:i a');
		$msgtime=date('g:i a');
		$prefix='<span style="margin-right:15px" title="'.$msgdate.'">'.$msgtime.'</span>'.$prefix;
		foreach ( $Server->wsClients as $id => $client ){
			if ( $id != $clientID ){
				//look for fields
				if(isset($Server->wsClients[$id]['fields'][0])){
					if(count($Server->wsClients[$id]['fields'])==1 && strtolower($Server->wsClients[$id]['fields'][0])=='all'){
						$Server->wsClients[$id]['fields']=array_keys($json);
					}
					$msg='<table class="table table-condensed table-striped">';
					$msg .= '<tr>';
					foreach($Server->wsClients[$id]['fields'] as $field){
						$msg .= "<th>{$field}</th>";
					}
					$msg .="</tr>";
					$msg .= '<tr>';
					foreach($Server->wsClients[$id]['fields'] as $field){
						$cval=isset($json[$field])?$json[$field]:'';
						$msg .= "<td>{$cval}</td>";
					}
					$msg.='</table>';
					$json['message']=$msg;
				}
				//look for filters
				if(isset($Server->wsClients[$id]['filters'])){
					//echo "Applying Filters for client {$id}\n";
                	$skip=0;
                	foreach($Server->wsClients[$id]['filters'] as $filter){
						$key=$filter['key'];
                    	switch($filter['op']){
                        	case 'eq':
                        		if(strtolower($json[$key])!=strtolower($filter['val'])){$skip++;}
                        	break;
                        	case 'ne':
                        		if(strtolower($json[$key])==strtolower($filter['val'])){$skip++;}
                        	break;
                        	case 'ct':
                        		if(!stringContains($json[$key],$filter['val'])){$skip++;}
                        	break;
                        	case 'nc':
                        		if(stringContains($json[$key],$filter['val'])){$skip++;}
                        	break;
                        	case 'bw':
                        		if(!stringBeginsWith($json[$key],$filter['val'])){$skip++;}
                        	break;
                        	case 'ew':
                        		if(!stringEndsWith($json[$key],$filter['val'])){$skip++;}
                        	break;
                        	case 'gt':
                        		if($json[$key] <= $filter['val']){$skip++;}
                        	break;
                        	case 'gte':
                        		if($json[$key] < $filter['val']){$skip++;}
                        	break;
                        	case 'lt':
                        		if($json[$key] >= $filter['val']){$skip++;}
                        	break;
                        	case 'lte':
                        		if($json[$key] > $filter['val']){$skip++;}
                        	break;
						}
					}
					if($skip > 0){continue;}
				}
				$Server->wsSend($id, "{$prefix} {$json['message']}");
			}
		}
	}
}
//---------- begin function stringContains
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function stringContains($string, $search){
	if(!strlen($string) || !strlen($search)){return false;}
	return strpos(strtolower($string),strtolower($search)) !== false;
}
//---------- begin function stringBeginsWith
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function stringBeginsWith($str='', $search=''){
	return (strncmp(strtolower($str), strtolower($search), strlen($search)) == 0);
}
//---------- begin function stringEndsWith
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function stringEndsWith($string, $search){
    $pos = strrpos(strtolower($string), strtolower($search)) === strlen($string)-strlen($search);
    if($pos === false){return false;}
	return true;
}
//---------- begin function printValue
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function printValue($v='',$exit=0){
	$type=strtolower(gettype($v));
	$plaintypes=array('string','integer');
	if(in_array($type,$plaintypes)){return $v;}
	$rtn='';
	if($exit != -1){$rtn .= '<pre class="w_times" type="'.$type.'">'."\n";}
	ob_start();
	print_r($v);
	$rtn .= ob_get_contents();
	ob_clean();
	if($exit != -1){$rtn .= "\n</pre>\n";}
    if($exit){echo $rtn;exit;}
	return $rtn;
}
//---------- begin function wsOnOpen
/**
* @describe - when a client connects
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsOnOpen($clientID){
	global $Server;
	$visitorCount=sizeof($Server->wsClients);
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	//$Server->log( "$ip ($clientID) has connected." );
	//Send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client ){
		if ( $id != $clientID ){
			//$Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
			//$Server->wsSend($id, "{$visitorCount} people are now in the room.");
		}
	}
}
//---------- begin function wsOnClose
/**
* @describe - when a client closes or lost connection
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wsOnClose($clientID, $status) {
	global $Server;
	$visitorCount=sizeof($Server->wsClients);
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	//$Server->log( "$ip ($clientID) has disconnected." );
	//Send a user left notice to everyone in the room
	foreach ( $Server->wsClients as $id => $client ){
		//$Server->wsSend($id, "Visitor $clientID ($ip) has left the room.");
		//$Server->wsSend($id, "{$visitorCount} people are now in the room.");
	}
}


?>