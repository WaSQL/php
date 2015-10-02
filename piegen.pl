#!/usr/bin/perl
use GD::Graph::pie;
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
my $graph = new GD::Graph::pie(170,80,1);
my @data=();
if(!$input{data}){
	@data  = (
	    ["Error","",""],
	    [100,0,0]
		);
	}
else{
	my @parts=split(/\:/,$input{data},3);
	@data  = (
	    ["Open","Resolved","Closed"],
	    [@parts]
		);
	}
$graph->set(
	dclrs 	=> [qw(lred lorange green)],
	'3d'	=> 1
	);
my $gd_image = $graph->plot( \@data );
print "Content-type: image/gif\n\n";
binmode STDOUT;
print $gd_image->gif;
exit(0);
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
