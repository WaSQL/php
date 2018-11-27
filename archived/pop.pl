#!/usr/bin/perl
#pop.pl - used to pop email and add it to wasql databases
#################################################################  
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
### Use and requires
$|=1;
use Digest::MD5 qw(md5 md5_hex md5_base64);
use Net::POP3;
use Cwd;
#ppm install mime-tools
use MIME::Parser;
use DBI;
our $progname="pop";
our $progpath=getcwd();
if(-d "/var/www/wasql" && -s "/var/www/wasql/pop.pl"){
	$progpath="/var/www/wasql";
	}
elsif(-d "/home/basgetti/wasql_stage" && -s "/home/basgetti/wasql_stage/pop.pl"){
	$progpath="/home/basgetti/wasql_stage";
	}
elsif(-d "/home/celiocorp/wasql_stage" && -s "/home/celiocorp/wasql_stage/pop.pl"){
	$progpath="/home/celiocorp/wasql_stage"; 
	}
print "Progpath: $progpath\n";
#exit;
require "$progpath/subs_common.pl";
require "$progpath/subs_socket.pl";
require "$progpath/subs_wasql.pl";
require "$progpath/subs_database.pl";
our $serverIndex;
printPopLog(localtime());
###########################################
### Check for config xml
if(!-s "$progpath/$progname\.xml"){
	print "No xml defined\r\n";
	exit(2);
	}
###########################################
### Read in config xml
our %Config=();
checkConfig();
###########################################
### Create a mime Parser
my $parser = new MIME::Parser;
$parser->decode_headers(1);
###########################################
### Main
my $frequency=$Config{timer} || 60; #default to 60 seconds
while(1){
	checkConfig();
	my @sources=sort(keys(%{$Config{sources}}));
	foreach my $index (@sources){
		$serverIndex=$index;
		if($Config{sources}{$index}{source}=~/^pop$/is){
			my $ok=popMessages($index);
			if(!isNum($ok)){abort("popMessages returned error",$err);}
        	}
        elsif($Config{sources}{$index}{source}=~/^dir$/is){
			my $ok=dirMessages($index);
			if(!isNum($ok)){abort("dirMessages returned error",$err);}
        	}
        elsif($Config{sources}{$index}{source}=~/^base$/is){
			my $ok=baseMessages($index);
			if(!isNum($ok)){abort("dirMessages returned error",$err);}
        	}
        else{abort(hashValues($Config{sources}{$index}));}
		}
	if(isNum($Config{loop}) && $Config{loop}==0){exit(0);}
	my $wtime=localtime();
	select(undef,undef,undef,$frequency);
	}
###########################################
### Subroutines
#####################
sub checkDatabase{
	my $index=shift;
	if(defined $Config{sources}{$index}{host}){
		my $host=$Config{sources}{$index}{host};
		my %Wasql=getConfig($host);
		if(defined $Wasql{err}){return abort("Wasql host error: $host. $Wasql{err}");}
		else{
			#Connect to this database:
			my @opts=();
			foreach my $opt (keys(%Wasql)){push(@opts,$opt=>$Wasql{$opt});}
			my $err = dbConnect(@opts,verbose=>isDebug());
			if(!$dbh){return abort("dbConnect",$err,@opts);}
          	}
        }
    return 1;
	}
