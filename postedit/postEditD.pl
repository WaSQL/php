#!/usr/bin/perl
#####################################################
### Compile Options - These options are stamped into the exe when compiled
#perl2exe_info CompileOptions -v -opt -tiny -icon=postedit.ico
#perl2exe_bundle "postedit.ico"
#perl2exe_info CompanyName=http://www.wasql.com
our $CompanyName="http://www.wasql.com";
#perl2exe_info FileDescription=WaSQL Edit Manager for DOS/Wine
our $FileDescription="WaSQL Edit Manager for DOS/Wine";
#perl2exe_info OriginalFilename=postEdit.exe
our $OriginalFilename="postEdit.exe";
#perl2exe_info InternalName=postEdit
our $InternalName="postEdit";
#perl2exe_info ProductName=WaSQL Edit Manager for DOS/Wine
our $ProductName="WaSQL Edit Manager for DOS/Wine";
#perl2exe_info ProductVersion=1.267.84
our $ProductVersion="1.267.84";
#perl2exe_info FileVersion=1.1601.11
our $FileVersion="1.1601.11";
#perl2exe_info LegalCopyright=Copyright 2004-2012, WaSQL.com
our $LegalCopyright="Copyright 2004-2012, WaSQL.com";
#################################################################
#  WaSQL - Copyright 2004 - 2012 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
# HTML Validation: http://validator.w3.org/#validate_by_input
# CSS Validation: http://jigsaw.w3.org/css-validator/#validate_by_input
#################################################################
#####################################################
our $CompanyName="http://www.wasql.com";
our $FileDescription="WaSQL Edit Manager";
our $OriginalFilename="postEdit.exe";
our $InternalName="postEdit";
our $ProductName="WaSQL Edit Manager";
our $ProductVersion="1.825.22";
our $FileVersion="1.1207.04";
our $LegalCopyright="Copyright 2004-2012, WaSQL.com";
use Cwd 'abs_path';
use Socket;
#determine the progpath
$progpath = abs_path($0) ;
$progpath =~ m/^(.*)(\\|\/)(.*)\.([a-z]*)/;
if($progpath=~/\.(exe|pl)/i){
	my @tmp=split(/[\\\/]+/,$progpath);
	my $name=pop(@tmp);
	if($^O =~ /^MSWIN32$/is){$progpath=join("\\",@tmp);}
	else{$progpath=join('/',@tmp);}
}
require '../subs_common.pl';
require '../subs_socket.pl';
#read any filters
our $lockfile='';
our %input=();
foreach my $arg (@ARGV){
	my ($key,$val)=split(/\=/,$arg);
	$key=lc(strip($key));
	$val=strip($val);
	next if !length($key) || !length($val);
	$input{$key}=$val;
	}
$progname="postedit";
my $xmlfile="$progpath/$progname\.xml";
my $xmlold="$progpath/$progname\.xml.old";
my $rsyncfile="$progpath/$progname\.rsync";
if(-s $rsyncfile){
	$url=getFileContents($rsyncfile);
	my ($head,$body,$code,$blen,$header_sent,$c)=postURL($url,_method=>"GET");
	if(-s $xmlfile && !-s $xmlold){rename($xmlfile,$xmlold);}
	if($code == 200 && open(FH,">$xmlfile")){
		binmode(FH);
		if($header->{content-type}=~/text/is){
			my @lines=split(/[\r\n]+/,$body);
			foreach my $line (@lines){print FH $line . "\r\n";}
    	}
		else{print FH $body;}
		close(FH);
	}
}

if(!-s $xmlfile){
	print "Missing $xmlfile\n";
	exit;
	}
###################################
our $baseDir="$progpath\\postEditFiles";
if(!-d $baseDir){buildDir($baseDir);}
our $filesDir=$baseDir;
our %xml=readXML($xmlfile,'hosts');
our %settings=readXML($xmlfile,'settings');
our %watchtable=();
###################################
$|=1;
our $update=1;
our $inimtime=0;
our @inilines=();
our %Watch=();
our %FileCount=();
#remove old log file if it exists
unlink("$progpath/$progname\.log") if -e "$progpath/$progname\.log";
#listen for this domain now
our $group='';
my $xgroup=$input{group};
our $host='';
my $xhost=$input{domain} || $input{host};
print "xGroup:$xgroup, xHost:$xhost\n";
my @groups=getGroups();
if(length($xgroup) && in_array($xgroup,@groups)){
	$group=$xgroup;
	my @domains=getDomains($group);
	if(length($xhost) && in_array($xhost,@domains)){
    	$host=$xhost;
	}
}
print "Group:$group, Host:$host\n";
while(!length($group) || !length($host)){
	$group=getUserInput("Select a group",@groups);
	my @domains=getDomains($group);
	push(@domains,'-BACK to GROUP-');
	$host=getUserInput("Select a Domain",@domains);
	if($host eq '-BACK to GROUP-'){$group='';$host='';}
	}
