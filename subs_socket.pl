#subs_socket.pl
#Note: requires subs_common.pl 
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
###############
#http://www.perlpod.com/5.8.2/ext/Socket/Socket.html
use Socket;
use MIME::Base64;
#ppm install http://trouchelle.com/ppm/Digest-SHA.ppd
use Digest::SHA1  qw(sha1 sha1_hex sha1_base64);
###################
sub getContentType{
	my $file=shift;
	my %mimeTypes = (
		'bin' => 'application/octet-stream',
		'aif' => 'audio/x-aiff',
		'aiff'=> 'audio/x-aiff',
		'au'  => 'audio/basic',
		'avi' => 'video/x-msvideo',
		'doc' => 'application/msword',
		'dv'  => 'video/x-dv',
		'eps' => 'application/postscript',
		'gz'  => 'application/x-gzip',
		'hqx' => 'application/mac-binhex40',
		'htm' => 'text/html',
		'jar' => 'application/java-archive',
		'jpg' => 'image/jpeg',
		'lzh' => 'application/x-lzh',
		'm4a' => 'audio/mp4a-latm',
		'mid' => 'audio/midi',
		'mov' => 'video/quicktime',
		'mp2' => 'audio/mpeg',
		'mp3' => 'audio/mpeg',
		'mpg' => 'video/mpeg',
		'pdf'  => 'application/pdf',
		'ppt' => 'application/vnd.ms-powerpoint',
		'ps'  => 'application/postscript',
		'rm'  => 'audio/x-pn-realaudio',
		'shtml' => 'text/html',
		'snd' => 'audio/basic',
		'svg' => 'image/svg+xml',
		'swf' => 'application/x-shockwave-flash',
		'tar' => 'application/x-tar',
		'tex' => 'application/x-tex',
		'tif' => 'image/tiff',
		'txt' => 'text/plain',
		'vrml'=> 'model/vrml',
		'wav' => 'audio/x-wav',
		'wbmp'=> 'image/vnd.wap.wbmp',
		'wpd' => 'application/wordperfect',
		'wrl' => 'model/vrml',
		'xbm' => 'image/x-xbitmap',
		'xhtml' => 'text/html',
		'xls' => 'application/vnd.ms-excel',
		'xpm' => 'image/x-xpixmap',
		'zip' => 'application/x-zip-compressed',
		);
	my $ext=lc(getFileExtension($file));
	#print "File: $file\n";
	#print "Ext: $ext\n";
	if($mimeTypes{$ext}){return $mimeTypes{$ext};}
	elsif(-T $file){
		#text file
		return "text/$ext";
    	}
    elsif(-B $file){
		#Binary File
		if($ext=~/^(gif|png|jpeg|jpg|bmp|pict|tiff)$/is){return "image/$ext";}
		elsif($ext=~/^(midi|mpeg)$/is){return "audio/$ext";}
		elsif($ext=~/^(mp4)$/is){return "video/$ext";}
		return "application/$ext";
    	}
    return "application/unknown";
	}
###################
sub getFileContentId{
	#info: returns a unique content ID based on the shaw hash - 40 char hexadecimal number
	my $file=shift;
	my $name=getFileName($file);
	$shaw=sha1_hex(getFileContents($file));
	return $name . '@'. $shaw;
	}
###################
sub getInternetIP{
	#Return the first IP that has an internet gateway
	my $hostname = shift || `hostname`;
	$hostname=strip($hostname);
	my $ip_address='';
	my ($name,$aliases,$addrtype,$length,@addrs)=gethostbyname($hostname);
	foreach my $ip_addr (@addrs){
	     my @tmp = unpack('C4',$ip_addr);
	     $ip_address=join('.',@tmp);
	     next if $ip_address=~/^169\.254/s;
	     #print "checking $ip_address\n";
	    my ($sock,$err) = connectToHost("www.google.com",80,$ip_addr);
	    if(!$err){last;}
	    }
	return $ip_address;
	}
###################
sub getNetworkIPs{
	#Returns the IPs of all nics
	my $hostname = shift || `hostname`;
	$hostname=strip($hostname);
	my $ip_address='';
	my @ips=();
	my ($name,$aliases,$addrtype,$length,@addrs)=gethostbyname($hostname);
	foreach my $ip_addr (@addrs){
	     my @tmp = unpack('C4',$ip_addr);
	     my $ip_address=join('.',@tmp);
	     push(@ips,$ip_address);
	    }
	if(wantarray){return @ips;}
	return $ips[0];
	}
##################
sub connectToHost {
	# Create a socket that connects to a certain host
	# connectToHost($MainSock, $remote_hostname, $port)
	my ($remote_hostname, $port) = @_;
	my $Sock;
	#print "connectToHost ($remote_hostname, $port)\n";
	my ($socket_format, $proto, $packed_port, $cur);
	my ($remote_addr, @remote_ip, $remote_ip);
	my ($local_port, $remote_port);
	if ($port !~ /^\d+$/) {
		$port = (getservbyname($port, "tcp"))[2];
		$port = 80 unless ($port);
		}
	$proto = (getprotobyname('tcp'))[2];
	$remote_addr = (gethostbyname($remote_hostname))[4];
	if (!$remote_addr) {
		return (undef,"Unknown host: $remote_hostname");
		}
	@remote_ip = unpack("C4", $remote_addr);
	$remote_ip = join(".", @remote_ip);
	#print STDOUT "Connecting to $remote_ip port $port.\r\n";
	$socket_format = 'S n a4 x8';
	$local_port = pack($socket_format, &AF_INET, 0, $SocketInfo{hostaddr});
	$remote_port = pack($socket_format, &AF_INET, $port, $remote_addr);
	socket($Sock, &AF_INET, &SOCK_STREAM, $proto) || return (undef,"Socket Error: $!");
	bind($Sock, $local_port) || return (undef,"Socket Bind Error: $!");
	#print "connect($Sock, $remote_port)\n";
	connect($Sock, $remote_port) || return (undef,"Socket Connect Error: $!");
	$cur = select($Sock);
	$| = 1; # Disable buffering on socket.
	select($cur);
	undef($remote_hostname);undef($port);
	undef($socket_format);undef($proto);undef($packed_port);
	undef($cur);undef($remote_addr);undef(@remote_ip);
	undef($local_port);undef($remote_port);undef($remote_ip);
	return $Sock;
	}
