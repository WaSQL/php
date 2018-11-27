#!/usr/bin/perl
#usage: wcmd.pl cmd path
# Example Usage from PHP
#	<?php
#		$out=cmdResults('perl','c:\wasql\wcmd.pl "git config --list" "c:\commissions\core"');
#		echo "<pre>{$out['stdout']}</pre>";
#	?>
$progname="wcmd";
$|=1;
print cmdResults(@ARGV[0],@ARGV[1]);
exit;
###############
sub cmdResults{
	#usage: my @lines=cmdResults($command[,$dir,"subname"]);
	#info: executes command in $dir and sends each line returned to sub called 'subname' as it happens.
	#tags: system
	
	my $cmd=shift || return 'No cmd';
	my $dir=shift;
	my $sub=shift;
	my $debug=shift;
	my $olddir;
	if($^O =~ /^MSWIN32$/is && $cmd=~/^\.\//){$cmd=~s/^\.\///s;}
	if($dir){$olddir=chdir($dir);}
	if($debug){print "cmdResults:\nCmd:$cmd\nDir:$dir\nSub:$sub\n";}
	my @lines=();
	if(open(RS,"$cmd 2>&1 |")){
		# Disable buffering on handle
		if($debug){print " - running\n";}
		my $cur = select(RS);
		$| = 1;
    	select($cur);
		while(<RS>){
			my $line=$_;
			if($debug){print "\t\t".strip($line)."\n";}
			if(length(strip($line))){push(@lines,$line);}
			#if sub pass line to sub.
			if($sub){&$sub($_);}
			}
		close(RS);
		}
	else{
		if($debug){print " - Error:".$^E."\n";}
		return $^E;
		}
	#get exit code. 1=errors, 0=command successful;
	my $exitcode = $? >> 8;
	if($dir){chdir($olddir);}
	if(wantarray){return @lines;}
	return join('',@lines);
	}
###############
sub strip{
	#usage: $str=strip($str);
	#info: strips off beginning and endings returns, newlines, tabs, and spaces
	#tags: strip
	my $str=shift;
	if(length($str)==0){return;}
	$str=~s/^[\r\n\s\t]+//s;
	$str=~s/[\r\n\s\t]+$//s;
	return $str;
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
###########