print "Group:$group, Host:$host\n";
createWasqlFiles($host);
print "Listening for changes (CTRL-C to exit)\n";
while(1){
	#call timer array
	WatchDir_Timer();
	#delay for 400 milliseconds
	select(undef,undef,undef,.4);
}
exit(0);
#############
#############
sub getGroups{
	my %hash=();
	foreach my $host (keys(%xml)){
		my $group=$xml{$host}{group} || "Other";
		$hash{$group}++;
		}
	my @groups=keys(%hash);
	@groups=sortTextArray(@groups);
	return @groups;
	}
#############
sub getDomains{
	my $group=shift || return;
	my %hash=();
	foreach my $host (keys(%xml)){
		my $cgroup=$xml{$host}{group} || "Other";
		if(isEqual($cgroup,$group)){
			my $domain=$xml{$host}{alias} || $xml{$host}{name};
			$hash{$domain}++;
			}
		}
	my @domains=keys(%hash);
	@domains=sortTextArray(@domains);
	return @domains;
	}
#############
sub alertMessage{
	my $message=shift || return 0;
	print $message;
	return 0;
	}
#############
sub abortMessage{
	my $message=shift || return 0;
	print $message;
	exit(1);
	}
#############
sub setTitle{
	return;
}
#############
sub setColorBox{
	return;
}
#############
sub getDomainHost{
	my $name=shift;
	foreach my $chost (keys(%xml)){
		if($xml{$chost}{alias}=~/^\Q$name\E$/is){
			return $chost;
        	}
		elsif($xml{$chost}{name}=~/^\Q$name\E$/is){
			return $chost;
        	}
    	}
    return '';
	}
