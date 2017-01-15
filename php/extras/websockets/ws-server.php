<?php
/*
	http://stackoverflow.com/questions/16979793/php-ratchet-websocket-ssl-connect
	This requires ratchet to be installed
	http://socketo.me/docs/install
	curl -sS https://getcomposer.org/installer | sudo php --  --filename=composer
	php composer require cboden/ratchet
*/
$progpath=dirname(__FILE__);
require_once "{$progpath}/../../common.php";
require 'vendor/autoload.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
date_default_timezone_set('UTC');
/* define the chat class before calling it */
class waChat implements MessageComponentInterface {
    protected $clients;
    //define log file
    protected $logfile;
    protected $clientinfo;


    public function __construct() {
        $this->clients = new \SplObjectStorage;
		$this->clientinfo=array();
        $progpath=dirname(__FILE__);
        $this->logfile="{$progpath}/ws-server.log";
		if(is_file($this->logfile)){unlink($this->logfile);}
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        //echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        $json=@json_decode($msg,true);
        if(!is_array($json)){
        	$json=array('message'=>$msg);
		}
		$clientid=$from->resourceId;
		if(!isset($this->clientinfo[$clientid])){
	        $this->clientinfo[$clientid]['remote_addr']=$from->remoteAddress;
			// or if you're behind a proxy:
			$this->clientinfo[$clientid]['forward_addr']=$from->WebSocket->request->getHeader('X-Forwarded-For');
			$tmp=(array)$from->WebSocket->request->getHeader('User-Agent');
			foreach($tmp as $tmpx){
	        	$this->clientinfo[$clientid]['user_agent']=$tmpx[0];
	        	break;
			}
			if(isset($this->clientinfo[$clientid]['user_agent'])){
				$agent=getAgentBrowser($this->clientinfo[$clientid]['user_agent']);
				$this->clientinfo[$clientid]['remote_browser']=$agent['browser'];
				$this->clientinfo[$clientid]['remote_browser_version']=$agent['version'];
				$jsonthis->clientinfo[$clientid]['remote_os']=getAgentOS($this->clientinfo[$clientid]['user_agent']);
				$agentlang=strtolower(getAgentLang($this->clientinfo[$clientid]['user_agent']));
				if(preg_match('/^([a-z]{2,2})\-([a-z]{2,2})$/i',$agentlang,$m)){
		    		$this->clientinfo[$clientid]['remote_lang']=$m[1];
		    		$this->clientinfo[$clientid]['remote_country']=$m[2];
				}
			}
			//joined date
			$this->clientinfo[$clientid]['joined']=date('Y-m-d H:i:s');
	        //add create date
	        $this->clientinfo[$clientid]['id']=$from->resourceId;
		}
		foreach($this->clientinfo[$clientid] as $k=>$v){
        	$json["-{$k}"]=$v;
		}
        $json['-send_count']=count($this->clients) - 1;
		foreach($json as $k=>$v){
			if(is_array($v)){continue;}
			if(preg_match('/^B64\:(.+)$/i',$v,$m)){
		    	$json[$k]=base64_decode($m[1]);
			}
		}
		ksort($json);
		//echo printValue($json);exit;
        //Log the message but only keep the last 9MB of logs
        $logmsg=json_encode($json);
		if(is_file($this->logfile) && filesize($this->logfile) > 9000000){unlink($this->logfile);}
		file_put_contents($this->logfile,"{$logmsg}\r\n",FILE_APPEND);
		//look for custom commands
		if(isset($json['message']) && preg_match('/^\/(.+)$/',$json['message'],$m)){
			$parts=preg_split('/\ +/',$m[1],2);
			$command=$parts[0];
			if(isset($parts[1])){$cmd_msg=$parts[1];}
			//echo "command:".printValue($parts);
			switch(strtolower($command)){
				case 'filter':
					if(preg_match('/^(.+?)\ (eq|ne|bw|ew|ct|nc|gt|lt|gte|lte)\ (.+)$/i',trim($cmd_msg),$m)){
						$this->clientinfo[$clientid]['filters'][]=array(
							'key'=>strtolower($m[1]),
							'op'	=> $m[2],
							'val'	=> $m[3]
						);
						$cnt=count($this->clientinfo[$clientid]['filters']);
						$msg="{$cnt} filters set.";
						$msg.='<ol>';
	                	foreach($this->clientinfo[$clientid]['filters'] as $i=>$filter){
	                    	$msg .= "<li>{$filter['key']} {$filter['op']} {$filter['val']}</li>";
						}
						$msg .= '</ol>';
						$from->send($msg);
					}
					elseif(strtolower($cmd_msg)=='list'){
	                	$msg='<ol>';
	                	foreach($this->clientinfo[$clientid]['filters'] as $i=>$filter){
	                    	$msg .= "<li>{$filter['key']} {$filter['op']} {$filter['val']}</li>";
						}
						$msg .= '</ol>';
	                	$from->send($msg);
					}
					elseif(strtolower($cmd_msg)=='clear'){
	                	unset($this->clientinfo[$clientid]['filters']);
	                	$from->send("filters cleared");
					}
					else{
	                	$from->send("invalid filter '{$cmd_msg}'.  specify {key} {eq,ne,bw,ew,ct,nc,gt,lt,gte,lte} {value}. i.e. name=bob" );
					}
					return;
				break;
				case 'fields':
					$this->clientinfo[$clientid]['fields']=preg_split('/\,/',$cmd_msg);
					$from->send("fields to display set to {$cmd_msg}");
					return;
				break;
		    	case 'who':
		    		$msg='<ol>';
		    		foreach ($this->clients as $client) {
	            		if ($from !== $client) {
							$clientid=$client->resourceId;
							if(isset($this->clientinfo[$clientid]['name'])){
								$name=$this->clientinfo[$clientid]['name'];
								$msg.= "<li>{$name}</li>";
							}
							else{
								$ip=$this->clientinfo[$clientid]['remote_addr'];
								$id=$this->clientinfo[$clientid]['id'];
								$msg.= "<li> #{$id} at {$ip}</li>";
							}
						}
					}
					$msg.='</ol>';
					$from->send($msg);
					return;
		    	break;
				case 'name':
				case 'icon':
				case 'color':
				case 'class':
					$key=strtolower($command);
		    		$this->clientinfo[$clientid][$key]=$cmd_msg;
					$from->send(" - {$key} set to {$cmd_msg}");
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
					$from->send($msg);
					return;
		    	break;
			}
		}

		//send message to others
		$msgdate=date('F j, Y, g:i a');
		$msgtime=date('g:i a');
		$datestamp='<span style="margin-right:15px" title="'.$msgdate.'">'.$msgtime.'</span>';
		//determine ifon
		if(isset($this->clientinfo[$clientid]['icon'])){$icon=$this->clientinfo[$clientid]['icon'];}
		elseif(isset($json['icon'])){$icon=$json['icon'];}
		elseif(isset($json['source'])){$icon=$json['source'];}
		else{$icon="question";}
		if(!preg_match('/^icon\-/',$icon)){$icon="icon-{$icon}";}
		$icon='<span class="'.$icon.'"></span>';
		if(isset($this->clientinfo[$clientid]['name'])){
			$name=$this->clientinfo[$clientid]['name'];
			$icon.=" {$name}";
		}
        foreach ($this->clients as $client) {
            if ($from !== $client) {
				$clientid=$client->resourceId;
				$info=$this->clientinfo[$clientid];
				//check for filters
				if(isset($info['filters'])){
					//echo "Applying Filters for client {$id}\n";
                	$skip=0;
                	foreach($info['filters'] as $filter){
						$key=$filter['key'];
						if(!isset($json[$key])){continue;}
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
				//determine icon
				$message=isset($json['message'])?$json['message']:json_encode($json);
				if(isset($json['type']) && in_array($json['type'],array('table','access'))){
					//echo "Table changed\r\n";
					if(!isset($info['fields']) || (count($info['fields'])==1 && strtolower($info['fields'][0])=='all')){
                    	$fields=array_keys($json);
					}
					else{$fields=$info['fields'];}
					//echo "fields".json_encode($fields)."\r\n";
					$message='<table class="table table-condensed table-striped">';
					$message .= '<tr>';
					foreach($fields as $field){
						if(preg_match('/^\-/',$field)){continue;}
						$message .= "<th>{$field}</th>";
					}
					$message .="</tr>";
					$message .= '<tr>';
					foreach($fields as $field){
						if(preg_match('/^\-/',$field)){continue;}
						$cval=isset($json[$field])?$json[$field]:'';
						$message .= "<td>{$cval}</td>";
					}
					$message.='</table>';
					//echo $message;
				}
                $client->send("{$icon} {$datestamp} {$message}");
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        $clientid=$conn->resourceId;
		unset($this->clientinfo[$clientid]);
        //echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(new HttpServer(new WsServer(new waChat())),9300);
$server->run();

