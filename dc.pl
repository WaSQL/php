#!/usr/bin/perl
#usage: checkdomain.pl
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
print "Content-type: text/html\n\n";
print qq|<html>\n<head><title>Domain Check</title>\n|;
print <<ENDOFSTYLE;
	<style type="text/css">
		body {margin:0px;padding:2px;}
		form {margin:0px;padding:2px;}
		.u {background:#FFE8E8;}
		.a {background:#CEFFCE;}
	</style>
ENDOFSTYLE
print qq|</head>\n<body>\n|;
print qq|<h3>Domain Check Service</h3>\n|;
print qq|<div>Enter the domain name you are interested in below:</div>\n|;
print searchForm();
my $url="http://www.instantdomainsearch.com/services/quick/";
#http://instantdomainsearch.com/services/rest/?name=safe
#return: {'name':'safe','com':'u','net':'u','org':'u'}
if(!defined $input{dom}){exit;}
if(!length(strip($input{dom}))){exit;}
my ($head,$body,$code)=postURL($url,name=>$input{dom});
if($code != 200){
	print qq|<center style="color:red">$code error: Service is currently unavailable</center>\n|;
	print qq|<div align="center" id="error">$body</div>\n|;
	print qq|</body>\n</html>\n|;
	exit;
	}
#{'name':'safe','com':'u','net':'u','org':'u'}
if($body=~/\{\'name\'\:\'(.+?)\'\,\'com\'\:\'(.+?)\'\,\'net\'\:\'(.+?)\'\,\'org\'\:\'(.+?)\'\}/is){
	my $dom=$input{dom};
	my %map=(
	u=>'already taken',
	a=>qq|available<br><br><center><a target="_new" href="http://www.dreamhost.com/r.cgi?210166/domreg.cgi?domain=$dom\[\]">Register with Dreamhost</a></center><br>|,
	);
	my ($dom,$com,$net,$org)=($1,$2,$3,$4);
	print qq|<table cellspacing="5" cellpadding="4" border="1" style="border-collapse:collapse">\n|;
	print qq|	<tr><th colspan="3">Name: $dom</th></tr>\n|;
	print qq|	<tr align="center">\n|;
	my $mcom=$map{$com};
	$mcom=~s/\[\]/\.com/sg;
	print qq|		<td class="$com"><a href="http://www.$dom\.com" target="_dom">$dom\.com</a><br> is $mcom</td>\n|;
	my $mnet=$map{$net};
	$mnet=~s/\[\]/\.net/sg;
	print qq|		<td class="$net"><a href="http://www.$dom\.net" target="_dom">$dom\.net</a><br> is $mnet</td>\n|;
	my $morg=$map{$org};
	$morg=~s/\[\]/\.org/sg;
	print qq|		<td class="$org"><a href="http://www.$dom\.org" target="_dom">$dom\.org</a><br> is $morg</td>\n|;
	print qq|	</tr>\n|;
	print qq|</table>\n|;

	print qq|</body>\n</html>\n|;
	exit;
	}
else{
	print qq|<div style="color:red;margin-left:50px;">$code error: invalid return</div>\n|;
	print qq|<div>$body</div>|;
	print qq|</body>\n</html>\n|;
	exit;
	}
exit;
#############
sub searchForm{
	my $form='';
	$form .= qq|<form name="checkdom" method="post" action="$cgiroot">\n|;
	$form .= qq|Name: <input type="text" style="width:200px;font-size:11pt;" name="dom">\n|;
	$form .= qq|<input type="submit" value="Check">\n|;
	$form .= qq|</form>\n|;
	$form .= qq|<script language="javascript">document.checkdom.dom.focus();</script>\n|;
	return $form;
	}
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