########################
sub readSocket{
	my $hash=shift || return "No Hash Reference";
	my $Socket=shift || return "No Socket";
	binmode $Socket;
	my @lines=();
	%{$hash}=();
	#Read in First line and process it
	my $firstline=<$Socket>;
	$hash->{request}=strip($firstline);
	push(@lines,$firstline);
	$hash->{url} = ($firstline =~ m|(http://\S+)|)[0];
	if($hash->{url}=~/\?(.+)/s){
		$hash->{getdata}=$1;
		}
	#Method,host,port
	my ($method,$host,$port);
	#POST http://www.basgetti.com/cgi-bin/wasql.pl HTTP/1.1
	#CONNECT www.netteller.com:443 HTTP/1.1
	#print "Firstline:$firstline\n";
	if($firstline =~ m!(GET|POST|HEAD) http://([^/:]+):?(\d*)!){
		#print "HERE-A\n";
		$method=$1;
		$host=$2;
		$port=$3
		}
	elsif($firstline =~ m/(GET|POST|HEAD)\ \/(.+?)\ HTTP\//){
		#print "HERE-B\n";
		$method=$1;
		$hash->{getdata}=$2;
		}
	elsif($firstline=~m!(CONNECT) ([^/:]+):?(\d*)!){
		#print "HERE-C\n";
		$method=$1;
		$host=$2;
		$port=$3
		}
	#print "HOST:[$host]\n";
	$hash->{method}=$method;
	$hash->{host}=$host;
	$hash->{port}=$port;
	#Read in rest of Header
	while (<$Socket> ) {
		next if (/(Proxy-Connection:|Keep-Alive:|Accept-Encoding:)/);
		if(/^([a-z\-]+)\:(.+)/is){
			my $attr=lc(strip($1));
			my $val=lc(strip($2));
			$hash->{$attr}=$val;
			}
		push(@lines,$_);
		last if ($_ =~ /^[\s\x00]*$/);
		}
	if($hash->{host}=~/(.+?)\:([0-9]+)$/s){
		$hash->{host}=$1;
		$hash->{port}=$2;
	        }
	if(!length($hash->{method}) && $hash->{request}=~/^(GET|POST)\ /is){
		$method=$1;
		$hash->{method}=$method;
		}
	my $len=$hash->{'content-length'}?$hash->{'content-length'}:0;
	if ($method=~/^POST$/is && $len) {
		my $data='';
		my $dlen=0;
		my $bytes=$len>2048?2048:$hash->{'content-length'};
		#print "Reading Post data\n";
		while($dlen<$len){
			my $cdata;
			my $n=read($Socket,$cdata,$bytes);
			#print "cdata:[$cdata]\n";
			$data .= $cdata;
			$dlen=length($data);
			last if $n==0;
			last if !defined $n;
			select(undef,undef,undef,.01);
			}
		$hash->{'postdata'}=$data;
		}
	$hash->{url}=~s/\?.*//s;
	return @lines;
	}
###################
sub getHostName {
	#usage: my $bhost = hostName($ip_address);
	#info: resolves hostname from ipaddress or name.
	#tags: socket, hostname
	my $str=shift;
	my (@bytes,@octets,$packedaddr,$raw_addr,$host_name,$ip);

	if($str =~ /[a-zA-Z]/g) {
		$raw_addr = (gethostbyname($str))[4];
		@octets = unpack("C4", $raw_addr);
		$host_name = join(".", @octets);
		}
	else {
		@bytes = split(/\./, $str);
		$packedaddr = pack("C4",@bytes);
		$host_name = (gethostbyaddr($packedaddr, 2))[0];
		}
	$host_name ||= '';
	return($host_name);
	}
###################
sub getURL {
	#usage: ($head,$body,$code)=getURL($url,name=>'Bob',age=>25,_proxyserver=>"lab-proxy",_proxyuser=>"proxyuser",_proxypass=>"proxyuser");
	#info: gets a request via http socket
	#tags: url, socket
	my $url=shift || return ("No url","No url",404);
	my ($host,$path,$head,$body,$hrec);
	$url=~/http:\/\/([^\/]*)\/*([^ ]*)/;
	$host = $1;
	$path = "/".$2;
	$path=~s/^http\///s;
	#$path=~s/\?.*$//;
	#Append any Key/value pairs to url
	my @setpairs=@_;

	my %sets=@setpairs;
	#print "Sets: @tmp\n" if $sets{_debug};
	my @fields=keys(%sets);
	my @pairs=();
	foreach my $field (@fields){
		$field=strip($field);
		#skip field that start with _  (special meaning fields)
		next if $field=~/^\_/s;
		my $val=$sets{$field};
		$val=encodeURL(strip($val));
		push(@pairs,"$field=$val");
		#print "getURL Field: $field = $val<br>\n";
		}

	#print "HERE<br>[@pairs]\n";
	my $paramstr=join("\&",@pairs);
	if(length($paramstr)){$path .= "?" . $paramstr;}
	#Check for different port
	$port=$sets{_port} || 80;
	if($host=~/^([^:]*):*([^ ]*)/){
		$host = $1;
		$port = $2;
		}
	#print "HOST=$host,PORT=$port,PATH=$path\n" if $sets{_debug};
	local($^W) = 0;

	my $shost=length($sets{_proxyserver})?$sets{_proxyserver}:$host;
	my $sport=$sets{_proxyport} | $sets{_port} || $port || 80;
	my ( $iaddr, $paddr, $proto );

	print "HOST=$shost,PORT=$sport,PATH=$path\n" if $sets{_debug};
	my $hostip=getInternetIP($shost);
	$iaddr   = inet_aton( $hostip );
	#print "A [$sport, $iaddr]\n" if $sets{_debug};
	$paddr   = sockaddr_in( $sport, $iaddr );
	#print "B\n" if $sets{_debug};
	$proto   = getprotobyname( 'tcp' );
	#print "C\n" if $sets{_debug};
	unless( socket( SOCK, PF_INET, SOCK_STREAM, $proto ) ) {
		print "ERROR Dude: getUrl socket: $!\n" if $sets{_debug};
		return "","content-type: text/plain\n\nERROR Dude: getUrl socket: $!";
		}
	#print "D\n" if $sets{_debug};
	unless( connect( SOCK, $paddr ) ) {
		print "ERROR man: getUrl connect: $!\n" if $sets{_debug};
		return "","content-type: text/plain\n\nERROR my man: getUrl connect: $!\n$req";
		}
	#print "E\n" if $sets{_debug};

	my $netloc = $shost;
	$netloc .= ":$port" if $port != 80;
	#BUILD HEADER
	#print $socket "GET http://$server$get HTTP/1.1\n";
    #print $socket "Proxy-Connection: Keep-Alive\n";
    #print $socket "User-Agent: Mozilla/4.78 [en] (X11; U; Safemode Linux i386)\n";
    #print $socket "Pragma: no-cache\n";
    #print $socket "Host: $server\n";
    #print $socket "Accept: */*\n";
    #print $socket "Accept-Language: en\n\n";
	#return ("","GET http://$host$path HTTP/1.1","");
	#Important Note: 	HTTP/1.1 is a keep-alive protocol so it will slow down the response.
	#					HTTP/1.0 is not a keep-alive protocol so it returns much quicker.

	my @head=(
		"GET http://" . $shost . $path . " HTTP/1.0",
		);

	if($sets{_cookie}){
		$sets{_cookie}=strip($sets{_cookie});
		push(@head,"Cookie: " . $sets{_cookie});
		}
	if($sets{_user}){
		my $str=$sets{_user} . ":" . $subs{_pass};
		my $auth=strip(encode_base64($str));
		push(@head,"Authorization: Basic " . $auth);
		}
	if($sets{_proxyuser}){
		my $str=$sets{_proxyuser} . ":" . $sets{_proxypass};
		my $auth=strip(encode_base64($str));
		push(@head,"Proxy-Authorization: Basic " . $auth);
		}
	my $os=uName() || $ENV{OS};
	my $useragent="$progname/$version [en] ($os i386)";
	push(@head,
		"User-Agent: $useragent",
		"Pragma: no-cache",
		"Host: $netloc",
		"Accept: */*",
		"Accept-Language: en"
		);
	push(@head,"","");
	#Build Header and print to socket
	my $header=join("\015\012",@head);
	my $outfile='';
	print "HEADER SENT:\n$header\n\n" if $sets{_debug};
	if($sets{_outfile}){
		setFileContents($sets{_outfile},"$header\n\n");
		print "REQUEST:\n$header\n\n";
	}
	$body='';
	#Send the header
	select SOCK;
	binmode(SOCK);
	$| = 1;
	my $redirect='';
	print SOCK $header;
	$hrec='';
	my $hgot=0;
	my $redirect=0;
	#Read the socket response and store as $body
	while( <SOCK> ) {
		my $line=$_;
		if($line=~/Location\: (.+)/is){$redirect=strip($1);}
		if($line=~/Set\-Cookie\:(.+?)\;/is){push(@pairs,_cookie=>$1);$sets{_cookie}=$1;}
		if ($line =~ m/^HTTP\/\d+\.\d+\s+(\d+)[^\012]*\012/) {$code = $1;}
		if(!$hgot && length(strip($line))==0){
			$hgot++;
			next;
			}
		if(!$hgot){$hrec .= $line;}
		else{$body .= $line;}
		}
	unless( close( SOCK ) ) {
		return ( "getUrl close: $!" );
		}
	select STDOUT;
	$body=strip($body);
	print "HEADER RECIEVED:\n$hrec\n\n" if $sets{_showhead};
	if($redirect && !$sets{_noredirect}){
		$url="http://" . $shost . $redirect;
		print "REDIRECT TO $url [@setpairs]\n" if $sets{_showhead};
		my ($hrec,$body,$code,$blen,$header)=getURL($url,@setpairs);
		if(wantarray){return ($hrec,$body,$code,$blen,$header);}
		return $body;
    	}
	my $blen=length($body);
	if(wantarray){return ($hrec,$body,$code,$blen,$header);}
	return $body;
	}
###################
sub postURL {
	#usage: ($head,$body,$code)=postURL($url,name=>'Bob',age=>25,_proxyserver=>"lab-proxy",_proxyuser=>"proxyuser",_proxypass=>"proxyuser");
	#usage: ($ok,$body,$code,$blen,$head)=urlPost(_url=>$url);
	#info: special params are _url,_method,_user,_pass,_port,_proxyuser,_proxypass,_proxyserver,_proxyport
	#info: you can also pass in an array of files to upload: _file=>[file1=>$file1,file2=>$file2,...]
	#info: Defaults are _method=POST, _port=80
	#info: Required params: _url
	#tags: url, socket
	#Get params as a hash
	my $url=shift || return (postURLHead(501),"No url",501);
	my @setpairs=@_;

	my %params=@setpairs;
	#print "postURL params\n" . hashValues(\%params) ."\n---------------------\n";
	#print STDOUT "params{_url}=$params{_url}\n";
	$params{_url}=$url || strip(lc($params{_url}));
	#Require _url
	if(length($params{_url})==0){
		return (postURLHead(502),"No url",502);
		}
	$params{_url}=~s/^http://sg;
	$params{_url}=~s/^\/+//sg;
	$params{_url}=~s/^http://sg;
	$params{_url}=~s/^\/+//sg;
	$params{_url}="http://" . $params{_url};
	my $postLength=0;
	#Analyze _url
	my ($host,$path,$args,$head,$body,$header,$hrec);
	$params{_url}=~/http:\/\/([^\/]*)\/*([^\ ]*)/;
	$host = $1;
	$path = "/".$2;
	$host=~s/\:+$//s;
	if($path=~/\?(.+)/s){
		$args=$1;
		$path=~s/\?\Q$args\E//;
		}
	else{
		#Append any Key/value pairs to url
		my @pairs=();
		foreach my $key (keys(%params)){
			$key=strip($key);
			next if length($key)==0;
			#skip field that start with _  (special meaning fields)
			next if $key=~/^\_(headeronly|file|debug|sub|url|method|user|pass|port|proxyserver|proxyport|proxyuser|proxypass)/s;
			my $val=strip($params{$key});
			$val=encodeURL($val);
			push(@pairs,"$key=$val");
			#print "getURL Field: $field = $val<br>\n";
			}
		$args=join('&',@pairs);
		}
	#Determine method.
	my $method="GET";
	if(length($params{_method})){$method=$params{_method};}
	elsif(length($args)){$method="POST";}
	#Force POST method if uploading a file
	if(defined $params{_file}){
		$method="POST";
		$params{_enctype}="multipart/form-data";
		}
	#Build Header
	my @header=();
	if($method=~/GET/is){
		my $str = qq|$host$path|;
		if(length($args)){$str .= qq|\?$args|;}
		push(@header,"GET http://$str HTTP/1.0\r\n");
		}
	elsif(defined $params{_file}){push(@header,"POST $path HTTP/1.1\r\n");}
	else{push(@header,"POST $path HTTP/1.0\r\n");}
	#Mozilla/4.78 [en] (X11; U; Safemode Linux i386)
	my $os=uName() || $ENV{OS};
	$os=strip($os);
	#User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1
	my $useragent=$params{_useragent} || $ENV{HTTP_USER_AGENT} || "Mozilla/5.0 ($os; en-US; $progname\;) Gecko/20061204 Firefox/2.0.0.11";
	$useragent=~s/[\r\n]+//sg;
	#"$progname/$version [en] ($os i386)";
	$host=strip($host);
	$host=~s/\:+$//s;
	push(@header,
		"Host: $host\r\n",
		"Accept: */*\r\n",
		"Accept-Language: en-us\r\n",
		"User-Agent: $useragent\r\n",
		"Pragma: no-cache\r\n"
		);
	#Check for basic Authentication
	if($params{_user}){
		my $str=$params{_user} . ":" . $params{_pass};
		my $auth=strip(encode_base64($str));
		push(@header,"Authorization: Basic " . $auth . "\r\n");
		}
	#Check for proxy authentication
	if($params{_proxyuser}){
		my $str=$params{_proxyuser} . ":" . $params{_proxypass};
		my $auth=strip(encode_base64($str));
		push(@header,"Proxy-Authorization: Basic " . $auth . "\r\n");
		}
	if($params{_cookie}){
		$params{_cookie}=strip($params{_cookie});
		push(@header,"Cookie: " . $params{_cookie} . "\r\n");
		}
	my $body='';
	if($method=~/POST/is){
		if(defined $params{_file}){
			#file upload
			my $boundary="---------------------------WaSQL7d826131701a8";
			#normal form data
			foreach my $key (keys(%params)){
				$key=strip($key);
				next if !length($key);
				#skip field that start with _  (special meaning fields)
				next if $key=~/^\_(enctype|file|headeronly|debug|sub|url|method|user|pass|port|proxyserver|proxyport|proxyuser|proxypass)/s;
				my $val=strip($params{$key});
				next if !length($val);
				$body .= "--" . $boundary . "\r\n";
				$body .= qq|Content-Disposition: form-data; name="$key"\r\n\r\n|;
				$body .= $val . "\r\n";
				}
			#Files to upload
			my %files=@{$params{_file}};
			foreach my $fkey (keys(%files)){
				next if !-s $files{$fkey};
				$body .= "--" . $boundary . "\r\n";
				$files{$fkey}=~s/[\\\/]+/\\/sg;
				my $filename=getFileName($files{$fkey});
				$body .= qq|Content-Disposition: form-data; name="$fkey"; filename="$filename"\r\n|;
				#determine type
				my $type=getContentType($files{$fkey});
				$body .= qq|Content-Type: $type\r\n\r\n|;
				if($params{_sub}){my $sub=$params{_sub};&$sub("Reading $fkey $files{$fkey}",0);}
				if(open(UF,$files{$fkey})){
					binmode UF;
					my $len=length($body);
					my $flen=-s $files{$fkey};
					my $lastpcnt=0;
					my $filebody='';
					while(<UF>){
						$filebody .= $_;
						my $pcnt=int((length($filebody)/$flen)*100);
						if($params{_sub} && $pcnt != $lastpcnt){
							my $sub=$params{_sub};
							&$sub("Reading $fkey $type",$pcnt);
							$lastpcnt=$pcnt;
							}
                    	}
                    $body .= strip($filebody);
					close UF;
                	}
				$body .= "\r\n";
            	}
            #close the boundary
            $body .= "\-\-$boundary\-\-\r\n\r\n";
            $postLength=length($body);
            $body .= "";
            push(@header,
            	"Keep-Alive: 115\r\n",
            	"Connection: keep-alive\r\n",
				"Content-Type: multipart/form-data; boundary=$boundary\r\n",
				"Content-Length: $postLength\r\n",
				);
        	}
		elsif(length($args)){
			$postLength=length($args);
			push(@header,
				"Content-Type: application/x-www-form-urlencoded\r\n",
				"Content-Length: $postLength\r\n",
				);
			}
		}
	#Connect to Socket
	my $Sock;
	my $port=$params{_port} || 80;
	my $chost=$params{_proxyserver}?$params{_proxyserver}:$host;
	my $cport=$params{_proxyport}?$params{_proxyport}:$port;
    my $header_sent=join("",@header);
    #print "Header Sent:\n$header_sent\n\n";
    #print "connectSocket($Sock, $chost, $cport)\n" if $params{_debug};
	my $cmsg=&connectSocket($Sock, $chost, $cport);
	if($cmsg=~/error/is){
		return (postURLHead(310),$cmsg,310);
		}
	binmode($Sock);
	if(length($cmsg)){
		return (postURLHead(312),"Error: $cmsg\r\n$^E",312);
		}
	#Send Header to Sock and wait for response
	if($params{_headeronly}){
		print @header;
        if(defined $params{_file} && length($body)){
			print $body;
			}
		elsif(length($args)){
			#print  "Args[$method]: $args\n";
			print  $args;
			}
		return 1;
    	}
	print $Sock @header;
	print $Sock "\r\n";
	if($params{_debug}){
		print "HEADER SENT:\r\n";
		print @header;
		print "\r\n";
    	}
	if($method=~/POST/is){
		if($params{_sub}){my $sub=$params{_sub};&$sub("Sending",0);}
		if(defined $params{_file} && length($body)){
			print $Sock $body;
			if($params{_debug}){print $body;}
			}
		elsif(length($args)){
			#print STDOUT "Args[$method]: $args\n";
			print $Sock $args;
			if($params{_debug}){print $args;}
			}
		}
	#Read in response into $body
	$hrec='';
	my $redirect='';
	my $hgot=0;
	$body='';
	#Read the socket response and store as $body
	my $redirect=0;
	my @cookies=();
	if($params{_sub}){my $sub=$params{_sub};&$sub("Receiving $postLength bytes",0);}
	my $lastpcnt=0;
	my $sendLength=0;
	my $getLength=0;
	while( <$Sock> ) {
		my $line=$_;
		my $pcnt=0;
		$sendLength += length($line);
		if($hgot && $getLength && $sendLength){
			$pcnt=int(($sendLength/$getLength)*100);
            }
		if($params{_sub} && $pcnt && $pcnt != $lastpcnt){
			my $sub=$params{_sub};
			&$sub("Receiving \%$pcnt",$pcnt);
			$lastpcnt=$pcnt;
			}
		print $line if $params{_flush}==1;
		if(!$hgot){
			#Special headers - cookies and redirects
			if($line=~/Location\: (.+)/is){$redirect=strip($1);}
			if($line=~/Set\-Cookie\:(.+?)\;/is){
				my $c=strip($1);
				push(@cookies,"$c\;");
				#$params{_cookie}=$1;
				}
			if ($line =~ m/^HTTP\/\d+\.\d+\s+(\d+)[^\012]*\012/) {$code = int($1);}
			if($line=~/Content-Length\: ([0-9]+)/is){
                $getLength=$1;
                #print "getLength Set: $getLength\r\n";
            	}
			if($line=~/^Content-([a-z]+?)\:(.+)/is){
				my $key=lc(strip($1));
            	}
            #print "$line\n";
			}
		if(!$hgot && length(strip($line))==0){
			$hgot++;
			next;
			}
		if(!$hgot){$hrec .= $line;}
		else{$body .= $line;}
		}
	unless( close( $Sock ) ) {
		return (postURLHead(320),"getUrl close: $!",320);
		}
	#print "Debug: HERE\n";
	return if $params{_flush}==1;
	select STDOUT;
	$body=strip($body);
	if(scalar @cookies){
        $params{_cookie}=join(" ",@cookies);
        push(@pairs,_cookie=>$params{_cookie});
    	}
	if($params{_debug}){
		print "\r\n\r\nHEADER RECIEVED:\r\n$hrec\r\n\r\n";
		print "redirect: $redirect\r\n";
		print "shost:$shost\r\n";
		}
	if($redirect && !$params{_noredirect}){
		if($redirect=~/^http/is){$url=$redirect;}
		else{$url=$chost . $redirect;}
		if($url!~/^http/is){$url="http://" . $url;}
		print "REDIRECT TO $url [@setpairs]\r\n" if $params{_debug};
		#exit;
		my ($hrec,$body,$code,$blen,$header_sent,$c)=postURL($url,@setpairs);
		if(wantarray){return ($hrec,$body,$code,$blen,$header_sent,$c);}
		return $body;
    	}
    #print "HERE\n";
    #exit;
	my $blen=length($body);
	if(wantarray){return ($hrec,$body,$code,$blen,$header_sent,$params{_cookie});}
	return $body;
	}
##################
sub postURLHead{
	my $code=shift || 503;
	my $head=qq|HTTP/1.1 $code OK\n|;
	$head .= "Date: " . localtime() . "\n";
	$head .= "Connection: close\n";
	$head .= "Content-Type: text/html; charset=iso-8859-1\n";
	return $head;
	}
##################
sub connectSocket {
	# Create a socket that connects to a certain host
	# connectToHost($Sock, $host, $port)
	# $local_host_ip is assumed to be my hostname IP address
	my ($host, $port) = @_[1,2];
	if(!length($host)){return "No Host";}
	#print "connectToHost ($host, $port)\n";
	my ($socket_format, $proto, $packed_port, $cur);
	my ($remote_addr, @remote_ip, $remote_ip);
	my ($local_port, $remote_port);
	if ($port !~ /^\d+$/) {
		$port = (getservbyname($port, "tcp"))[2];
		$port = 80 unless ($port);
		}
	$proto = (getprotobyname('tcp'))[2];
	$remote_addr = (gethostbyname($host))[4];
	if (!$remote_addr) {
		return "Error resolving hostname: [$host]";
		}
	@remote_ip = unpack("C4", $remote_addr);
	$remote_ip = join(".", @remote_ip);
	$socket_format = 'S n a4 x8';
	$local_port = pack($socket_format, &AF_INET, 0, $local_host_ip);
	$remote_port = pack($socket_format, &AF_INET, $port, $remote_addr);
	socket($_[0], &AF_INET, &SOCK_STREAM, $proto) || return "ConnectSocket socket error: $!";
	bind($_[0], $local_port) || return "ConnectSocket bind error: $!";
	connect($_[0], $remote_port) || return "ConnectSocket connect error: $!";
	$cur = select($_[0]);
	$| = 1; # Disable buffering on socket.
	select($cur);
	return '';
	}
###############
sub sendMail{
	#usage: $ck=sendMail(smtp=>$smtp,smtpuser=>$smtpuser,smtppass=>$smtppass,sendmail=>$path,to=>$email,from=>$email,subject=>$subject,message=>$message,attach=>$file,cc=>$email,bcc=>$email);
	#info: sends email using either the smtp server or sendmail if provided. returns 1 or error message
	#tags: socket, email
	my @params=@_;
	my %params=@params;
	my $MailSock;
	my $details='';
	#strip leading and ending spaces from params
	$details .= qq|<b><u>Params</u></b><br>\n| if $params{verbose};
	foreach my $key (keys(%params)){
		$params{$key}=strip($params{$key});
		}
	#check for required params - from,subject,message, either smtp or sendmail, either to, cc, or bcc
	if(length($params{from})==0){return "from is required";}
	if(length($params{subject})==0){return "subject is required";}
	if(length($params{message})==0){return "message is required";}
	if(!length($params{smtp}) && length($Config{smtp})){$params{smtp}=$Config{smtp};}
	if(!length($params{smtpuser}) && length($Config{smtpuser})){$params{smtpuser}=$Config{smtpuser};}
	if(!length($params{smtppass}) && length($Config{smtppass})){$params{smtppass}=$Config{smtppass};}
	if(length($params{smtp})==0 && length($params{sendmail})==0){
		if(-e '/usr/lib/sendmail'){$params{sendmail}='/usr/lib/sendmail -i -t';}
		else{return "either smtp or sendmail is required";}
		}
	if(length($params{to})==0 && length($params{cc})==0 && length($params{bcc})==0){return "either to, cc, or bcc is required";}
	foreach my $key (keys(%params)){
		$details .= qq| <b>$key: </b> $params{$key}<br>\n| if $params{verbose};
		}
	#Define Boundary
	my $boundary=qq|----=_WaSql_SendMail_BoundaryString.060704|;
	#Open up $MailSock
	if(length($params{sendmail})){
		if($params{_sub}){my $sub=$params{_sub};&$sub("connecting to sendmail");}
		#connect using sendmail
		if(!open($MailSock, "|$params{sendmail}")){return $details . "Unable to open $params{sendmail}";}
		$details .= qq|Using sendmail: $params{sendmail}<br>| if $params{verbose};
		if($params{_sub}){my $sub=$params{_sub};&$sub("Connected to sendmail");}
		#connected
		my $ok=&sendMailData($MailSock,@params);
		$details .= qq|sendMailData returned $ok<br>| if $params{verbose};
		close($MailSock);
		}
	elsif(length($params{smtp})){
		#Connect to smtp server
		if($params{_sub}){my $sub=$params{_sub};&$sub("connecting to $params{smtp}");}
		my ($proto) = (getprotobyname('tcp'))[2];
		my ($port) = (getservbyname('smtp', 'tcp'))[2];
		#print "connecting to $params{smtp} on port $port\n";
		my ($smtpaddr) = ($params{smtp}=~/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/)? pack('C4',$1,$2,$3,$4): (gethostbyname($params{smtp}))[4];
		if (!defined($smtpaddr)){return $details . "unknown SMTP - $params{smtp}";}
		#print "opening a socket\n";
		if (!socket($MailSock, AF_INET, SOCK_STREAM, $proto)){return $details . "SMTP socket failure - $params{smtp}";}
		#print "connecting\n";
		if (!connect($MailSock, pack('Sna4x8', AF_INET, $port, $smtpaddr))){return $details . "SMTP socket connection failure - Port $port.  $params{smtp} \[$port\]\n$!\n$^E\n\n";}
		$details .= qq|Using smtp: $params{smtp}<br>| if $params{verbose};
		my ($oldfh) = select($MailSock); $| = 1; select($oldfh);
		#print "reading\n";
		$_ = <$MailSock>;
		if (/^[45]/){close $MailSock;return $details . "SMTP service not available - $params{smtp}";}
		#print "R:$_\n";
		#connected
		if($params{_sub}){my $sub=$params{_sub};&$sub("Communicating with $params{smtp}");}
		#print "Printing helo\n";
		print $MailSock "helo localhost\r\n";
		$_ = <$MailSock>;
		#print "R:$_\n";
		if (/^[45]/){close $MailSock;return $details . "Unknown SMTP communication error - $params{smtp}";}
		#Check for user authentication
		if($params{smtpuser} && $params{smtppass}){
			if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Credentials to $params{smtp}");}
			my $muser=$params{smtpuser};
			my $mpass=$params{smtppass};
			$muser=~s/[\r\n\t\s]+$//s;
			$mpass=~s/[\r\n\t\s]+$//s;
			$muser=encode_base64($muser);
			$mpass=encode_base64($mpass);
			$muser=~s/[\r\n\t\s]+$//s;
			$mpass=~s/[\r\n\t\s]+$//s;
			print $MailSock "AUTH LOGIN\r\n";
			$_ = <$MailSock>;
			if ($_=~/334/){
				print $MailSock "$muser\r\n";
				$_ = <$MailSock>;
				if ($_!~/334/ && !length($params{force})){close $MailSock;return $details . "SMTP user authentication error - $params{smtpuser}";}

				print $MailSock "$mpass\r\n";
				$_ = <$MailSock>;
				if ($_!~/235/ && !length($params{force})){close $MailSock;return $details . "SMTP password authentication error [$muser][$muser2],[$mpass][$mpass2],[$params{smtpuser}][$params{smtppass}]";}
				}
			elsif(!length($params{force})){close $MailSock;return $details . "SMTP user authentication is not supported - $params{smtp}";}
			}
        if($params{_sub}){my $sub=$params{_sub};&$sub("From: $params{from}");}
		#From
		if($params{from}=~/\<(.+?)\>/s){
			#From: "Derek Price" <Derek@raeofsunshine.com>
			my $from=$1;
			print $MailSock "mail from: $from\r\n";
        	}
		else{
			print $MailSock "mail from: <$params{from}>\r\n";
			}
		$_ = <$MailSock>;
		if (/^[45]/){close $MailSock;return $details . "<pre><xmp>SMTP mail error in sender (FROM) $params{from} - $_</xmp></pre>";}
		#To
		my $To;
		#parse the to by commas and semicolons and send each one separately.
		my @emails=split(/[\,\;]+/,$params{to});
		if($params{_sub}){my $sub=$params{_sub};&$sub("To: $params{to}");}
		foreach my $email (@emails){
			#check for valid email address.
			$email=strip($email);
			if($params{_sub}){my $sub=$params{_sub};&$sub("to: $email");}
			if($email=~/\<(.+?)\>/s){print $MailSock "rcpt to: $1\r\n";}
			else{print $MailSock "rcpt to: $email\r\n";}
			$_ = <$MailSock>;
			if (/^[45]/){close $MailSock;return $details . "SMTP mail error in recipient (TO) $email - $_";}
			$To .= qq|To: $email\r\n|;
			}
		#CC
		my $Cc;
		if(length($params{cc})){
			if($params{_sub}){my $sub=$params{_sub};&$sub("CC: $params{cc}");}
			@emails=split(/[\,\;]+/,$params{cc});
			foreach my $email (@emails){
				$email=strip($email);
				if($params{_sub}){my $sub=$params{_sub};&$sub("CC: $email");}
				if($email=~/\<(.+?)\>/s){print $MailSock "rcpt to: $1\r\n";}
				else{print $MailSock "rcpt to: $email\r\n";}
				$_ = <$MailSock>;
				if (/^[45]/){close $MailSock;return $details . "SMTP mail error in recipient (CC) $email - $_";}
				$Cc .= qq|CC: $email\r\n|;
				}
			}
		#Bcc
		my $Bcc;
		if(length($params{bcc})){
			if($params{_sub}){my $sub=$params{_sub};&$sub("BCC: $params{bcc}");}
			@emails=split(/[\,\;]+/,$params{bcc});
			foreach my $email (@emails){
				$email=strip($email);
				if($params{_sub}){my $sub=$params{_sub};&$sub("BCC: $email");}
				if($email=~/\<(.+?)\>/s){print $MailSock "rcpt to: $1\r\n";}
				else{print $MailSock "rcpt to: $email\r\n";}
				$_ = <$MailSock>;
				if (/^[45]/){close $MailSock;return $details . "SMTP mail error in recipient (BCC) $email - $_";}
				$Bcc .= qq|BCC: $email\r\n|;
				}
			}
		if($params{_sub}){my $sub=$params{_sub};&$sub("Request to send Mail Data");}
		#Data
		print $MailSock "data\r\n";
		$_ = <$MailSock>;
		if (/^[45]/){close $MailSock;return $details . "SMTP data error - $params{smtp} - $_";}
		&sendMailData($MailSock,@params);
		if($params{_sub}){my $sub=$params{_sub};&$sub("Closing connection to $params{smtp}");}
		#End Message
		print $MailSock "\r\n.\r\n";
		$_ = <$MailSock>;
		if (/^[45]/){close $MailSock;return $details . "SMTP mail finish error - $params{smtp} - $_";}
		#Quit
		print $MailSock "quit\r\n";
		$_ = <$MailSock>;
		close($MailSock);
		}
	if($params{_sub}){my $sub=$params{_sub};&$sub("Successfully sent email");}
	return $details if $params{verbose};
	return 1;
	}
#################
sub sendMailData{
	my $MailSock=shift;
	#open($MailSock,">sendMail.log");
	my %params=@_;
	if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Email Data");}
	my $boundary=qq|----=_WaSql_SendMail_BoundaryString.060704|;
	#Message Header
	#Date
	#determine GMT offset
	my $offset=getGMTOffset(1);
	#Date
	if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Message Header");}
	print $MailSock "Date: " . getDate("AD, ND AM YYYY MH:MM:SS $offset") . "\r\n";
	#From
	if($params{from}=~/\<(.+?)\>/s){
		#From: "Derek Price" <Derek@raeofsunshine.com>
		print $MailSock "From: $params{from}\r\n";
        }
	else{
		print $MailSock "From: <$params{from}>\r\n";
		}

	#To
	print $MailSock "To: $params{to}\r\n";
	#CC
	if(length($params{cc})){
		my @emails=split(/[\,\;]+/,$params{cc});
		my $ccstr=join(',',@emails);  
		print $MailSock "Cc: $ccstr\r\n" if $params{cc};
		}
	#Bcc - Do not show Bcc recipients in message header
	my $replyaddr=$params{reply};
	if(isEmail($replyaddr)){
		print $MailSock "Reply-to: $replyaddr\r\n";
		}
	if($params{_sub}){my $sub=$params{_sub};&$sub("Subject: $params{subject}");}
	print $MailSock "Subject: $params{subject}\r\n";
	#Message Body
	#Message ID
	#Message-ID: <00f001c86b39$f3cee6b0$f20a0a0a@WOLFGANG>
	my $base64=strip(encode_base64($params{message}));
    $params{from}=~/\@([a-z\_\-]+)/is;
    my $domain=uc($1);
    my $ctime=time();
	my $msgID=substr($base64,0,20) . '$' .  encodeCRC($base64 . $ctime) . "\@$domain";
	print $MailSock "Message-ID: <$msgID>\r\n";
	print $MailSock "Mime-Version: 1.0\r\n";
	print $MailSock qq|Content-Type: multipart/mixed;\r\n|;
	print $MailSock qq|\tboundary="$boundary"\r\n|;
	my $msg_type='Text';
	if($params{message}=~m/\<(.+?)\>/is){$msg_type='HTML';}
	#print $MailSock qq|X-Msg-Type: $msg_type\r\n|;
	#Priority
	#X-Priority: 3
	#X-MSMail-Priority: Normal
	#X-Mailer: Microsoft Outlook Express 6.00.2800.1106
	#X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2800.1106
	if(length($params{priority}) && $params{priority}=~/^(1|high|important|urgent)$/is){
		print $MailSock qq|X-Priority: 1\r\n|;
		print $MailSock qq|X-MSMail-Priority: High\r\n|;
		print $MailSock qq|X-MimeOLE: Produced By WaSQL Mailer MimeOLE V6.00.2800.1106\r\n|;
		print $MailSock qq|Priority: High\r\n|;
		print $MailSock qq|Importance: Urgent\r\n|;
		}
	else{
		print $MailSock qq|X-Priority: 3\r\n|;
		print $MailSock qq|X-MSMail-Priority: Normal\r\n|;
		print $MailSock qq|X-MimeOLE: Produced By WaSQL Mailer MimeOLE V6.00.2800.1106\r\n|;
		print $MailSock qq|Priority: Normal\r\n|;
		}
	print $MailSock "X-Mailer: WaSQL Mailer Express 6.00.2900.3138\r\n";
	print $MailSock "Status: RO\r\n\r\n";
	if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){print $MailSock qq|This is a multi-part message in MIME format.\r\n\r\n|;}
	if($msg_type=~/^Html$/is){
		if($params{_sub}){my $sub=$params{_sub};&$sub("HTML/Text message");}
		#Send this message in two parts: text and Html
		print $MailSock qq|\-\-$boundary\r\n|;
		my $mpa_boundary=qq|----=_WaSql_SendMail_MPA_BoundaryString.060704|;
		#send other html and text
		print $MailSock qq|Content-Type: multipart/alternative; boundary="$mpa_boundary"\r\n\r\n|;
        #Text Version
        print $MailSock qq|\-\-$mpa_boundary\r\n|;
		print $MailSock qq|Content-Type: text/plain;\r\n|;
		print $MailSock qq|\tcharset="iso-8859-1"\r\n|;
		print $MailSock qq|Content-Transfer-Encoding: 7bit\r\n\r\n|;
		my $textmsg=removeHtml($params{message});
		my @message_lines=split(/\r\n/,$textmsg);
		foreach my $message_line (@message_lines){
			#$message_line=strip($message_line);
			print $MailSock qq|$message_line\r\n|;
			}
		print $MailSock qq|\r\n|;
		#Html version
		print $MailSock qq|\-\-$mpa_boundary\r\n|;
		print $MailSock qq|Content-Type: text/html;\r\n|;
		print $MailSock qq|\tcharset="iso-8859-1"\r\n|;
		print $MailSock qq|Content-Transfer-Encoding: quoted-printable\r\n\r\n|;
		if($params{message}!~/\<body\>/is){$params{message}="<body>\r\n" . $params{message} . "</body>\r\n";}
		if($params{message}!~/\<html\>/is){$params{message}="<html>\r\n" . $params{message} . "</html>\r\n";}
		my $message=&encodeQP($params{message});
		my @message_lines=split(/[\r\n]+/,$message);
		foreach my $message_line (@message_lines){
			#$message_line=strip($message_line);
			print $MailSock qq|$message_line\r\n|;
			}
		print $MailSock qq|\r\n\r\n|;
		print $MailSock qq|\r\n\-\-$mpa_boundary\-\-\r\n|;
		}
	elsif($params{message}=~m/\<(.+?)\>/is){
		if($params{_sub}){my $sub=$params{_sub};&$sub("HTML message");}
		print $MailSock qq|\-\-$boundary\r\n|;
		print $MailSock qq|Content-Type: text/html;\r\n|;
		print $MailSock qq|\tcharset="iso-8859-1"\r\n|;
		print $MailSock qq|Content-Transfer-Encoding: base64\r\n\r\n|;
		if($params{message}!~/\<body\>/is){$params{message}="<body>\n" . $params{message} . "</body>\n";}
		if($params{message}!~/\<html\>/is){$params{message}="<html>\n" . $params{message} . "</html>\n";}
		my $message=encode_base64($params{message});
		my @message_lines=split(/[\r\n]+/,$message);
		foreach my $message_line (@message_lines){
			#$message_line=strip($message_line);
			print $MailSock qq|$message_line\r\n|;
			}
		print $MailSock qq|\r\n\r\n|;
		}
	else{
		#if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){print $MailSock qq|\-\-$boundary\r\n|;}
		if($params{_sub}){my $sub=$params{_sub};&$sub("Text message");}
		print $MailSock qq|\-\-$boundary\r\n|;
		print $MailSock qq|Content-Type: text/plain;\r\n|;
		print $MailSock qq|\tcharset="iso-8859-1"\r\n|;
		print $MailSock qq|Content-Transfer-Encoding: 7bit\r\n\r\n|;
		my @message_lines=split(/\r\n/,$params{message});
		foreach my $message_line (@message_lines){
			#$message_line=strip($message_line);
			print $MailSock qq|$message_line\r\n|;
			}
		print $MailSock qq|\r\n\r\n|;
		}
	#Check for attachments
	if ($params{attach} ne ''){
		my $temp='';
		#split into files array
		my @files=split(/[:;,]+/,$params{attach});
		foreach my $file (@files){
			#get full path of file
			my $filepath;
			if(-e $file){$filepath=$file;}
			else{$filepath="$ENV{DOCUMENT_ROOT}/$file";}
			$filepath=~s/\/+/\//g;
			next if !-e $filepath;
			my @tmp=split(/[\\\/]/,$filepath);
			my $name=pop(@tmp);
			my $size=-s $filepath;
			my $vsize=verboseSize($size);
			#capture file name
			my @fileparts = split(/\/|\\|:/, $file);
			my $filename=pop(@fileparts);
			#get type
			my $type=getContentType($filepath);
			if($params{_sub}){my $sub=$params{_sub};&$sub("Attachment: $name is $vsize");}
			print $MailSock qq|\-\-$boundary\r\n|;
			print $MailSock qq|Content-Type: $type;\r\n|;
			print $MailSock qq|\tname="$filename"\r\n|;
			print $MailSock qq|Content-Description: File Transfer\r\n|;
			if($type=~/text/is){
				print $MailSock qq|Content-Transfer-Encoding: 7bit\r\n|;
				}
			else{
				print $MailSock qq|Content-Transfer-Encoding: base64\r\n|;
				}
			$cid=getFileContentId($file);
			print $MailSock qq|Content-ID: <$cid>\r\n|;
			if($params{inline}){
				print $MailSock qq|Content-Disposition: inline;\r\n|;
				}
			else{
				print $MailSock qq|Content-Disposition: attachment;\r\n|;
				}
			print $MailSock "\tfilename=\"$filename\"; size=".$size.";\r\n";
   			print $MailSock qq|\r\n|;
			my $temp='';
			open(ATT,$filepath) || return "SMTP attachment error - Cannot open attachment $filepath. " ;
			if($params{_sub}){my $sub=$params{_sub};&$sub("Reading Attachment: $name");}
			binmode(ATT) if -B $filepath;
			while (<ATT>){
				$temp .= $_;
				my $pcnt=int((length($temp)/$size)*100);
				if($params{_sub}){my $sub=$params{_sub};&$sub("Reading Attachment: $name",$pcnt);}
				}
			close(ATT);
			if($type=~/text/is){
				if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Attachment: $name");}
				print $MailSock $temp;
				}
			else{
				if($params{_sub}){my $sub=$params{_sub};&$sub("Base64 Encoding: $name");}
				my $tx=encode_base64($temp);
				if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Attachment: $name");}
				my @lines=split(/[\r\n]/,$tx);
				my $linecnt=@lines;
				my $lastpcnt=0;
				for(my $x=0;$x<$linecnt;$x++){
					my $pcnt=int(($x/$linecnt)*100);
					print $MailSock "$lines[$x]\r\n";
					if($params{_sub}){my $sub=$params{_sub};&$sub("Sending Attachment: $name",$pcnt);}
                	}
				#print $MailSock "==";
				}
			print $MailSock qq|\r\n|;
			}
		}
	if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){print $MailSock qq|\r\n\-\-$boundary\-\-\r\n|;}
	return 1;
	}
