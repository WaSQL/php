#!/usr/bin/perl
$|=1;
binmode STDOUT;
require 'subs_common.pl';
require 'subs_socket.pl';
my $base=shift || '192.168.1';
my $start=shift || 1;
my $stop=shift || 255;
print "ip_address,computer_name\r\n";
for($x=$start;$x<=$stop;$x++){
	my $ip=$base.'.'.$x;
	my $name=getHostName($ip);
	next if!length($name);
 	print "$ip,$name\r\n";
}
exit(0);

#############
BEGIN {
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
		}
	}