#####################
sub popMessages{
	my $index=shift;
	$serverIndex=$index;
	my $server=$Config{sources}{$index}{name} || return abort("popMessages","no name");
	my $user=$Config{sources}{$index}{user} || return abort("popMessages","no user");
	my $pass=$Config{sources}{$index}{pass} || return abort("popMessages","no pass");
	print "popMessages for $user/$pass on $server\r\n";
	###########################################
	### Connect to POP3 server
	my $trace=$Config{sources}{$index}{trace} || $Config{trace} || 0;
	my $pop = Net::POP3->new($server, Timeout => 60, Debug => $trace) || return abort("checkForMessage","Server Connection Error",$server,$^E);
	###########################################
	### Get the messages. first try authenticated pop and then try normal pop.
	my $msgcnt=$pop->apop($user, $pass) || $pop->login($user, $pass) || return abort("checkForMessage","Authentication Error",$server,$user,$pass);
	print "\tfound $msgcnt messages\r\n";
	###########################################
	### Connect to database?
    checkDatabase($index);
	###########################################
	### Loop through the messages
	if ($msgcnt > 0) {
		my $msgnums = $pop->list; # hashref of msgnum => size
		my @nums=sortTextArray(keys(%$msgnums));
	    foreach my $msgnum (@nums) {
			print "\tgetting message #$msgnum\r\n";
	    	my $msg = $pop->get($msgnum);
	    	my $data = join ('',@$msg);
	    	my $uid='UIDL-' . md5_base64($data);
			print "\t\tProcessing message #$msgnum\r\n";
			processMessage($msg,$index,$uid);
			if(!defined $Config{sources}{$index}{keep} || $Config{sources}{$index}{keep}==0){
				print "\t\tDeleting message #$msgnum\r\n";
				$pop->delete($msgnum);
            	}
	    	}
	    }
	else{
		print "No Messages for $server,$user,$pass\r\n" if isDebug();
    	}
	$pop->quit;
	if($dbh){$dbh->disconnect;}
	return 1;
	}
#####################
sub dirMessages{
	my $index=shift;
	$serverIndex=$index;
	#print "dirMessages($index)\r\n";
	my $BaseDir=$Config{sources}{$index}{name} || return abort("dirMessages","no dir name");
	#name="/svn/wasql/pop/bestgazette.com/test/"
	###########################################
	### Connect to database?
    checkDatabase($index);
	###########################################
	### Get the messages. first try authenticated pop and then try normal pop.
	my @dirs=listDirs($BaseDir);
	my $dircnt=@dirs;
	#print "dirMessages: $dircnt dirs\r\n";
	foreach my $dir (@dirs){
		my @files=listFiles($dir,"eml");
		my $filecnt=@files;
		#print "\t[$filecnt]$dir\r\n";
		foreach my $file (@files){
			my $msg=getFileContents("$dir/$file");
			my $len=length($msg);
			if($msg=~/Message\-ID\:\ \<(.+?)\>/is){
				$uid='UIDL-' . md5_base64($msg);
				processMessage($msg,$index,$uid);
				}
			else{printPopLog("FAILURE","dirMessages","No Message-ID",$index,$dir,$file);}
        	}
    	}
	if($dbh){$dbh->disconnect;}
	return 1;
	}
#####################
sub baseMessages{
	my $index=shift;
	$serverIndex=$index;
	#print "dirMessages($index)\r\n";
	my $BaseDir=$Config{sources}{$index}{name} || return abort("baseMessages","no dir name");
	#name="/svn/wasql/pop/bestgazette.com/test/"
	###########################################
	### Connect to database?
    checkDatabase($index);
	###########################################
	### Get the messages. first try authenticated pop and then try normal pop.
	my @servers=listDirs($BaseDir);
	my $dircnt=@servers;
	#print "baseMessages: $dircnt dirs [$BaseDir]\r\n";
	foreach my $server (@servers){
		my @users=listDirs($server);
		#print "baseMessages: [$server][@users]\r\n";
		foreach my $user (@users){
			my @msgdirs=listDirs($user);
			#print "baseMessages: [$user][@msgdirs]\r\n";
			foreach my $msgdir (@msgdirs){
				my @files=listFiles($msgdir,"eml");
				#print "baseMessages: [$msgdir][@files]\r\n";
				foreach my $file (@files){
					my $msg=getFileContents("$msgdir/$file");
					my $len=length($msg);
					if($msg=~/Message\-ID\:\ \<(.+?)\>/is){
						$uid='UIDL-' . md5_base64($msg);
						processMessage($msg,$index,$uid);
						}
					else{printPopLog("FAILURE","baseMessages","No Message-ID",$index,$msgdir,$file);}
		        	}
				}
			}
    	}
	if($dbh){$dbh->disconnect;}
	return 1;
	}