##################
sub getSocketMsg{
	my $sock=shift || return -1;
	my $nowait=shift;
	#Read the socket response and store as $body
	my $code;
	my $bytes=int(8*1024);
	my $body='';
	select $sock;
	$|=1;
	#read until $n is less that $readsize
	#my $gcnt='';
	while(1){
		my $pbody='';
		last if !$sock;
		my $n=sysread($sock, $pbody,$bytes);
		#print "\tn=$n,bytes=$bytes,len=" . length($pbody) . "\n";
		last if $n==0;
		last if !defined $n;
		$body .= $pbody;
		last if $pbody=~/[\r\n\s]+[0-9]+[\r\n\s]+$/s;
		#if($nowait && $n < $bytes){last;}	#$n returns the size to bytes actually read.
		sleep(2);	#Required, else it does not return all the data...
		next;
		}
	$body=strip($body);
	select STDOUT;
	if(wantarray){return (length($body),$body);}
	return $body;
	}
##################
sub parseHeader{
	my $data=shift || return;
	my @lines=split(/[\r\n]+/,$data);
	my %header=();
	foreach my $line (@lines){
		my ($key,$val)=split(/\:/,$line,2);
		#ignore lines that do not look like a key/value pair
		next if $key=~/[^a-z\-]/is;
		$key=lc(strip($key));
		$val=strip($val);
		next if !length($val);
		$header{$key}=$val;
    	}
    return %header;
	}
