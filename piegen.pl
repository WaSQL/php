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