##################
sub processMessage{
	my $msg=shift;
	my $index=shift;
	my $uid=shift;
	$serverIndex=$index;
	### Use MIME::Parser to parse msg into multiple files...
	### Determine BaseDir for parser base/server/user
	my ($user,$server);
	if($Config{sources}{$index}{source}=~/^pop$/is){
		if($Config{sources}{$index}{user}=~/\@/is){($user,$server)=split(/\@/,$Config{sources}{$index}{user},2);}
		else{
			$user=$Config{sources}{$index}{user};
			$server=getUniqueHost($Config{sources}{$index}{name});
	    	}
		}
	elsif($Config{sources}{$index}{source}=~/^dir$/is){
		my @tmp=split(/[\\\/]+/,$Config{sources}{$index}{name});
		$user=pop(@tmp);
		$server=pop(@tmp);

    	}
	my $popBase=$Config{sources}{$index}{base} || $Config{base} || "$progpath/pop";
	my $BaseDir="$popBase/$server/$user";
	if(!-d $BaseDir){buildDir($BaseDir);}
	my %Msg=();
	$Msg{uid}=$uid;
	$Msg{subdir}=$uid;
	$Msg{dir}="$BaseDir/$Msg{subdir}";
	if(-d $Msg{dir}){cleanDir($Msg{dir});}
	else{buildDir($Msg{dir});}
	$parser->output_under($BaseDir,DirName=>$Msg{subdir});
	my $entity = $parser->parse_data($msg);
    #$entity->dump_skeleton if defined $Config{trace} && $Config{trace}==1;
    #Examine parsed files
    my @files=listFiles($Msg{dir});
    @files=sortTextArray(@files);
    my $header=$entity->head;
    my @tmp=@{$entity->head->{mail_hdr_list}};
    my %headers=parseHeader(join('',@tmp));
    foreach my $key (keys(%headers)){
		$Msg{"header\_$key"}=$headers{$key};
        }
    foreach my $file (@files){
		my $afile="$Msg{dir}/$file";
		#get rid of non-ascii characters in filename
		if($file=~/[^a-z0-9\-\_\.]/s){
			my $nfile=$file;
			$nfile=~s/[^a-z0-9\-\_\.]+/\_/isg;
			$nfile=~s/^\_+//isg;
			if(rename($afile,"$Msg{dir}/$nfile")){
				$file=$nfile;
				$afile="$Msg{dir}/$file";
				}
        	}
		my $size=-s $afile;
		my $ext=getFileExtension($file);
		if($file=~/\.avi$/is){
			printPopLog("convert2Flv started",$afile);
            my $flvfile=convert2Flv($afile);
            printPopLog("convert2Flv finished",$afile,$flvfile);
            if(-s $flvfile){
				my $newfile=$file;
				$newfile=~s/\.avi$/\.flv/is;
				$Msg{"attach\_$newfile"}=$flvfile;
				}
			}
		elsif($file=~/^msg\-/is && $ext=~/^(txt|html|htm)$/is){
			#Text
			if(!open(FH,$afile)){
				printPopLog($^E,$afile);
				next;
				}
			my @lines=<FH>;
			close(FH);

			my $contents=join('',@lines);
			$contents=strip($contents);
			if(length($contents) && !defined $Msg{"body\_$ext"}){
				$Msg{"body\_$ext"}=$contents;
                printPopLog("set Msg{body\_$ext}",$ext,length($contents),$afile);
				}
			else{
				printPopLog("did NOT set Msg{body\_$ext}",$ext,length($contents),$size,$afile);
            	}
			}
		elsif(-B $afile){
			$Msg{"attach\_$file"}="$Msg{subdir}/$file";
			#Check for thumbs
			if($file=~/\.(jpg|bmp|png|gif|tif|jpeg)$/is && $Config{sources}{$index}{thumbnails}){
				my @sizes=split(/[\,\;\s]+/,$Config{sources}{$index}{thumbnails});
				makeThumbnails($afile,@sizes);
				my $ext=getFileExtension($file);
				foreach my $size (@sizes){
					my $thumbfile=$file;
					my $thext=$ext;
					if($thext!~/^(jpg|png|gif)$/is){$thext="jpg";}
					$thumbfile=~s/\.\Q$ext\E$/\_$size\.$thext/is;
                    $Msg{"attach\_$thumbfile"}="$Msg{subdir}/$thumbfile";
                	}
            	}
			}
		else{
			$Msg{"attach\_$file"}="$Msg{subdir}/$file";
			}
        }
    # Write a header file
    my $hfile="$Msg{dir}/header.txt";
    if(open(FH,">$hfile")){
		binmode(FH);
		foreach my $line (@{$entity->head->{mail_hdr_list}}){
			$line=strip($line);
			print FH "$line\r\n";
			}
		close(FH);
    	}
    #write a blank index file so the dir is not browsable
    if(!$Config{sources}{$index}{browse} || $Config{sources}{$index}{browse}=~/^(1|true)$/is){
	    if(open(FH,">$Msg{dir}/index.html")){
			binmode(FH);
			print FH qq|<html>\n<head>\n|;
			print FH qq|<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">\n|;
			print FH qq|</head>\n<body>\n|;
			print FH qq|</body>\n</html>\n|;
			close(FH);
			}
		}
    # Write a summary file
    my $sfile="$Msg{dir}/summary.txt";
    if(open(FH,">$sfile")){
		binmode(FH);
		print FH qq|UID: $Msg{uid}\r\n|;
		print FH qq|Date: $Msg{'header_date'}\r\n|;
		print FH qq|From: $Msg{'header_from'}\r\n|;
		print FH qq|To:   $Msg{'header_to'}\r\n|;
		print FH qq|Subj: $Msg{'header_subject'}\r\n|;
		#attachments
		my @attachments=();
		foreach my $key (sort(keys(%Msg))){
			if($key=~/^attach\_(.+)/is){push(@attachments,$1);}
	        }
	    $Msg{attach}=@attachments;
	    $Msg{attach_list}=join("\r\n",@attachments);
	    if($Msg{attach}){
			print FH qq|Attachments [$Msg{attach}]: @attachments\r\n|;
			}
		my $body=$Msg{body_txt} ||  removeHtml($Msg{body_html});
	    $body=~s/[\r\n]/\r\n/sg;
	    $Msg{body_txt}=$body;
		if($body){
			print FH qq|\r\n$body\r\n|;
			}
		close(FH);
		$Msg{summary}="$Msg{subdir}/summary.txt";
		}
    else{return abort($index,$uid,$Msg{subdir},$^E,$hashfile);}
	# Write the raw email file also
    my $rawfile="$Msg{dir}/raw.eml";
    if(open(FH,">$rawfile")){
		binmode(FH);
		print FH @$msg;
		close(FH);
		$Msg{raw}="$Msg{subdir}/raw.eml";
        }
    else{return abort($index,$uid,$Msg{subdir},$^E,$rawfile)}
    #go through the message to make sure it was not forwarded. assign the last from:
    #From: "John Doe" <jondoe@yourdomain.com>
    #From: jondoe@yourdomain.com [mailto:jondoe@yourdomain.com]
    $msgfrom=$Msg{header_from};
    foreach my $msgline (@$msg){
		$msgline=strip($msgline);
		if($msgline=~/^From:\ "(.+?)" \<(.+?)\>/is){$Msg{'header_from'}="$1 <$2>";}
		elsif($msgline=~/^From:\ (.+?) \[mailto\:(.+?)\]/is){$Msg{'header_from'}="$1 <$2>";}
		elsif($msgline=~/^From:\ (.+?) \<(.+?)\>/is){$Msg{'header_from'}="$1 <$2>";}
    	}
    my $table=$Config{sources}{$index}{table} || "email";
    if(defined $Config{sources}{$index}{host} && $dbh){
		#Add to database - email table [to,from,subject,body,uid]
		#Check for valid table
		if(!isDBTable($table)){printPopLog("FAILURE","processMessage","Invalid table: $table",$index,$uid,$Msg{subdir});}
		else{
			#Add record to database
			my $id=addDBData($table,
				msg_uid		=> $Msg{uid},
				msg_to		=> $Msg{header_to},
				msg_from	=> $Msg{header_from},
				msg_subject	=> $Msg{header_subject},
				msg_body	=> $Msg{body_txt},
				msg_dir		=> $Msg{dir},
				msg_subdir	=> $Msg{subdir},
				msg_attach	=> $Msg{attach},
				msg_attach_list	=> $Msg{attach_list},
				msg_priority	=> $Msg{'header_x-priority'},
				);
			if(!isNum($id)){
				printPopLog("FAILURE","processMessage","addDBData",$index,$uid,$Msg{subdir});
				}
			else{
				$Msg{db_id}=$id;
                $Msg{"$table\_id"}=$id;
                printPopLog($id,"processMessage","addDBData",$index,$uid,$Msg{subdir});
				}
			}
        }
    if(defined $Config{sources}{$index}{view}){
		#Post to a url
		my $view=$Config{sources}{$index}{view};
		my @opts=();
		foreach my $opt (keys(%Msg)){
			next if $opt=~/^body\_/is;
			push(@opts,$opt=>$Msg{$opt});
			}
		my $url="http://".$Config{sources}{$index}{host}."/cgi-bin/wasql.pl";
		if($view=~/^http/is){
			$url=$view;
			push(@opts,'message'=>$Msg{body_txt});
			}
		else{push(@opts,'_view'=>$view);}
		my ($hrec,$body,$code,$blen,$header_sent)=postURL($url,@opts);
		if($code != 200){
			printPopLog("$code Error in ViewPage",$Config{sources}{$index}{view},$url,$code);
			printPopLog($header_sent);
			printPopLog("\r\n");
			printPopLog($hrec);
			printPopLog("\r\n");
			printPopLog($body);
        	}
        else{
			printPopLog("Success in ViewPage",$Config{sources}{$index}{view},$url,$code);
			printPopLog($body);
        	}
		$Msg{url_code}=$code;
		$Msg{url_body}=$body;
		if(isNum($Msg{db_id})){
			my $txt=removeHtml($body);
			my $ok=editDBData($table,"_id=$Msg{db_id}",notes=>"Page View Results:\r\n----------\r\n$txt\r\n---------------\r\n");
			printPopLog("editDBData",$ok);
        	}
        }
    printPopLog("Finish","processMessage",$index,$Config{sources}{$index}{host},$Config{sources}{$index}{user},$Msg{url_code},$Msg{db_id});
    return %Msg;
	}
