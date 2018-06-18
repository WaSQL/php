#!/usr/bin/perl
#This is used to extract EXIF data out of any file and return the results in XML format - easy to read into PHP
if($ENV{HTTP_HOST}){print "Content-type: text/xml\n\n";}
require "$progpath/subs_common.pl";
use CGI qw(:cgi-lib redirect);
use Image::ExifTool;
#################################################################
### Read in input hash
my $cgi = new CGI;
my %input = $cgi->Vars;
$input{keywords}=undef;
#lowercase the input keys
foreach my $key (keys(%input)){
	my $lkey=lc($key);
	if(!defined $input{$lkey}){
		$input{$lkey}=$input{$key};
		delete($input{$key});
	}
}
if(!$input{file}){
	$input{file} = shift;
	#read any other inputs into
	while(1){
		$arg = shift;
		if(!defined $arg || !length($arg)){
			last;
		}
		my($k,$v)=split(/\=/,$arg,2);
		$input{$k}=$v;
	}
	if(!$input{file}){
		print "ERROR: No File specified";
		exit(1);
	}
}
my $keycount=0;
foreach my $k(keys(%input)){
	my $v=$input{$k};
	if(length($v)){	
		#print "key:$k = $v\r\n";
		$keycount++;
	}
}
if(!-e $input{file}){
	print "ERROR: No such file: " . $input{file};
	exit(1);
}
if(!-s $input{file}){
	print "ERROR: Empty file: " . $input{file};
	exit(1);
}
my $afile=$input{file};
my $exifTool = new Image::ExifTool;
$exifTool->Options(Unknown => 1, WriteMode => 'wcg');
if($keycount > 1){
	#write exif
	$exifTool->ExtractInfo($afile);
	$exifTool->SetNewValue('Make'); # Deleting this tag magically makes it work
	foreach my $k (keys(%input)){
		my $v=$input{$k};
		print "key:$k = $v\r\n";
		if($k ne 'file' && length($v)){
			print "Writing to file:$k =  $v \r\n";
			my $success = $exifTool->SetNewValue($k,$v);
		}
	}
	$success = $exifTool->WriteInfo($afile);
	if($success){
		print "Changes saved\r\n";
	}
}
print "<root>\r\n";
print "	<_input>\r\n";
print "		<file>".$input{file}."</file>\r\n";
print "		<name>".getFileName($input{file})."</name>\r\n";
print "		<path>".getFilePath($input{file})."</path>\r\n";
print "		<keycount>".$keycount."</keycount>\r\n";
print "	</_input>\r\n";

my $info = $exifTool->ImageInfo($afile);
my $tag;
my %exif=();
foreach $tag ($exifTool->GetFoundTags('Group0')) {
    my $group = strip($exifTool->GetGroup($tag));
    my $val = $info->{$tag};
    #clean up binary data
    next if ref $val eq 'SCALAR';
    $tag=strip($tag);
    $tag=~s/\([0-9]+\)$//s;
    $exif{$group}{$tag}=$val;
}
foreach my $group (sort(keys(%exif))){
	$group=xmlEncodeCDATA($group);
	print "		<$group>\n";
	foreach my $key (sort(keys(%{$exif{$group}}))){
    	$val=$exif{$group}{$key};
    	$val=xmlEncodeCDATA($val);
		print "			<$key>".$val."</$key>\r\n";
	}
	print "		</$group>\r\n";
}
print "</root>\r\n";
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