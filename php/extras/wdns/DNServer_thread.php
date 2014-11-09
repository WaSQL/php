<?
/*
**    DNServer (Public Domain)
**    Cesar Rodas <saddor@guaranix.org>
**
**    The Idea is to give a simple way to handle DNS querys and retrives an IP.
**    This project could be used as a DNS Trafic Balancer to mirrow sites with a 
**    close geografic position, of course with a IP2Country Module.
****************************************************************************************
**    La idea es dar una manera de manejar las peticiones de DNS y retornar un IP.
**    El proyecto puede ser usado como un Balanceador de Trafico hacia sitios espejos 
**    con una posicion geografica cercana, desde luego que con un modulo de IP2Country.
**
*/

/*
**	PAY ATENTION! THIS "THREADED" DNS USES THE FORK PROCESS. FORK IS ONLY AVALIABLE
**	ON UNIX LIKE OS (Linux, FreeBSD, MacOS, OpenBSD, and others).
*/	
class DNServer_Thread extends DNServer {
    
    function DNServer_thread($a,$b=NULL) {
		$this->DNServer($a,$b);
	}
	
	
    function HandleQuery($buf,$clientip,$clientport,$socket)
    {
    	$handler = new MultiThreaderHandler($buf,$clientip,$clientport, $this->types,$socket);
		$handler->run();	
    }
    
    
}


class MultiThreaderHandler extends PHP_Fork {
	var $buf;
	var $clientip;
	var $clientport;
	var $types;
	/**/
	var $socket;
	
	function MultiThreaderHandler($buf,$clientip,$clientport,$types,$socket) {
		$this->buf = $buf;
		$this->clientip = $clientip;
		$this->clientport = $clientport;
		$this->types = $types;
		$this->socket = $socket;
		$name = time() + microtime(); /* Generating micro time*/
		$this->PHP_Fork($name);
	}
	
	function run() {
		$buf=$this->buf;
		$clientip=$this->clientip;
		$clientport=$this->clientport;
		
		$dominio="";
        $tmp = substr($buf,12);
        $e=strlen($tmp);
        for($i=0; $i < $e; $i++)
        {
            $len = ord($tmp[$i]);
            if ($len==0)
                break;
            $dominio .= substr($tmp,$i+1, $len).".";
            $i += $len;
        }
        $i++;$i++; /* move two char */
        /* Saving the domain name as queried*/
        print $DomainAsQueried;
        
        $querytype = array_search((string)ord($tmp[$i]), $this->types ) ;
        
        $dominio = substr($dominio,0,strlen($dominio)-1);
        $callback = $this->func;
        $ips = $callback($dominio, $querytype);

        $answ = $buf[0].$buf[1].chr(129).chr(128).$buf[4].$buf[5].$buf[4].$buf[5].chr(0).chr(0).chr(0).chr(0);
        $answ .= $tmp;
        $answ .= chr(192).chr(12);
        $answ .= chr(0).chr(1).chr(0).chr(1).chr(0).chr(0).chr(0).chr(60).chr(0).chr(4);
        $answ .= $this->TransformIP($ips);

             

        if (socket_sendto($this->socket,$answ, strlen($answ), 0,$clientip, $clientport) === false)
            printf("Error in socket\n");    
	}
	
	function TransformIP($ip)
    {
        $nip="";
        foreach(explode(".",$ip) as $pip)
            $nip.=chr($pip);

        return $nip;
    }
}
?>