##################
sub makeThumbnails{
	my $afile=shift;
	my @sizes=@_;
	my $ext=getFileExtension($afile);
	my $cnt=0;
	foreach my $size (@sizes){
		my $thumbfile=$afile;
		my $thext=$ext;
		if($thext!~/^(jpg|png|gif)$/is){$thext="jpg";}
		$thumbfile=~s/\.\Q$ext\E$/\_$size\.$thext/is;
		my $cmd=qq|convert -thumbnail $size "$afile" "$thumbfile"|;
		printPopLog($cmd);
		system($cmd);
		$cnt++;
    	}
    return $cnt;
	}
##################
sub convert2Flv{
	my $afile=shift;
	#ffmpeg -y -i /path/to/avi/12.avi -acodec mp3 -ar 22050 -f flv /path/to/flv/14.flv
	my $ext=getFileExtension($afile);
	my $flvfile=$afile;
	$flvfile=~s/\.$ext$/\.flv/is;
	my $cmd=qq|ffmpeg -y -i "$afile" -ar 22050 -f flv "$flvfile"|;
	printPopLog($cmd);
	system($cmd);
	if(-s $flvfile){unlink($afile);}
    return $flvfile;
	}

##################
sub checkConfig{
	if(!-s "$progpath/$progname\.xml"){
		return abort("No xml defined");
		}
	my $xmldata=getFileContents("$progpath/$progname\.xml");
	$xmldata=evalPerl($xmldata);
	%Config=readXML($xmldata,"config");
	#process servers
	#<server name="mail.bestgazette.com" user="test@bestgazette.com" pass="test88" />
	my $sources=$Config{sources};
	undef($Config{sources});
	my $sIndex=0;
	while($sources=~/\<(pop|dir|base)(.+?)\>/sig){
		my $source=lc(strip($1));
		$Config{sources}{$sIndex}{source}=$source;
		my $attributes=strip($2);
		while($attributes=~m/([a-z\_\-]+?)([\=\s]*?)\"(.*?)\"/sig){
			my $key=lc(strip($1));
			next if $key=~/^source$/is;
			my $val=strip($3);
			$Config{sources}{$sIndex}{$key}=$val;
			}
		$sIndex++;
    	}
    return 1;
	}
