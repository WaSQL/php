<?php
/*
	whois-servers.txt can be found at http://www.nirsoft.net/whois-servers.txt
		txt file of all WHOIS servers (port 43) which provide information about registered domains. This list contains the WHOIS servers for generic domains (For example: .com, .org, .net, and so on) as well as for country code domains (For example: .sk, .pl, .it, .de, and so on...)
*/
function whoisServer($tld){
	$progpath=dirname(__FILE__);
	$file="{$progpath}/whois/whois-servers.txt";
	if ($fh = fopen($file,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$line = trim(stream_get_line($fh, 1000000, "\n"));
			if(stringBeginsWith($line,';')){continue;}
			list($line_tld,$server)=preg_split('/\ /',$line,2);
			if(strtolower($line_tld)==strtolower($tld)){return $server;}
		}
		fclose($fh);
	}
	return null;
}
function whoisServers(){
	$progpath=dirname(__FILE__);
	$file="{$progpath}/whois/whois-servers.txt";
	$list=array();
	if ($fh = fopen($file,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$line = trim(stream_get_line($fh, 1000000, "\n"));
			if(stringBeginsWith($line,';')){continue;}
			list($line_tld,$server)=preg_split('/\ /',$line,2);
			$list[$line_tld]=$server;
		}
		fclose($fh);
	}
	return $list;
}
function whoisCheckDomain($domain){
    // Get the domain without http:// and www.
    $requestTimeout=3;
    $domain = trim($domain);
    preg_match('@^(http://www\.|http://|www\.)?([^/]+)@i', $domain, $matches);
    $domain = $matches[2];
    $info=array();
	$info['domain']=$domain;
    // Get the tld
    $tld = preg_split('/\./', $domain,2);
    $info['tld'] = strtolower(trim($tld[1]));
    //get the whois server for this tld
    $info['whois_server'] = whoisServer($info['tld']);

    if(!strlen($info['whois_server'])){
		$info['error']='Unsupported tld';
		return $info;
	}
	//get data from the whois server for this domain
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "{$info['whois_server']}:43"); // Whois Server
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$info['whois_request']="{$domain}\r\n";
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $info['whois_request']); // Query
	$data = curl_exec ($ch); 
	curl_close($ch);
	$data=trim($data);
	if(
		stringContains($data,'invalid request') || 
		stringContains($data,'Invalid query') ||
		stringContains($data,'Neither object nor interpretation') ||
		stringContains($data,'Query is invalid')
		){
		// connect to whois server a different way
		$data='';
	    if ($conn = fsockopen ($info['whois_server'], 43)) {
	    	fputs($conn, $domain."\r\n");
	        while(!feof($conn)) {
	            $data .= fgets($conn,128);
	        }
	        fclose($conn);
	    }
	}
	if(!strlen($data)){
    	$info['error']="No data returned from {$info['whois_server']}";
		return $info;
	}
	$info['status']='available';
	$lines=preg_split('/[\r\n]+/',$data);
	foreach($lines as $line){
		$line=trim($line);
		if(stringBeginsWith($line,';')){continue;}
		if(stringBeginsWith($line,'%')){continue;}
		//registrar
		if(!isset($info['registrar']) && !preg_match('/(technical|support)/i',$line) && (preg_match('/(Registrar|Registration Service Provider)/i',$line) || isset($info['registrar_nextline']))){
        	$parts=preg_split('/\:/',$line,2);
        	if(strlen(trim($parts[1]))){
				$str=trim($parts[1]);
        		unset($info['registrar_nextline']);
        		unset($info['dns_nextline']);
        		if(strlen($str) < 40 && !stringEndsWith($parts[0],'http')){
	        		$info['registrar']=$str;
				}
			}
			elseif(isset($info['registrar_nextline']) && strlen(trim($parts[0]))){
				$str=trim($parts[0]);
				unset($info['dns_nextline']);
	        	unset($info['registrar_nextline']);
				if(strlen($str) < 40 && !stringEndsWith($parts[0],'http')){
	        		$info['registrar']=$str;
				}
			}
			else{$info['registrar_nextline']=1;}
		}
		//dns
		if(!isset($info['dns']) && (preg_match('/(Nombres de Dominio|Domain servers in listed order|dns|nsserver|nserver|name server|DNS Servers|Name servers|nameserver|Nameservers|NAME SERVER INFORMATION|dns_name)[\:\]\#]/i',$line) || isset($info['dns_nextline']))){
        	$parts=preg_split('/[\:\#\]]/',$line,2);
        	if(strlen(trim($parts[1]))){
        		$info['dns']=trim($parts[1]);
        		unset($info['dns_nextline']);
        		unset($info['registrar_nextline']);
			}
			elseif(isset($info['dns_nextline']) && strlen(trim($parts[0]))){
        		$info['dns']=trim($parts[0]);
        		unset($info['dns_nextline']);
        		unset($info['registrar_nextline']);
			}
			else{$info['dns_nextline']=1;}
		}
		elseif(!isset($info['dns']) && preg_match('/^dns_name/i',$line)){
        	$parts=preg_split('/\ +/',$line,2);
        	if(strlen(trim($parts[1]))){
        		$info['dns']=trim($parts[1]);
        		unset($info['dns_nextline']);
        		unset($info['registrar_nextline']);
			}
		}
		//Owner
		if(preg_match('/^(Owner|Owner)\ *+\:/i',$line) || preg_match('/Domain Name ID\:/i',$line)){
        	$info['status']='taken';
		}
		if(preg_match('/quota exceeded/i',$line)){
        	$info['status']='unknown';
		}
		//create date
		if(!isset($info['create_date']) && preg_match('/(Fecha de Creacion|Creation Date|Create Date|activated on|Created On|Created|Registered|Registration Date|Commencement Date|registration|Date de creation)[\:\#]/i',$line)){
        	$parts=preg_split('/[\:\#]+/',$line,2);
        	if(isset($parts[1])){
	        	$datestr=str_replace('.','-',$parts[1]);
	        	$datestr=preg_replace('/^[\-\ ]+/','',$datestr);
	        	$info['create_date']=date('Y-m-d',strtotime($datestr));
			}
		}
		elseif(!isset($info['create_date']) && preg_match('/Creation Date \(dd\/mm\/yyyy\)\:/i',$line)){
        	$parts=preg_split('/\:/',$line,2);
        	$dateparts=preg_split('/\//',trim($parts[1]));
        	$info['create_date']=date('Y-m-d',strtotime("{$dateparts[2]}-{$dateparts[1]}-{$dateparts[0]}"));
		}
		elseif(!isset($info['create_date']) && preg_match('/Acivated.+?\:/i',$line)){
        	$parts=preg_split('/\:/',$line,2);
        	$info['create_date']=date('Y-m-d',strtotime(trim($parts[1])));
		}
		elseif(!isset($info['create_date']) && preg_match('/^Record created on(.+)$/i',$line,$m)){
        	$parts=preg_split('/\ +/',trim($m[1]));
        	if(strlen(trim($parts[0]))){
        		$info['create_date']=date('Y-m-d',strtotime(str_replace('.','-',$parts[0])));
			}
		}
		//update date
		if(!isset($info['update_date']) && preg_match('/(Update Date|Last Update|Updated On|Updated|Updated Date|Changed)\)*\:/i',$line)){
        	$parts=preg_split('/\:/',$line,2);
        	$info['update_date']=date('Y-m-d',strtotime(str_replace('.','-',$parts[1])));
		}
		elseif(!isset($info['update_date']) && preg_match('/^Last-update/i',$line)){
        	$parts=preg_split('/\ +/',$line,2);
        	if(strlen(trim($parts[1]))){
        		$info['update_date']=trim($parts[1]);
			}
		}
		//expire date
		if(!isset($info['expire_date']) && preg_match('/(expire on|Expiration Date|expires at|Expire Date|Expired|Paid\-Till|Expiry Date|Expiry\ |Expires|renewal|Expires On)\:/i',$line)){
        	$parts=preg_split('/\:/',$line,2);
        	$info['expire_date']=date('Y-m-d',strtotime(str_replace('.','-',$parts[1])));
		}
		elseif(!isset($info['expire_date']) && preg_match('/Expiration Date \(dd\/mm\/yyyy\)\:/i',$line)){
        	$parts=preg_split('/\:/',$line,2);
        	$dateparts=preg_split('/\//',trim($parts[1]));
        	$info['expire_date']=date('Y-m-d',strtotime("{$dateparts[2]}-{$dateparts[1]}-{$dateparts[0]}"));
		}
		elseif(!isset($info['expire_date']) && preg_match('/^Valid-date/i',$line)){
        	$parts=preg_split('/\ +/',$line,2);
        	if(strlen(trim($parts[1]))){
        		$info['expire_date']=trim($parts[1]);
			}
		}
		elseif(!isset($info['expire_date']) && preg_match('/^Record expires on(.+)$/i',$line,$m)){
        	$parts=preg_split('/\ +/',trim($m[1]));
        	if(strlen(trim($parts[0]))){
        		$info['expire_date']=date('Y-m-d',strtotime(str_replace('.','-',$parts[0])));
			}
		}
	}
	if(
		isset($info['expire_date']) || 
		isset($info['update_date']) || 
		isset($info['create_date']) || 
		isset($info['registrar']) || 
		isset($info['dns'])
		){
		$info['status']='taken';
	}
	$info['whois_lines']=$lines;
	return $info;
}
?>