##################
sub sendSocketMsg{
	#Send a message via a socket
	my $sock=shift || return 0;
	my $msg=shift || return 0;
	my $msglen=length($msg);
	my $T=syswrite($sock, $msg ,$msglen);
	return $T;
	}
###########
sub parseRSS{
	my $url=shift;
	my %rss=();
    my ($head,$body,$code)=postURL($url);
    if($code != 200){
		$rss{error}="parseRss Failure [$code]\r\n$head";
		return %rss;
		}
	while($body=~/<rss(.*?)>(.*?)<\/rss>/sig){
		my $params=$1;
		my $rss=$2;
        %rss=readXML($channel,"channel");
    	}
    return %rss;
	}
########################
sub wget{
	#usage: my $afile=wget($url[,$dir,$unique,$sub]);
	#info: gets a file from the web, saves it locally and return the full path to the file.
	#info: you can pass in the dir to save it to and a flag to make the filename unique
	#tags: socket
	my $file=shift;
	my $dir=shift || $progpath;
	my $unique=shift;
	my $sub=shift;
	if($file!~/^http/is){return "$file is not a URL";}
	my $local=$file;
	$local=~s/^http\:\/\///is;
	$local=~s/(.+?)\///sg;
	if($unique){$local=time() . "_" . $local;}
	$local=~s/\+/\_/sg;
	if(-s "$dir/$local"){return "$dir/$local";}
	#print "Remote File: $file\r\n";
	#print "Local File: $local\r\n";
	my ($head,$body,$code,$blen,$header_sent,$c)=postURL($file,_sub=>$sub,_method=>"GET");
	if($code != 200){return("$code error: unable to retrieve $file\r\n$header_sent\r\n\r\n$head\r\n\r\n$body\r\n\r\n");}
	my $header=parseHeader($head);
	my $ctype=$header->{'content-type'};
	my $ext='';
	if($ctype=~/(image|text|application)\/([a-z]+)/is){$ext=$2;}
	if($ext && $local !~/\.$ext$/is){$local .= "." . $ext;}
	$local=~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;

	if(!open(FH,">$dir/$local")){return("unable to save $file\r\nto\r\n$dir\\$local");}
	#print "Saving $dir/$local\n";
	binmode(FH);
	if($header->{content-type}=~/text/is){
		my @lines=split(/[\r\n]+/,$body);
		foreach my $line (@lines){print FH $line . "\r\n";}
    	}
	else{print FH $body;}
	#
	close(FH);
	return "$dir\\$local";
	}
##################
return 1;
