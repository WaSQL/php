#!/usr/bin/perl
#usage: stock.pl?sym=GM
use Socket;
$progname="geturl";
$|=1;
require "subs_common.pl";
require "subs_socket.pl";
our $cgiroot=$ENV{SCRIPT_NAME};
use CGI qw(:cgi-lib redirect);
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
my $url=$input{url} || shift;
delete $input{url};
our @inp=%input;

print "Content-type: text/html\n\n";
if($url){
	my ($head,$body,$code)=postURL($url,@inp);
	if($code != 200){
		print qq|<center style="color:red">$code error: Service is currently unavailable</center>\n|;
		print qq|<div align="center" id="error">$body</div>\n|;
		exit;
		}
	print $body;
	}
else{
	print "ERROR: url not supplied\n";
	}
exit;
#############
BEGIN {
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