#############
sub createWasqlFiles{
	my $name=shift || return;
	print "createWasqlFiles: $name\n";
	my @wasql_dirs=();
	my $wasql_user='';
	my $wasql_group='';
	#determine what host
	my $host=getDomainHost($name) || return 1;
	return 1 if !$xml{$host}{apikey};
	return 1 if !$xml{$host}{username};
	my $new_lockfile="$progpath/wasql_".$name.".lock";
	if(length($lockfile)!=0 && encodeBase64($lockfile) == encodeBase64($new_lockfile)){
     	#do nothing - just a refresh of the same domain
	}
	else{
		if(-e $new_lockfile){
			alertMessage("A $ProductName instance is already running for $name\r\nI am not able to comply.");
	     	return 1;
		}
	}
	%Watch=();
	my $chost=$xml{$host}{name};
	my $newtitle=$chost;
	if($xml{$host}{alias}){$newtitle=$xml{$host}{alias};}
	if($xml{$host}{group}){$newtitle .= ' ('.$xml{$host}{group}.')';}
	setTitle($newtitle);
	#print " - Cleaning $filesDir\n";
	my $cdir=$xml{$host}{alias} || $chost;
	$cdir=~s/[^a-z0-9\s\.\(\)\_\-]+//ig;
	$filesDir="$baseDir/$cdir";
	if(!-d $filesDir){buildDir($filesDir);}
	else{cleanDir($filesDir,1);}
	#remove directories inside also

	$apikey=$xml{$host}{apikey};
	$username=$xml{$host}{username};
	my $tables=$xml{$host}{tables};
	#remove extra dirs that do not match up
	my @tdirs=split(/[\s\,\;]+/,strip($tables));
	my %istdir=();
	foreach my $tdir (@tdirs){
		$istdir{$tdir}=1;
		}
	my @dirs=listDirs($filesDir,0,1);
	foreach my $dir (@dirs){
		if(!$istdir{$dir}){rmDir("$filesDir/$dir");}
    	}
	#set key/values to pass to postURL
	my %postopts=(apikey=>$apikey,username=>$username,_noguid=>1,postedittables=>$tables,apimethod=>"posteditxml",_sub=>"winEvents");
	my $url="http://$chost/php/index.php";
	#check for alternate port
	if($chost=~/^(.+?)\:([0-9]+)$/s){
		$postopts{_port}=$2;
		$chost=$1;
		$url="http://$chost/php/index.php";
    	}
    setColorBox('working','Calling API...');
	#print "calling $url\n";
	#$postopts{_debug}=1;
	#print hashValues(\%postopts);
	my ($head,$body,$code)=postURL($url,%postopts);
	appendMessage("$code - Saving Results");
	setFileContents("$progpath/postedit.result",$body);
	if($code==200 && $body=~/\<\?xml/is){
		if(-e $lockfile){unlink($lockfile);}
		$lockfile=$new_lockfile;
		$ok=setFileContents($lockfile,time());
		appendMessage("Parsing XML ...");
		#check for fatal errors from server
		if($body=~/<fatal_error>(.+?)<\/fatal_error>/is){
			abortMessage($1);
			return 0;
			}
		if($body=~/<wasql_dirs>(.+?)<\/wasql_dirs>/is){
			@wasql_dirs=split(/\,+/,$1);
			}
		#set user
		if($body=~/<uid_name>(.+?)<\/uid_name>/is){
			$wasql_user=$1;
			}
		elsif($body=~/<uid>(.+?)<\/uid>/is){
			$wasql_user=$1;
			}
		#set group
		if($body=~/<gid_name>(.+?)<\/gid_name>/is){
			$wasql_group=$1;
			}
		elsif($body=~/<gid>(.+?)<\/gid>/is){
			$wasql_group=$1;
			}
		setColorBox('working','Parsing...');
		#Build a page hash will all the pages
		%watchtable=();
		appendMessage("Parsing results ...");
		while($body=~m/\<WASQL_RECORD(.*?)>(.+?)\<\/WASQL_RECORD\>/sig){
			my %att=parseAttributes($1);
			my $content=$2;
			my $table=$att{table};
			my $id=$att{_id};
			#print "Getting $table results for record $id\n";
			foreach my $key (keys(%att)){
				$watchtable{$table}{$id}{$key}=$att{$key};
				#print "watchtable{$table}{$id}{$key} = " . $watchtable{$table}{$id}{$key} . "\n";;
				}
			my @xmlfields=split(/\,/,$att{_xmlfields});
			foreach my $field (@xmlfields){
                while($content=~m/\<$table\_$field>(.*?)\<\/$table\_$field\>/sig){
					my $val=removeCDATA($1);
					$watchtable{$table}{$id}{$field}=$val;
                    }
                }
            }
        #write the page files
        #setColorBox('working','Writing...');
        appendMessage("Writing local files to $filesDir\n");
        foreach my $table (keys(%watchtable)){
			foreach my $id (keys(%{$watchtable{$table}})){
				my $fname=$watchtable{$table}{$id}{name};
				$fname=~s/[\ \-\_]+/\_/sg;
				$fname=~s/[^a-z0-9\_]+//isg;
				my @xmlfields=split(/\,/,$watchtable{$table}{$id}{_xmlfields});
				foreach my $field (@xmlfields){
					my $ext=getExtension($watchtable{$table}{$id}{$field});
					my $filename="$fname\.$table\.$field\.$id\.$ext";
					my $pdir="$filesDir\\$table";
					if(!-d $pdir){buildDir($pdir);}
					my $pfile="$filesDir\\$table\\$filename";
					if($^O !~ /^MSWIN32$/is){
						#convert slashes to linux
						my $slash='/';
						my @parts=split(/[\\\/]+/,$pfile);
						$pfile=join($slash,@parts);
					}
					#print "Writing $pfile\n";
					if(open(FH,">$pfile")){
						binmode(FH);
						print FH strip($watchtable{$table}{$id}{$field}) . "\r\n";
						close(FH);
						if($watchtable{$table}{$id}{atime} && $watchtable{$table}{$id}{mtime}){
							utime($watchtable{$table}{$id}{atime},$watchtable{$table}{$id}{mtime},$pfile);
							}
						elsif($watchtable{$table}{$id}{ctime} && $watchtable{$table}{$id}{mtime}){
							utime($watchtable{$table}{$id}{ctime},$watchtable{$table}{$id}{mtime},$pfile);
							}
						elsif($watchtable{$table}{$id}{mtime}){
							utime(time(),$watchtable{$table}{$id}{mtime},$pfile);
							}
						#appendMessage(" - $filename");
						my %stat=fileStats(-file=>$pfile);
                    	$Watch{$pfile}=$stat{mtime};
                    	}
					else{
                    	#print " - Unable to write file\n";
					}
					}
				}
        	}
        #setColorBox('success','Completed.');
 		#appendMessage("Update Complete.",1000);
 		}
 	else{
		#Update Failed - show error
		#setColorBox("Fail",'Error');
		#appendMessage($body);
		appendMessage("Update Error - $url");
		print hashValues(\%postopts);
    	}
	#wasql files?
	my $wasql_file_cnt=@wasql_dirs;
	}
