#!/usr/bin/perl
use lib qw( /home/username/perlmods/lib/perl/5.10 /home/username/perlmods/lib/perl/5.10.0
            /home/username/perlmods/share/perl/5.10 /home/username/perlmods/share/perl/5.10.0 );
print "Content-type: text/plain\n\n";
require "$progpath/subs_common.pl";
use Spreadsheet::ParseExcel::Simple;
use CGI qw(:cgi-lib redirect);
use Data::Dumper;
#################################################################
### Read in input hash
my $cgi = new CGI;
my %input = $cgi->Vars;
#lowercase the input keys
foreach my $key (keys(%input)){
	my $lkey=lc($key);
	if(!defined $input{$lkey}){
		$input{$lkey}=$input{$key};
		delete($input{$key});
		}
	}
if(!$input{file}){
	print "ERROR: No File specified";
	exit(1);
	}
if(!-e $input{file}){
	print "ERROR: No such file. " . $input{file};
	exit(1);
	}
if(!-s $input{file}){
	print "ERROR: Empty file. " . $input{file};
	exit(1);
	}
my $afile=$input{file};
my $xls = Spreadsheet::ParseExcel::Simple->read($afile) || die $^E;
my @rows=();
foreach my $sheet ($xls->sheets) {
	while ($sheet->has_data) {
		my @row=$sheet->next_row;
		my $line=formatCsv(@row);
    	push(@rows,$line);
		}
	}
foreach my $line (@rows){
	print "$line\r\n";
	}
exit;
#############
BEGIN {
	#add path to INC
	my @parts=split(/\:/,$ENV{PATH});
	foreach $part (@parts){
    	unshift(@INC,$part);
	}
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
