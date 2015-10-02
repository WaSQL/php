#!/usr/bin/perl
#wb.pl - WaSQL benchmark utility
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
use CGI qw/:standard/;
use Time::HiRes;
our %input=();
our $input_count = CGI::ReadParse(\%input);
require 'subs_socket.pl';
require 'subs_common.pl';
$|=1;
print header();
print "<html>\n";
print "<head>\n";
print qq|<style type="text/css">\n|;
print qq|	form{margin:0px;padding:0px;}\n|;
print qq|	table th{background:#F4F4F0;font-size:11pt;}\n|;
print qq|	table td{background:#FFF;font-size:10pt;}\n|;
print qq|	select, input{font-size:9pt;}\n|;
print qq|	.das{background:#F4F4F0;}\n|;
print "</style>\n";
print "</head>\n";
print "<body>\n";
if(!$input{bench}){print qq|<div><img src="/wfiles/wasql.gif" border="0" style="float:left;margin:4px"><b> Benchmark Utility</b></div>\n|;}
#print hashValues(\%input);
if($input{bench}){
	my $cnt=$input{times} || 5;
	#print "<pre>\n";
	my ($head,$body,$code)=getURL("http://$input{domain}/$input{page}");
	#print "</pre>\n";
	#print "<pre><xmp>$code</xmp></pre>\n<hr><hr>";
	my @pairs=();
	if($head=~/Set\-Cookie\:(.+?)\;/is){
		push(@pairs,_cookie=>$1);
		#print "@pairs<br>\n";
		}
	my $url=$input{url} || "http://$input{domain}/$input{page}";
	print qq|<div><b>URL: </b>$url</div>\n|;
	my $threadcnt=$input{threads} || 1;
	print qq|<table cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse"><tr>\n|;
	for(my $x=0;$x<$threadcnt;$x++){
		my $rcnt=$x+1;
		print qq|<td>Thread $rcnt<br><span id="thread$x"></span></td>\n|;
		}
	print "</tr><tr>\n";
	for(my $x=0;$x<$threadcnt;$x++){
		my $rcnt=$x+1;
		print qq|<td id="sum$x"></td>\n|;
		}
	print "</tr></table>\n";
	for(my $x=0;$x<$threadcnt;$x++){
		my $divid="thread$x";
		my $cid=fork();
		if($cid){next;}
		my %Total=();
		for(my $x=0;$x<$cnt;$x++){
			my $start=Time::HiRes::time();
			#print "<pre>\n";
			my ($head,$body,$code)=getURL($url,@pairs,_showhead=>0);
			#print "</pre>\n";
			my $stop=Time::HiRes::time();
			my $diff=$stop-$start;
			$Total{$code}{cnt}++;
			$Total{total}{cnt}++;
			$Total{$code}{time} +=$diff;
			$Total{total}{time} +=$diff;
			if(!length($Total{$code}{min}) || $diff < $Total{$code}{min}){$Total{$code}{min}=$diff;}
			if(!length($Total{$code}{max}) || $diff > $Total{$code}{min}){$Total{$code}{max}=$diff;}
			my $rx=$x+1;
			my $sdiff=sprintf("%.4f",$diff);
			print qq|<script language="javascript">document.getElementById('$divid').innerHTML='<b>Requests: </b>$rx/$cnt <b>Time:</b> $sdiff'\;</script>\n|;
	    	}
	    #Now show the results
	    my $table='';
	    $table .= qq|<div id="sumtable$x" style="display:none">\n<table cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse">\n|;
	    $table .= qq|<tr><th>code</th><th>cnt</th><th>mean</th><th>min</th><th>max</th></tr>\n|;
	    foreach my $code (sort(keys(%Total))){
			my $cnt=$Total{$code}{cnt};
			my $mtime=sprintf("%.4f",($Total{$code}{time}/$Total{$code}{cnt}));
			my $min=sprintf("%.4f",$Total{$code}{min});
			my $max=sprintf("%.4f",$Total{$code}{max});
	        $table .= qq|<tr><td>$code</td><td>$cnt</td><td>$mtime</td><td>$min</td><td>$max</td></tr>\n|;
	    	}
	    $table .= qq|</table>\n|;
	    $table .= qq|</div>\n|;
	    print $table;
        print qq|<script language="javascript">document.getElementById('sum$x').innerHTML=document.getElementById('sumtable$x').innerHTML</script>\n|;
	    }
	}
else{
	#my $url="
	#my ($head,$body,$code)=getURL($url
	print '<form method="POST" action="/cgi-bin/wb.pl" target="benchmark">';
	print '<input type="hidden" name="bench" value="1">';
	print '<table cellpadding="2" cellspacing="0" border="1" style="margin:10px;border-collapse:collapse;background:#FFF;">' . "\n";
	print "\t<tr valign=\"top\">\n";
	#Domain
	print "\t\t<th>Domain<br>\n";
	print qq|<input type="text" name="domain" style="width:100px;font-size:9pt" maxlength="255" value="$ENV{HTTP_HOST}">|;
	print "\t\t</th>\n";
	#page
	print "\t\t<th>Page<br>\n";
	print qq|<input type="text" name="page" style="width:100px;font-size:9pt" maxlength="255" value="cgi-bin/wasql.pl?1">|;
	print "\t\t</th>\n";
	#Concurrent
	print "\t\t<th>Times<br>\n";
	print '<select name="times">';
	print '<option value="1">1</option>';
	print '<option value="2">2</option>';
	print '<option value="10" selected>10</option>';
	print '<option value="50">50</option>';
	print '<option value="100">100</option>';
	print '</select>';
	print "\t\t</th>\n";
	print "\t\t<th>Threads<br>\n";
	print '<select name="threads">';
	print '<option value="1" selected>1</option>';
	print '<option value="3">3</option>';
	print '<option value="5">5</option>';
	print '</select>';
	print "\t\t</th>\n";
	#Run
	print "\t\t<th><br><input type=\"submit\" value=\"Run\"></th>\n";
	print "\t</tr>\n";
	print '<tr><td colspan="5">URL: <input type="text" name="url" style="width:330px;" maxlength="255"></td></tr>';
	print '</table>';
	print '</form>';
	#print '<div id="benchmark"></div>' . "\n";
	print '<iframe name="benchmark" id="benchmark" src="about:blank" frameborder="0" framepadding="0" width="100%" height="400px;"></div>' . "\n";
	print "</body>\n";
	print "</html>\n";
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