##################
sub printPopLog{
	my @msgs=@_;
	#writeLog
	my $writeLog=0;
	if(isNum($Config{log}) && $Config{log}==1){$writeLog++;}
	if(isNum($Config{sources}{$serverIndex}{log}) && $Config{sources}{$serverIndex}{log}==1){$writeLog++;}
	#
	#print "printPopLog [$debug][$writeLog][$serverIndex]\r\n";
	print join(',',@msgs) . "\r\n" if isDebug();
	if($writeLog){
		my $ok=printLog(@msgs);
		if(!isNum($ok)){print "printLog Error: $ok\r\n";}
    	}
    return 1;
	}
##################
sub isDebug{
	my $debug=0;
	if(isNum($Config{debug}) && $Config{debug}==1){$debug++;}
	if(isNum($Config{sources}{$serverIndex}{debug}) && $Config{sources}{$serverIndex}{debug}==1){$debug++;}
	return $debug;
	}
##################
sub abort{
	my @msgs=@_;
	printPopLog("ABORT",@msgs);
	print "Abort: @msgs\r\n";
	exit(1);
	}
#############
BEGIN {
	#add path to INC
	my @parts=split(/\:/,$ENV{PATH});
	foreach $part (@parts){
    	unshift(@INC,$part);
	}
	our ($temp_dir,$progpath,$progexe,$progname,$isexe)=('','','','',0);
	$temp_dir = ( $ENV{TEMP} || $ENV{TMP} || $ENV{WINDIR} || '/tmp' ) . "/p2xtmp-$$";
	$0 = $^X unless ($^X =~ m%(^|[/\\])(perl)|(perl.exe)$%i);
	($progpath) = $0 =~ m%^(.*)[/\\]%;
	$progpath ||= ".";
	unshift(@INC,$progpath);
	$progname=lc($0);
	if($progname=~/[\/\\]/){
		my @stmp=split(/[\/\\]/,$progname);
		$progname=pop(@stmp);
		}
	$progname=~s/\Q$progpath\E//s;
	$progname=~s/^[\\\/]+//s;
	if($progname=~/(.+?)\.(exe|pl|so)/is){$progname=$1;}
	if ($^X =~ /(perl)|(perl\.exe)$/i) {
		$progexe=$progname . '.pl';
		$isexe=0;
		}
	else{
		#Compiled
		$isexe=1;
		@INC=($progpath,"./lib","./","PERL2EXE_STORAGE",$temp_dir);
		$progexe=$progname . '.exe';
		}
	}
###########
END {
	if($dbh){$dbh->disconnect;}
	}


