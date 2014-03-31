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
our @inp = $cgi->Vars;
#lowercase the input keys
foreach my $key (keys(%input)){
	my $lkey=lc($key);
	if(!defined $input{$lkey}){
		$input{$lkey}=$input{$key};
		delete($input{$key});
		}
	}
print "Content-type: text/html\n\n";
print <<ENDOFSTYLE;
<style type="text/css">
	body,h3 {margin:0px;padding:0px;}
	form {margin:0px;padding:0px;}
	form input{font-size:10pt;}
	a {text-decoration:none;color:#336699;}
	a:hover{text-decoration:underline;}
	#hash2Html tr.odd {background:#F1F5FA;}
	#hash2Html td {font-size:10pt;}
</style>
ENDOFSTYLE
if(!defined $input{hide} || $input{hide}!~/form/is){
	print qq|<div>Enter the tickers name you are interested in below:</div>\n|;
	print searchForm();
	}
#http://download.finance.yahoo.com/d/quotes.csv?s=GM&f=sl1d1t1c1ohgv&e=.xml
my $url="http://download.finance.yahoo.com/d/quotes.csv";
if(!defined $input{ticker} || !length(strip($input{ticker}))){exit;}
my %list=();
my @tickers=split(/[\ \t\,\;]+/,$input{ticker});
my $tickerlist=join(' ',@tickers);
my ($head,$body,$code)=postURL($url,f=>"snl1d1t1c1ohgvjkex",e=>".xml",s=>$tickerlist);
if($code != 200){
	print qq|<center style="color:red">$code error: Service is currently unavailable</center>\n|;
	print qq|<div align="center" id="error">$body</div>\n|;
	exit;
	}
my @lines=split(/[\r\n]+/,$body);
my $x=0;
foreach my $line (@lines){
	#"GM",6.25,"10/28/2008","4:01pm",+0.80,5.95,6.25,5.6199,24413744
	next if !length(strip($line));
	#parse the csv line into a hash row
	($list{$x}{ticker},$list{$x}{name},$list{$x}{price},$list{$x}{date},$list{$x}{time},$list{$x}{change},$list{$x}{open},$list{$x}{dayhigh},$list{$x}{daylow},$list{$x}{volume},$list{$x}{yearhigh},$list{$x}{yearlow},$list{$x}{eps},$list{$x}{exchange})=csvParseLine($line);
	$x++;
	}
$list{count}=$x;
$list{'fields'}=[qw(ticker name price exchange volume change eps open dayhigh daylow yearhigh yearlow date time)];
print hash2Html(\%list,
	title=>"<h3>Stock Quote Service</h3>",
	price_align=>"right",
	volume_align=>"right",
	yearhigh_title=>"Year High",
	yearlow_title=>"Year Low",
	dayhigh_title=>"Day High",
	daylow_title=>"Day Low",
	dayhigh_align=>"right",
	daylow_align=>"right",
	yearhigh_align=>"right",
	yearlow_align=>"right",
	open_align=>"right",
	open_title=>"Day Open",
	eps_title=>"EPS",
	eps_tip=>"Earnings Per Share",
	volume_tip=>"Number of shares traded today",
	change_align=>"right",
	price_format=>"<b>\$ \%.2f</b>",
	dayhigh_format=>"\$ \%.2f",
	daylow_format=>"\$ \%.2f",
	yearhigh_format=>"\$ \%.2f",
	yearlow_format=>"\$ \%.2f",
	open_format=>"\$ \%.2f",
	change_sub=>"formatChange",
	volume_sub=>"formatComma",
	ticker_href=>"http://finance.google.com/finance?q=%ticker%",
	@inp
	);
print qq|<div style="font-size:9pt;margin-left:25px;">\n|;
print qq|	powered by <a href="http://finance.yahoo.com">Yahoo Finance</a>\n|;
print qq|	, <a href="http://finance.google.com">Google Finance</a>\n|;
print qq|	and <a href="http://www.wasql.com">WaSQL</a>\n|;
print qq|	</div>\n|;
exit;
#############
sub formatChange{
	my $change=shift;
	if($change=~/\+/is){return qq|<b style="color:green">$change</b>|;}
	if($change=~/\-/is){return qq|<b style="color:red">$change</b>|;}
	return $change;
	}
sub searchForm{
	my $form='';
	$form .= qq|<form name="stockform" method="GET" action="$cgiroot">\n|;
	$form .= qq|<input type="text" style="width:200px;" name="ticker" value="$input{ticker}">\n|;
    $form .= qq|<input type="checkbox" value="form" name="hide" checked>\n|;
	$form .= qq|<input type="submit" value="Get Quotes">\n|;
	$form .= qq|</form>\n|;
	$form .= qq|<script language="javascript">document.stockform.ticker.focus();</script>\n|;
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