#############
#############
sub WatchDir_Timer{
	my @watchFiles=keys(%Watch);
	my $watchcnt=@watchFiles;
	foreach my $afile (keys(%Watch)){
        my %stat=fileStats(-file=>$afile);
		my $mtime=$stat{mtime};
		if($Watch{$afile} != $mtime){
            if(length($Watch{$afile})){fileChanged(getFileName($afile));}
            $Watch{$afile}=$mtime;
        	}
        select(undef,undef,undef,.015);
    	}
	return 1;
	}
#############
sub fileChanged{
	my $file=shift;
	my ($fname,$table,$field,$id,$ext)=split(/\./,$file);
	print "WaSQL detected the following file has changed: $fname\n";
	#print "fileChanged: $fname in $table\n";
	my $name=$host;
	my $key=getDomainHost($name) || return;
	my $host=$xml{$key}{name};
	my $apikey=$xml{$key}{apikey};
	my $username=$xml{$key}{username};
	my $url="http://$host/php/index.php";
	#print " - $url\n";
	$pfile="$filesDir\\$table\\$file";
	if($^O !~ /^MSWIN32$/is){
		#convert slashes to linux
		my $slash='/';
		my @parts=split(/[\\\/]+/,$pfile);
		$pfile=join($slash,@parts);
	}
	my $content=getFileContents($pfile);
	if(!isNum($id)){
		print "[$id] is not a number\r\n";
		return 0;
    	}
	if(!length($content)){
		print "No content for $id!!!\r\n";
		return 0;
		}
	#checks
	if($file=~/\.pl$/is && 1==2){
		setColorBox('working','Perl Syntax');
		appendMessage("Checking Perl Syntax");
		#check for the correct perl syntax before uploading...
		showBalloon($balloonMsg,"Checking Perl Syntax...","warning");
		my @errors=checkPerlSyntax($content);
		if(scalar @errors){
			showBalloon($balloonMsg."@errors","Checking Perl Syntax...Perl Errors!","error");
			#restoreWindow();
			setMessage("Perl Syntax Failed");
			setColorBox("Fail");
			appendMessage("$file\r\n@errors");
			if($settings{sound}{fail}){
				playSound($settings{sound}{fail});
				}
			return 0;
        	}
        setColorBox("Pass");
        appendMessage("Perl Syntax Passed");
    	}
    if($file=~/\.php$/is && 1==2){
		setColorBox('working','PHP Syntax');
		appendMessage("Checking PHP Syntax");
		#check for the correct perl syntax before uploading...
		showBalloon($balloonMsg,"Checking PHP Syntax...","warning");
		my @errors=checkPHPSyntax($file,$table);
		if(scalar @errors){
			showBalloon($balloonMsg."@errors","Checking PHP Syntax...PHP Errors!","error");
			#restoreWindow();
			setMessage("PHP Syntax Failed");
			setColorBox("Fail");
			appendMessage("$file\r\n@errors");
			if($settings{sound}{fail}){
				playSound($settings{sound}{fail});
				}
			return 0;
        	}
        setColorBox("Pass");
        appendMessage("PHP Syntax Passed");
    	}
    #
	my $timestamp=$watchtable{$table}{$id}{timestamp};
	my %postopts=(apikey=>$apikey,username=>$username,_noguid=>1,_id=>$id,timestamp=>$timestamp,_action=>'postEdit',_table=>$table,_fields=>$field,$field=>$content,_return=>'XML',_sub=>"winEvents");
	#check for alternate port
	if($host=~/^(.+?)\:([0-9]+)$/s){
		$postopts{_port}=$2;
		$host=$1;
		$url="http://$host/php/index.php";
    	}
    #print hashValues(\%postopts);
	appendMessage("Sending changes to $url ...");
	#print "timestamp:$timestamp\n";
	sendEditChanges:
	setColorBox('working','Sending Changes');
	showBalloon($balloonMsg,"Sending Changes to Server...","warning");
	my ($head,$body,$code)=postURL($url,%postopts);
	#print "body:\n$body\n";
	appendMessage("Return Code: $code");
	if($code != 200){
		$err=strip($head);
		showBalloon($balloonMsg."$err","Sending Changes to Server...Failed!","error");
		appendMessage("Update Failed");
		setColorBox("Fail",'Update Failed');
		appendMessage("$file\r\n$head");
		if($settings{sound}{fail}){
			playSound($settings{sound}{fail});
		}
		return 1;
    	}
    elsif($body=~/<fatal_error>(.+?)<\/fatal_error>/is){
		my $err=strip($1);
		showBalloon($balloonMsg."$err","Sending Changes to Server...Failed!","error");
		#restoreWindow();
		abortMessage($err);
		if($settings{sound}{fail}){
			playSound($settings{sound}{fail});
		}
		return 0;
		}
    elsif($body=~/<error>(.+?)<\/error>/is){
		$err=strip($1);
		#restoreWindow();
		appendMessage("ERROR in $file");
		appendMessage($err);
		return 1;
    	}
    else{
		if($body=~/<timestamp>(.+?)<\/timestamp>/is){
			my $ts=$1;
			$watchtable{$table}{$id}{timestamp}=$ts;
			}
		showBalloon("");
		showBalloon("SUCCESSFULLY Updated $file");
		if($settings{sound}{success}){
			playSound($settings{sound}{success});
			}
		}
	return 1;
	}
