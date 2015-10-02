#!/usr/bin/perl
#################################################################
#pop.pl - used to pop email and add it to wasql databases
#################################################################  
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
### Usage: - Note: you CAN run multiple cron.pl processes without worry of conflicts
# cron.pl &  - run in the background and process any crons found in any hosts in the config.xml file
# cron.pl host=localhost - only process crons found in the localhost database
# cron.pl name=bob - only process crons named bob
# cron.pl host=localhost name=bob - only process crons found in the localhost database where name=bob
# cron.pl minutes=8 - run 8 minutes and then stop
# cron.pl echo=1 - echo the elapsed minutes each loop
# cron.pl runonce=1 - run once stop
# wget command to pipe output to stdout:
#		wget -q -O - http://www.mydomain.com/cron_name
### Use and requires
$|=1;
#use Cwd;
#$progname="cron";
#our $progpath=getcwd();
if(!-s "$progpath/subs_common.pl" && -s "$progpath/wasql/subs_common.pl"){
	$progpath="$progpath/wasql";
	}
#print "progpath: $progpath\n";
#exit;
require "$progpath/subs_common.pl";
require "$progpath/subs_database.pl";
#our $pidfile="cron_".time().".pid";
#setFileContents($pidfile,$$);
#run abort if the script get interupted
$SIG{'INT'} = 'abort';
#read any filters
our %filter=();
foreach my $arg (@ARGV){
	my ($key,$val)=split(/\=/,$arg);
	$key=lc(strip($key));
	$val=strip($val);
	next if !length($key) || !length($val);
	$filter{$key}=$val;
	}
