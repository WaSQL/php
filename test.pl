#!/usr/bin/perl
$|=1;
binmode STDOUT;
require 'subs_common.pl';
require 'subs_socket.pl';
my $x=getProcessCount('cron.pl');
print "x = $x\n";
exit;

@lines=getFileContents("$progpath/GP2010.html");
my %chapter=();
my $num=0;
foreach my $line (@lines){
	$line=fixMicrosoft(strip(removeHtml($line)));
	next if !length($line);
	if($line=~/^Chapter ([0-9]+) (.+)/is){
		$num=$1;
		$lesson{$num}{title}=$2;
		next;
		}
	next if !$num;
	$line=~s/^([0-9]+?).*//s;
	$lesson{$num}{content}.=$line;
	if($line=~/[.?!]$/s){$lesson{$num}{content}.="<br />\r\n";}
	else{$lesson{$num}{content}.=" ";}
	}
my @chapters=keys(%lesson);
@chapters=sortTextArray(@chapters);
$url="http://stage.dearscriptures.com/import";
foreach my $chapter (@chapters){
	my $title=$lesson{$chapter}{title};
	print "Uploading Chapter $chapter - $title\n";
	my ($head,$body,$code)=postURL($url,title=>$title,chapter=>$chapter,body=>$lesson{$chapter}{content});
	print "\t$code: $body\n"
	}
exit;
print hashValues($lesson{4});
exit;
my $dir='G:\SVN\Dev\custom\Websites\trackmyscout.com\mb\images';
my @lines=getFileContents("$dir/index.txt");
shift(@lines);
my %mb=();
foreach my $line (@lines){
	$line=strip($line);
	next if $line=~/(old|replaced)/is;
	my @tmp=split(/\.+/,$line);
	my $cnt=@tmp;
	my $name=strip($tmp[0]);
	next if !length($name);
	$mb{$name}{number}=strip($tmp[1]);
	$mb{$name}{image}="/mb/images/" . strip($tmp[2]) . ".gif";
	my $worksheet=$name;
    $worksheet=~s/\ /\-/sg;
    $mb{$name}{worksheet}="/mb/worksheets/" . $worksheet . ".pdf";
	}

@lines=getFileContents("$progpath/since.txt");
my $cnt=@lines;
print "parsing since[$cnt]\n";
foreach my $line (@lines){
	$line=strip($line);
	my @tmp=split(/\t+/,$line);
	my $cnt=@tmp;
	my $name=strip($tmp[0]);
	my $number=strip($tmp[1]);
	next if !length($name);
	next if !isNum($mb{$name}{number});
	next if $mb{$name}{number} != $number;
	$mb{$name}{since}=strip($tmp[2]) . "-01-01";
	}
my @names=keys(%mb);
@names=sortTextArray(@names);
print "name,number,image,worksheet,since\n";
foreach my $name (@names){
	next if !isNum($mb{$name}{number});
	print "$name,$mb{$name}{number},$mb{$name}{image},$mb{$name}{worksheet},$mb{$name}{since}\n";
	}
exit;

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