#############
sub winEvents{
	return;
	my $msg=shift;
	$msg=strip($msg);
	print "$msg\n";
}
#############
sub appendMessage{
	my $msg=shift;
	$msg=strip($msg);
	print "$msg\n";
}
#############
sub showBalloon{
	my $msg=shift;
	$msg=strip($msg);
	print "$msg\n";
}
#############
sub checkPHPSyntax{
	my $file=shift;
	my $table=shift;
	my $cmd=qq|php -l $file|;
	my @errors=();
	my @lines=cmdResults($cmd,"$filesDir\\$table");
	foreach my $line (@lines){
		if($line=~/No syntax errors detected/is){return @errors;}
    	}
	return @lines;
	}
#############
sub checkPerlSyntax{
	my $str=shift;
	my @errors=();
	while ($str=~m/\<perl(.*?)\>(.*?)\<\/perl\>/sig){
		my $code=strip($2);
		$perltags++;
		#remove any beginning and ending comments <!--  -->
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		if(length($code)){
			$code="return;$code";
			eval($code);
			if($@){
				#return the error message.
				my @lines=split(/[\r\n]+/,$@);
				my $err=join("\r\n",@lines);
				push(@errors,$err);
                }
			}
		}
	while ($str=~m/\<\?(.*?)\?\>/sig){
		my $code=strip($1);
		#Do not process xml defination strings as Perl
		next if $code=~/^(xml|php)\ /is;
		$perltags++;
		#remove any beginning and ending comments <!--  -->
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		if(length($code)){
			$code="return;$code";
			eval($code);
			if($@){
				#return the error message.
				my @lines=split(/[\r\n]+/,$@);
				my $err=join("\r\n",@lines);
				push(@errors,$err);
                }
			}
		}
	return @errors;
	}
#############
sub getExtension{
	my $content=shift;
	$content=strip($content);
	if($content=~m/^(.{0,5})filetype\:([a-z0-9]+)\*/is){return $2;}
	while($content=~m/<\?(.+?)\?\>/sig){
		my $code=$1;
		if($code=~m/\*filetype\:(.+?)\*/is){return $1;}
		return 'php';
		my @lines=split(/[\r\n]+/,$code);
		foreach my $line (@lines){
			$line=strip($line);
			if($line=~/^(function|echo)\ /){return 'php';}
			if($line=~/^(sub|my|require|use|our)\ /){return 'pl';}
			if($line=~/^(END|BEGIN)/){return 'pl';}
			if($line=~/^\#\!\//){return 'pl';}

        	}
		return "php";
		}
	return "php";
	if(strip($content)=~/^function\ /s){return "js";}
	if(isHtml($content)){return 'html';}
	return 'txt';
	}
###############
sub playSound{
	return;
	}
#############
END{
	if(-e $lockfile){unlink($lockfile);}
}
#############
BEGIN {
	#add path to INC
	my @parts=split(/\:/,$ENV{PATH});
	foreach $part (@parts){
    	unshift(@INC,$part);
	}
	#call exit when CTRL-C is used to abort - this way we cleanup properly
	$SIG{INT} = sub {exit(1);};
	our ($temp_dir,$progpath,$progexe,$progname,$isexe)=('','','','',0);
	$temp_dir = ( $ENV{TEMP} || $ENV{TMP} || $ENV{WINDIR} || '/tmp' ) . "/p2xtmp-$$";
	$0 = $^X unless ($^X =~ m%(^|[/\\])(perl)|(perl.exe)$%i);
	($progpath) = $0 =~ m%^(.*)[/\\]%;
	$progpath ||= ".";
	$progname=$0;
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
		$progexe=$progname . '.exe';
		}
	if(-e "$progpath/$progname\_exit\.txt"){unlink("$progpath/$progname\_exit\.txt");}
	}