#get the process id of the current process
our $cron_pid=$$;
#pause
#select(undef,undef,undef,2);
#################################################################
###  Read Configuration File to determine db to connect to
my $starttime=time();
while(1){
	#Read Config.xml
	my %Config=readConfig();
	#print hashValues(\%Config);
	#exit;
	#update the pidfile so we can tell it is active
	#setFileContents($pidfile,$cron_pid);
	my %runcheck=();
	foreach my $host (keys(%Config)){
		#skip if host filter is set and $host does not match filter
		if(length($filter{host}) && $filter{host}!~/^\Q$host\E$/i){
			#print "skipping $host - filter mismatch\n";
			next;
        	}
        #skip this host if nocron is set
        next if $Config{$host}{nocron};
        #skip this host if cron=0
        next if defined $Config{$host}{cron} && $Config{$host}{cron}==0;
        #skip this host is stage and cron is not set
        next if $Config{$host}{stage} && !$Config{$host}{cron};
        #check for dups
        $runcheck_key=$Config{$host}{dbhost}.$Config{$host}{dbname}.$Config{$host}{dbtype};
        if(length($runcheck{$runcheck_key})){
			#if($filter{echo}){print " - skipping dup entry - $runcheck_key\n";}
			next;
			}
		$runcheck{$runcheck_key}=1;
		#connect to this database and check for the _cron table
		#if($filter{echo}){print "Connecting to db on $host - $Config{$host}{dbname}\n";}
		our $dbh=eval{
			my $verbose=0;
			if($filter{echo}){$verbose=1;}
			our @dbopts=(
				dbhost=>$Config{$host}{dbhost},
				dbname=>$Config{$host}{dbname},
				dbtype=>$Config{$host}{dbtype},
				dbuser=>$Config{$host}{dbuser} || $Config{$host}{dbusername},
				dbpass=>$Config{$host}{dbpass} || $Config{$host}{dbpassword},
				verbose=>$verbose
				);
			our $err = dbConnect(@dbopts);
			if(!$dbh){return undef;}
			return $dbh;
			};
		if($@){
			if($filter{echo}){print "Failed connecting to db ".$Config{$host}{dbname}." on $host\n";}
			next;
			}
		if(!$dbh){
			#print "skipping - unable to connect\n";
			if($filter{echo}){print "Failed: No DB  on $host :: $err\n";}
			next;
	    	}
	    if(!isDBTable("_cron")){
			#if($filter{echo}){print "Skipped $host - cron not set\n";}
			#print "skipping - no _cron table\n";
			next;
			}
		if($filter{echo}){print "Checking settings on $host\n";}
		#check to see if cron is on or off.  skip if off.
		my %rec=getDBRecord(-table=>"_settings",'key_name'=>"wasql_crons",'user_id'=>0);
		next if !isNum($rec{key_value}) || $rec{key_value} != 1;
		if($filter{echo}){print "Collecting jobs on $host\n";}
		#print "$host...";
		#print "Reading Cron Jobs\n";
		#Get a list of record Ids that are active
	    my %recs=getDBRecords(-table=>"_cron",'-query'=>"select name,_id,frequency,UNIX_TIMESTAMP(run_date) as run_date_utime,running from _cron where active=1 order by _id");
	    if($filter{echo}){
			print " - Found " .$recs{count}. " cron jobs to check\n";
			print " - Run:" .$filter{run}. "\n";
			}
	    for(my $x=0;$x<$recs{count};$x++){
			#skip jobs currently running if they are newer than their frequency
			if($recs{$x}{running}==1){
				next if !isNum($recs{$x}{frequency});
				next if !isNum($recs{$x}{run_date_utime});
				$age=time()-($recs{$x}{running}*60);
				next if $age < $recs{$x}{run_date_utime};
            	}
			my $recid=$recs{$x}{_id};
			#get the whole cron record
			my %rec=getDBRecord('-table'=>"_cron",'_id'=>$recid);
			next if !isNum($rec{_id});
			#check for name filter
			$name=$rec{name};
			if($filter{echo}){
				print " - name:$name\n";
				}
			if(length($filter{name}) && $filter{name}!~/^\Q$name\E$/i){
				#print " - skipping $name - filter mismatch\n";
				next;
				}
			#skip if  not active anymore
			next if $rec{active}==0;
			#skip if run_command is blank
			my $run_cmd=strip($rec{run_cmd});
			next if !length($run_cmd);
			my $ctime=time();
			#skip jobs that have a begin_date in the future
			next if isNum($rec{begin_date}) && $ctime < $rec{begin_date};
			#skip jobs that have a end_date in the past
			next if isNum($rec{end_date}) && $ctime > $rec{end_date};
			#skip if currently running
			if($rec{running}==1){
				next if !isNum($rec{frequency});
				next if !isNum($rec{run_date_utime});
				$age=time()-($rec{running}*60);
				next if $age < $rec{run_date_utime};
            	}
			#Check to see if we should run this cron now
			my $do=0;
			#check for Frequency setting - use it if it is set
			if(length($filter{run}) && $filter{run}=~/^\Q$name\E$/i){
				if($filter{echo}){print " - run filter match\n";}
				$do=1;
				}
			elsif(isNum($rec{frequency}) && $rec{frequency} > 0){
				#frequency if set - check to see if time has passed since the last run
				if(!isNum($rec{run_date_utime})){$do=1;}
				else{
					my $utime=time();
					my $diff=int(($utime - $rec{run_date_utime})/60);
					$freq=$rec{frequency};
					#print " - Frequency Check: $diff >= $freq\n";
					if($diff >= $freq){
						if($filter{echo}){print " - frequency match\n";}
						$do=1;
						}
					}
	        	}
	        elsif(length($rec{run_format})  && length($rec{run_values})){
				my $run_format=$rec{run_format};
				my $checkval=getDate($run_format);
				#check to see if it has been run in the last 5 minutes
				my $valcheck=1;
				if(isNum($rec{run_date_utime})){
					my $utime=time();
					my $diff_seconds=$utime - $rec{run_date_utime};
					my $diff_minutes=int($diff_seconds/60);
					if($diff_minutes < 5){$do=0;$valcheck=0;}
					if($filter{echo}){print " - diff seconds = $diff_seconds, diff minutes = $diff_minutes\n";}
                	}
                if($filter{echo}){print " - valcheck = $valcheck\n";}
                if($valcheck==1){
					my @run_values=split(/[\=\,]/,$rec{run_values});
					foreach my $val (@run_values){
						if($filter{echo}){print " - checking run_format - $run_format -- $checkval = $val\n";}
						if(($run_format=~/^MM$/s && $val=~/\*/) || $val=~/^\Q$checkval\E$/is){
							if($filter{echo}){print " - run_format match\n";}
							$do=1;
							}
						}
					}
				}
			if($filter{echo}){print " - Do:$do\n";}

			#run the cron if $do==1
			if($do==1){
				#skip if running
				my %cronrec=getDBRecord('-table'=>"_cron",'_id'=>$recid);
				next if !isNum($cronrec{_id});
				if($cronrec{running}==1){
					next if !isNum($cronrec{frequency});
					next if !isNum($cronrec{run_date_utime});
					$age=time()-($cronrec{running}*60);
					next if $age < $cronrec{run_date_utime};
	            	}
				#set to running
				$rundate=getDate("YYYY-NM-ND MH:MM:SS");
				my $ok=editDBData("_cron","_id=$recid",cron_pid=>$cron_pid,running=>1,run_date=>$rundate);
				#wait a second and check to see if I am the cron owner after all - in case two crons set the cron_pid at the same time
				select(undef,undef,undef,1);
				my %cronrec=getDBRecord('-table'=>"_cron",'_id'=>$recid);
				next if !isNum($cronrec{_id});
				next if $cronrec{cron_pid} != $cron_pid;
				#ok It i got here then I need to run the cron job
				#if run command is a page, then prepend the wget to it
				my %pagerec=getDBRecord('-table'=>"_pages",'name'=>$run_cmd);
				if(isNum($pagerec{_id})){
					$ispage=1;
					if($^O =~ /^MSWIN32$/is){
						#use wget.pl on windows
						$run_cmd="perl $progpath/wget.pl url=http://".$host."/".$run_cmd;
					}
					else{
						##wget -q -O - http://www.mydomain.com/cron_name
                    	$run_cmd="wget -q -O - http://".$host."/".$run_cmd;
					}

				}
				$name=$rec{name};
				if($filter{echo}){print " - *** RUNNING $name ***\n";}
				#how many crons are currently running
				my $count_crons=getDBCount("_cron");
				my $count_cronlogs=getDBCount("_cronlog")+1;
				my $count_crons_active=getDBCount("_cron",'active'=>1);
				my $count_crons_inactive=getDBCount("_cron",'active'=>0);
				my $count_crons_running=getDBCount("_cron",'running'=>1);
				my $count_crons_listening=getProcessCount('cron.pl');
				#Time to run
				my $start_time=time();
				my @rlines=cmdResults($run_cmd,$progpath,'',$filter{echo});
				my $stop_time=time();
				my $run_length=$stop_time-$start_time;
				#print " - Run Length: $run_length=$stop_time-$start_time\n";
				my @tmp=();
				foreach my $rline (@rlines){
					$rline=strip($rline);
					next if !length($rline);
					next if $rline=~/^Content-type/i;
					push(@tmp,$rline);
					}
				my $result=join("\r\n",@tmp);
				#add _cronlog record
				my $ok=addDBData("_cronlog",
					cron_run_count=>$running_count,
					cron_id=>$recid,
					cron_pid=>$cron_pid,
					run_date=>$rundate,
					run_length=>$run_length,
					name=>$name,
					run_cmd=>$run_cmd,
					run_result=>$result,
					run_length=>$run_length,
					count_crons=>$count_crons,
					count_crons_active=>$count_crons_active,
					count_crons_inactive=>$count_crons_inactive,
					count_crons_running=>$count_crons_running,
					count_crons_listening=>$count_crons_listening,
					count_cronlogs=>$count_cronlogs,
					);
				#update the record with the run_date and run_result
				#wrap the result
				$result=strip($result);
				$xml = '<run_result run_cmd="'.$run_cmd.'" ispage="'.$ispage.'" utime="'.time().'" timestamp="'.localtime().'" runtime="'.$run_length.'">'."\r\n";
				$xml .= "$result\r\n";
				$xml .= "</run_result>\r\n\r\n";
				if(isNum($rec{run_log}) && $rec{run_log}==1 && length($rec{run_result})){
					#append the result to the
					$xml .= $rec{run_result};
                	}
				my $ok=editDBData("_cron","_id=$recid",
					running		=> 0,
					run_result	=> $xml,
					run_length	=> $run_length
					);
				#write a logfile if specified
				if(length($result) && $rec{logfile}){
					if(-s $rec{logfile} && isNum($rec{logfile_maxsize})){
						my $size=-s $rec{logfile};
                    	if($size > $rec{logfile_maxsize}){
							my $backupnum=1;
							my $backupfile='';
							while(1){
								$backupfile=$rec{logfile} . ".backup".$backupnum;
								last if !-e $backupfile;
								$backupnum++;
                            	}
                            rename($rec{logfile},$backupfile);
                        	}
						}
					my $logfile=$rec{logfile};
					if(open(LF,">>$logfile")){
						binmode LF;
						print LF $xml;
						close(LF);
                    	}
                	}
				#print " - Done\n";
				}
	    	}
	    if($dbh){$dbh->disconnect;}
		}
	last if $debug==1;
	last if $filter{runonce};
	#sleep for 60 seconds
	minuteSleep();

	$endtime=time();
	$minutes=int(($endtime-$starttime)/60);
	if($filter{echo}){print "$minutes minutes elapsed so far.\n";}
	last if isNum($filter{minutes}) && $minutes >= $filter{minutes};
	}
