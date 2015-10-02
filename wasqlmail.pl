#!/usr/bin/perl
require 'subs_common.pl';
require 'subs_socket.pl';
require 'subs_email.pl';
#################################################################
###  Use Statements
use Socket;
use Digest::MD5 qw(md5 md5_hex md5_base64);
use CGI qw(:cgi-lib redirect);
#################################################################
### Read in input hash
our $cgi = new CGI;
our %input = $cgi->Vars;
#lowercase the input keys
foreach my $key (keys(%input)){
	my $lkey=lc($key);
	if(!defined $input{$lkey}){
		$input{$lkey}=$input{$key};
		delete($input{$key});
		}
	}
my @input_keys = $cgi->param;
my $input_count=@input_keys;
my $input_fields=join(':',@input_keys);
my $input_first=$input_keys[0];
if($input{_view}){$input_first=$input{_view};}
#Require the request to come from our server
$host=$ENV{'HTTP_HOST'};
$ref=$ENV{'HTTP_REFERER'};
$server=$ENV{'SERVER_NAME'};
#build sendopts
my %sendopts=(
	to=>$input{to},
	from=>$input{from},
	subject=>$input{subject},
	message=>$input{message},
	);
my @optkeys=qw(smtp smtpuser smtppass attach priority inline);
foreach my $optkey (@optkeys){
	if($input{$optkey}){$sendopts{$optkey}=$input{$optkey};}
	}
my $xmltags='';
foreach my $key (keys(%sendopts)){
	next if $key=~/^message$/is;
	$val=xmlEncodeCDATA($sendopts{$key});
	$xmltags .= "	<$key>$val</$key>\r\n";
	}

if($server=~/^\Q$host\E$/is){}
elsif($ref!~/^(http|https):\/\/\Q$host\E/is){
	print "Content-Type: text/xml\n\n";
	print "<$progname>\r\n";
	print "	<error>Invalid Request - remote requests are not allowed. [$ref][$host]</error>\r\n";
	print $xmltags;
	print "<$progname>\r\n";
	exit;
	}

$ck=sendMail(%sendopts);
if($input{redirect}){
	#print "Content-Type: text/html\n\n";
	print "Location: $input{redirect}\n\n";
	exit;
	}
else{
	print "Content-Type: text/xml\n\n";
	print "<$progname>\r\n";
	if(isNum($ck)){
		print "	<success>$ck</success>\r\n";
		print $xmltags;
		}
	else{
		print "	<error>$ck</error>\r\n";
		print $xmltags;
		}
	print "</$progname>\r\n";
	}
exit;
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