#unlink($pidfile);
exit;
sub minuteSleep{
	if($filter{echo}){print "sleeping for 60 seconds\n";}
	for(my $x=0;$x<60;$x++){
		select(undef,undef,undef,1);
	}
	return 1;
}
###########
sub readConfig{
	#usage: our %Config=getConfig($file[,$host]);
	if(-s "$progpath/config.xml"){
		my $data=getFileContents("$progpath/config.xml");
		$data=evalPerl($data);
		my %xml=readXML($data,'hosts');
		#print hashValues(\%xml);
		#exit;
		my %ConfigXml=();
		my %allhosts=();
		if(defined $xml{allhost}){
			foreach my $key (keys(%{$xml{allhost}})){
				$key=lc(strip($key));
				$allhosts{$key}=strip($xml{allhost}{$key});
            	}
        	}
        foreach my $key (keys(%xml)){
			next if $key!~/^host/is;
			my $cname=strip(lc($xml{$key}{name}));
			next if !length($cname);
			foreach my $skey (keys(%allhosts)){
				my $ckey=lc(strip($skey));
				$ConfigXml{$cname}{$ckey}=strip($allhosts{$skey});
	            }
            foreach my $skey (keys(%{$xml{$key}})){
				my $ckey=lc(strip($skey));
				$ConfigXml{$cname}{$ckey}=strip($xml{$key}{$skey});
	            }
	       }
		return %ConfigXml;
    	}
    return "No config.xml";
	}
############
sub abort {
	my $ctime=localtime();
	my $utime=time();
	my $msg=shift;
	#unlink($pidfile);
	print "\n$utime,$ctime,$msg\r\n";
	exit(1);
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


