#!/usr/bin/perl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
###  Variable Initialization
$|=1;
#version - major, minor, patch, build
$version="1.9.0.146";
#################################################################
###  Use Statements
use Socket;
use CGI qw(:cgi-lib redirect);
#################################################################
### Read in input hash
our $cgi = new CGI;
our %input = $cgi->Vars;
#lowercase the input keys
foreach my $key (keys(%input)){
	my $lkey=lc($key);
	if(!defined $input{$lkey}){
		$input{$lkey}=$input{$key};
		delete($input{$key});
		}
	}
my @input_keys = $cgi->param;
my $input_count=@input_keys;
my $input_fields=join(':',@input_keys);
my $input_first=$input_keys[0];
if($input{_view}){$input_first=$input{_view};}
#################################################################
### Verify DBI is installed
eval('use DBI;');
if($@){abort("DBI is not installed");}
#################################################################
###  Load required subs and evaluate them for syntax
our @reqFiles=("subs_common.pl","subs_wasql.pl","subs_database.pl","subs_socket.pl");
foreach my $req (@reqFiles){
	my $evalstr=qq|require '$req'|;
	eval($evalstr);
	if ($@){
		my $val=formatError($@);
		printHead("Code Error!");
		print $val;
		printFooter();
		exit;
		}
	}
#################################################################
### Set Document Root for MS IIS servers
if(!$ENV{DOCUMENT_ROOT} && $ENV{SERVER_SOFTWARE}=~/Microsoft-IIS/is){$ENV{DOCUMENT_ROOT}='C:\Inetpub\wwwroot';}
our $docroot='';
if($ENV{'DOCUMENT_ROOT'} ne ""){$docroot=$ENV{'DOCUMENT_ROOT'};}
else{$docroot=$ENV{PATH_TRANSLATED};}
$docroot=~s/\\/\//g;
$docroot=~s/\/$//;
$docroot=~s/\/+/\//g;
#################################################################
###  Set Environment Variables and Global Variables
if($ENV{SCRIPT_URL}){$ENV{SCRIPT_NAME}=$ENV{SCRIPT_URL};}
our %HEADER=(); #Hash to determine what header information to write
&parseEnv();
#get GUID
&getGuid();
our $cgiroot=$ENV{SCRIPT_NAME};
$ENV{WaSQL_Version}=$version;
$ENV{ShowSystemErrors}=1;
$ENV{SystemError}='';
$blank="\&" . "nbsp" . "\;";
%PAGE=();	#stores current page information if viewing a page.
%XML=();
%RESULT=(); #stores the result of what we did - success or error message.
$Operation;
$Message;
#################################################################
### Show environment
if(isNum($input{_env})){
	&printHeader();
	print qq|<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">\n|;
	print qq|<html>\n|;
	print qq|<head>\n|;
	print qq|<title>WaSQL - Environment</title>\n|;
	print &waSQLCssJs();
	print qq|</head>\n|;
	print qq|<body class="w_body">\n|;
	print &Env();
	printFooter();
	exit;
	}
#################################################################
###  Read Configuration File to determine db to connect to
our %Config=getConfig();
if(defined $Config{err}){abort($Config{err});}
if(defined $Config{lib}){
	my @libs=split(/[\,\ \;]+/,$Config{lib});
	unshift(@INC,@libs);
	}
#################################################################
### check to see if this site is under maintenance in the config file
if($Config{'maintanence'}){
	&printHeader();
	print underMaintenance($Config{'maintanence'});
	exit;
    }
#################################################################
###  Connect to the database
$dbname=$Config{database} || $Config{dbname} || abort("No database for $ENV{UNIQUE_HOST} in wasql.conf<br>\n$Config{err}<hr>");
$dbt=$Config{dbtype} || $Config{dbt} || "SQLite";
$dbhost=$Config{dbhost} || "localhost";
my @dbopts=(
	dbhost=>$dbhost,
	dbname=>$dbname,
	dbtype=>$dbt,
	dbuser=>$Config{dbuser} || $Config{dbusername},
	dbpass=>$Config{dbpass} || $Config{dbpassword},
	verbose=>0
	);
my $err = dbConnect(@dbopts);
if(!$dbh){abort("Database Connection Failure",qq|<div style="display:none">\n@dbopts\n</div>|,$err);}
#################################################################
###  Setup Database if there are no wasql tables found
&setupWaSQL();
#################################################################
###  Store current table data in a META hash for quicker reference
%META=();	#stores current tables metadata
if($input{_table}){
	$METAcnt=getDBData(\%META,"select * from _fielddata where tablename='$input{_table}'");
	}
#################################################################
### translate view if it is not a number
if($input{_view} && !isNum($input{_view})){
	my %rec=getDBRecord(-table=>"_pages",name=>$input{_view});
	if($rec{_id}){$input{_view}=$rec{_id};}
	}
#################################################################
### convert keys with multiple values
foreach my $key (keys(%input)){
	my @values=$cgi->param($key);
	if(scalar @values > 1){$input{$key}=join(':',@values);}
	}
#################################################################
### Process File Uploads in multipart forms
if($cgi->request_method() =~/POST/is && $cgi->content_type() =~/multipart\/form-data/is){
	foreach my $key (keys(%input)){
		my $fieldname=$cgi->param($key);
        my $hash = $cgi->uploadInfo($fieldname);
		if(defined $hash->{'Content-Type'}){
			#do we allow this content-type?
			my $content_type=$hash->{'Content-Type'};
            $input{"$key\_type"}=$content_type;
			my ($general,$specific)=split(/[\\\/]/,$content_type);
			#debugCgi("[$content_type][$general][$specific][$Config{upload_types}]");
			if($Config{upload_types}){
				my $valid=0;
				my @valid_types=split(/[\,\:\;\s\t]/,$Config{upload_types});
				foreach my $valid_type (@valid_types){
					if(	$valid_type=~/^\Q$general\E$/is || $valid_type=~/^\Q$specific\E$/is || $valid_type=~/^\Q$content_type\E$/is){
						$valid++;
						last;
                    	}
                	}
                if(!$valid){
					#invalid content type - toss it and set error
					abort("Files of type $content_type are not allowed to be uploaded");
                	}
            	}
            #Valid file type - write the file
            #determine dir to write it to - create dir if it does not exist
            my $outdir="$docroot/ufiles";
            if($input{"ipath\_$key"}){$outdir=qq|$docroot/$input{"ipath\_$key"}|;}
            $outdir=~s/[\\\/]+/\//sg;
            if(!-d $outdir){buildDir($outdir,"\-");}
            my $filename=getFileName($input{$key});
            #determine if we should autonumber the file
            my $afile="$outdir/$filename";
            $afile=~s/[\\\/]+/\//sg;
            if($input{"iname\_$key"} && $input{"iname\_$key"}=~/^autonumber$/is){
				#autonumber to guarantee file uniqueness
				my $filename=getFileName($afile);
				my $newname=time() . "_" . $filename;
				$afile=~s/\Q$filename\E$/$newname/s;
            	}
            if(open(UF,">$afile")){
				binmode UF;
				while(<$fieldname>){print UF $_;}
				close UF;
				$input{"$key\_size"}=-s $afile;
				if($general=~/^image$/is && $specific=~/(jpg|jpeg)/is){
					#jpeg file - get width and height
					my ($width,$height,$type) = &jpgInfo($afile);
                    $input{"$key\_width"}=$width;
                    $input{"$key\_height"}=$height;
                	}
                elsif($general=~/^image$/is && $specific=~/(gif)/is){
					#gif file - get width and height
					my ($width,$height,$type) = &gifInfo($afile);
                    $input{"$key\_width"}=$width;
                    $input{"$key\_height"}=$height;
                	}
				$input{$key}=$afile;
				$input{$key}=~s/^\Q$docroot\E//s;
            	}
            else{
				#Write Error
				abort("Error Saving File ($key)<b>\n$^E<br>\n$afile");
				}
			}
		}
	}
#################################################################
### Check for auth request
#NOTE: If a host has an authhost set in the config file, then during login, send an auth request to the auth server,
# then write/edit this user record in the local database for access from then on,
# until the next login request.  This should keep user records in sync and provide fast access when
# it really counts.
if($input{_auth}){
	my $xml=qq|<?xml version="1.0" encoding="ISO-8859-1" ?>\n|;
	$xml .= qq|<WaSQL>\n|;
	my %Msg=();
	#200 OK
	#400 Bad Request
	#401 Unauthorized
	#404 Not Found
	#405 Method Not Allowed
	if($input{_auth}=~/^login$/is){
		if(!length($input{username})){
			$Msg{_code}=400;
			$Msg{_msg}="Unauthorized - No username";
			goto ProcessAuthRequest;
			}
		if(!length($input{password})){
			$Msg{_code}=400;
			$Msg{_msg}="Unauthorized - No password";
			goto ProcessAuthRequest;
			}
		if(!length($input{guid})){
			$Msg{_code}=400;
			$Msg{_msg}="Unauthorized - No guid";
			goto ProcessAuthRequest;
			}
		if(!length($input{_code})){
			$Msg{_code}=400;
			$Msg{_msg}="Unauthorized - No auth code";
			goto ProcessAuthRequest;
			}
		#check auth code
		my $code=encodeCRC($input{username}  . $input{password} . $input{guid});
		if($code != $input{_code}){
			$Msg{_code}=400;
			$Msg{_msg}="Unauthorized - invalid auth code [$code]";
			goto ProcessAuthRequest;
			}
		#Check for user and valid password
		my $sql=qq|select * from _users where username like '$input{username}'|;
		if(length($input{_where})){$sql .= qq| and $input{_where}|;}
		my %list=();
		my $cnt=&getDBData(\%list,$sql,"nocount=1;limit=1");
		if(!isNum($cnt)){
			$Msg{_code}=400;
			$Msg{_msg}="SQL Error - $cnt\n$sql";
			goto ProcessAuthRequest;
			}
		elsif($cnt==0){
			$Msg{_code}=404;
			$Msg{_msg}="Not Found - user not found";
			goto ProcessAuthRequest;
			}
		elsif($cnt==1){
			my $pass=$list{0}{password};
			my $rpass=$input{password};
			if($pass !~/\Q$rpass\E$/s){
				$Msg{_code}=401;
				$Msg{_msg}="Unauthorized - invalid password";
				goto ProcessAuthRequest;
				}
			else{
				#Valid User
				$Msg{_code}=200;
				$Msg{_msg}="OK - user found";
				my @fields=@{$list{fields}};
				foreach my $field (@fields){
					my $val=strip($list{0}{$field});
					if($field=~/^(guid)$/is){$val=$input{$field};}
					next if length($val)==0;
					$Msg{$field}=$val;
					}
				goto ProcessAuthRequest;
				}
			}
		else{
			$Msg{_code}=505;
			$Msg{_msg}="Not Supported - unknown error";
			goto ProcessAuthRequest;
			}
		}
ProcessAuthRequest:
	foreach my $key (sort(keys(%Msg))){
		$xml .= qq|\t<$key>$Msg{$key}</$key>\n|;
	     }
	$xml.=qq|</WaSQL>\n|;
     &printHeader();
     print $xml;
	exit;
     }
#################################################
#Check for remindMeForm
if($input{_remind}==1){
    printHeader();
    print qq|<div style="height:120px;">\n|;
	if(!length($Config{dbname})){
		print "No SMTP server configured for $Config{host} in wasql.conf";
	     }
	if(!length($input{email}) || !isEmail($input{email})){
		print "Invalid email address.";
		}
	else{
        my %user=getDBRecord(-table=>"_users",email=>$input{email});
		if(isNum($user{_id})){
			my $subject="Reminder for $ENV{HTTP_HOST}";
			my $message="You have requested to be reminded of your login information. If you did NOT request this information, please contact us as soon as possible.\r\n";
			$message .= qq|Username: $user{username}\r\n|;
			$message .= qq|Password: $user{password}\r\n|;
			$message .= qq|\r\nThanks,\r\n\r\n|;
			$message .= qq|$ENV{UNIQUE_HOST}\r\n|;
			$ck=sendMail(
				smtp=>$Config{smtp},
				smtpuser=>$Config{smtpuser},
				smtppass=>$Config{smtppass},
				force=>$Config{force},
				sendmail=>$Config{sendmail},
				to=>$user{email},from=>$user{email},
				subject=>$subject,
				message=>$message
				);
			if($ck!=1){
				$title="Send Error";
				print "Error sending reminder message to $input{email}<br>Error Code: $ck";
				}
			else{
				print "A reminder has been sent to $user{email}";
				}
			}
		elsif($cnt==0){
			print "No user was found with that email.";
	        }
		print qq|	$msg\n|;
		}
	print qq|<img src="/wfiles/clear.gif" width="1" height="1" onLoad="removeDivOnExit('remindMeForm',1);">\n|;
	print qq|</div>\n|;
	exit;
	}
#################################################
### User login
%USER=();
$UserLogin=&userLogin();
#################################################
###  Check for user logout request
if($input{_logout}==1 && $input{_login} != 1 && $USER{_id}){my $msg=&userLogout();}
#################################################################
### Make sure utype is not null
if($USER{_id} && !length($USER{utype})){$USER{utype}=0;}
#################################################################
###  DatabaseSQL Commands
if(defined $input{_runsql} && $USER{_id} && $USER{utype}==0){
     print "Content-type: text/html\n\n";
     if(!length($input{_runsql})){
	 	print "No SQL";
	 	exit;
     	}
     if($input{_action} && $input{_action}=~/test/is){
	 	my $ok=checkSQL($input{_runsql});
	     if(!isNum($ok) && $input{_runsql}!~/^show (tables|schema|index)$/is){
			if($ok=~/dbdimp\.c/is){
				$ok=~s/at dbdimp.+//is;
				$ok=strip($ok);
				$ok=~s/\(([0-9]+?)\)$//s;
				}
			print qq|<div class="w_red w_bold w_big">SQL Syntax Error</div>\n|;
			print qq|<div class="w_indent w_bold w_bigger">$ok</div>\n|;
			print qq|<p>$input{_runsql}</p>\n|;
			exit;
			}
	 	print qq|<div class="w_dblue w_bold w_big">SQL Syntax OK</div>\n|;
		print qq|<p>$input{_runsql}</p>\n|;
	 	exit;
     	}
    print qq|<div>$input{_runsql}</div>\n|;
	print &RunSQL($input{_runsql});
	exit;
	}
#################################################################
###  Dynamic Page View
if(length($input{_dview}) && isNum($input{_dauth}) && $ENV{REQUEST_METHOD}=~/^POST$/is){
	print "Content-type: text/html\n\n";
	my $dview=strip($input{_dview});
	my $base=encodeBase64($dview);
	$base=~s/[^a-z]+//isg;
	my $crc=encodeCRC($base);
	my $dcrc=encodeCRC($input{base});
	if($crc != $dcrc){
		print qq|Error #345:Invalid authorization - Dynamic page view\n|;
		print qq|<div style="display:none" id="dynamicpageview">\n|;
		print qq|	auth:[$input{_dauth}]\n|;
		print qq|	base: [$base]\n|;
		print qq|	ibase:[$input{base}]\n|;
		print qq|	crc:$crc\n|;
		print qq|	----------------\n|;
		print qq|	$dview\n\n|;
		print "</div>\n";
		exit;
    	}
	#initialize any inputs passed in as params
    print eval($input{_dview});
	exit;
	}
#################################################################
###  page2xml Commands
if($input_count==1 && defined $input{postedit}){
	my $ckey=$input{postedit};
	my $salt=substr($ckey,0,2);
	print "Content-type: text/xml\n\n";
	if(!-s "$progpath/postedit.key"){
		print "postEdit Key not Found\r\nUnable to authorize request!\r\n";
		exit;
    	}
    my $key=strip(getFileContents("$progpath/postedit.key"));
	my $lkey=crypt($key,$salt);
	if($ckey !~ /^\Q$lkey\E$/is){
		print "invalid postEdit key\r\nUnable to authorize request!\r\nKey:$key\r\nSalt:$salt\r\nCKey:$ckey\r\nInKey:$lkey\r\n";
		exit;
    	}
	print &postEditList();
	exit;
	}
#posteditxml - the new way so we can edit multiple fields
if($input_count==1 && defined $input{posteditxml}){
	my $ckey=$input{posteditxml};
	my $salt=substr($ckey,0,2);
	print "Content-type: text/xml\n\n";
	if(!-s "$progpath/postedit.key"){
		print "postEdit Key not Found\r\nUnable to authorize request!\r\n";
		exit;
    	}
    my $key=strip(getFileContents("$progpath/postedit.key"));
	my $lkey=crypt($key,$salt);
	if($ckey !~ /^\Q$lkey\E$/is){
		print "invalid postEdit key\r\nUnable to authorize request!\r\nKey:$key\r\nSalt:$salt\r\nCKey:$ckey\r\nInKey:$lkey\r\n";
		exit;
    	}
	print &postEditXml();
	exit;
	}
#debugCgi();
#################################################################
###  Check for number, page name, or _logout action as first input
#/wasql.pl?43	or	/wasql.pl?home	or	/wasql.pl?43&name=bob	or	/wasql.pl?home&name=bob
if($input_count && (isNum($input_first) || !length($input{$input_first}) || ($input_count==1 && $input_first=~/^keywords$/is))){
	my $val=strip($input{keywords});
	my %rec=();
	if(isNum($input_first)){%rec=getDBRecord(-table=>"_pages",_id=>$input_first);}
	elsif(!length($input{$input_first})){
		if($input_first=~/^\_logout$/is){}
        else{%rec=getDBRecord(-table=>"_pages",name=>$input_first);}
    	}
	elsif($input_count==1 && $input_first=~/^keywords$/is){
		if(isNum($input{$input_first})){%rec=getDBRecord(-table=>"_pages",_id=>$input{$input_first});}
		else{%rec=getDBRecord(-table=>"_pages",name=>$input{$input_first});}
    	}

	if($rec{-error}){abort("Invalid page record or id<br>$rec{-sql}");}
	elsif($rec{_id}){
    	$input{_view}=$rec{_id};
     	if(!$input{_table}){$input{_table}='_pages';}
        $found++;
    	}
	}
#################################################################
### Process tag request
###  if _view=0 and tablename,fieldname,inputtype then this must be a request to build a dependant tag.
if((defined $input{_view} && $input{_view}==0) || (isNum($first_input) && $first_input==0) ){
	print "Content-type: text/html\n\n";
	if(defined $input{perlcheck}){
		my $str=$input{perlcheck};
		if(!length($str)){
			print qq|<img src="/wfiles/success.gif" border="0" width="19" height="16" title="passed - Nothing to check">\n|;

			exit;
			}
		my $perltags=0;
		my @errors=();
		while ($str=~m/\<perl(.*?)\>(.*?)\<\/perl\>/sig){
			my $code=strip($2);
			$perltags++;
			#remove any beginning and ending comments <!--  -->
			if($code=~/^\<\!\-\-/s){
				$code=~s/^\<\!\-\-//s;
				$code=~s/\-\-\>$//s;
				}
			if(length($code)){
				$code="return;$code";
				eval($code);
				if($@){
					#return the error message.
					my @lines=split(/[\r\n]+/,$@);
					my $err=join("<br>\n",@lines);
					push(@errors,$err);
					#print qq|<div><b>$perltags</b> $@</div>\n|;
	                    }
				}
			}
		while ($str=~m/\<\?(.*?)\?\>/sig){
			my $code=strip($1);
			$perltags++;
			#remove any beginning and ending comments <!--  -->
			if($code=~/^\<\!\-\-/s){
				$code=~s/^\<\!\-\-//s;
				$code=~s/\-\-\>$//s;
				}
			if(length($code)){
				$code="return;$code";
				eval($code);
				if($@){
					#return the error message.
					my @lines=split(/[\r\n]+/,$@);
					my $err=join("<br>\n",@lines);
					push(@errors,$err);
					#print qq|<div><b>$perltags</b> $@</div>\n|;
	                    }
				}
			}
		if(!$perltags){
			print qq|<img src="/wfiles/success.gif" border="0" width="19" height="16" title="passed - No Perl found">|;
			exit;
			}
		my $ecnt=@errors;
		if($ecnt){
			my $id=time() . "\_perlcheck_errors";
			print qq|<img src="/wfiles/failed.gif" border="0" width="19" height="16" onLoad="initDrop();showDrop('$id');" title="PerlCheck Failed - Click to view results" onClick="showDrop('$id');">|;

			print qq|<div id="$id" class="w_drop w_helpbox" _behavior="dropdown">\n|;
			print join("<br><br>\n",@errors);
			print qq|</div>\n|;
			exit;
	          }
		else{
			my $title="Perl syntax is OK";
			if($perltags){$title .= " - checked $perltags Perl tags";}
			print qq|<img src="/wfiles/success.gif" border="0" title="$title">|;
			exit;
			}
	     }
	elsif(length($input{tablename}) && length($input{fieldname})){
		#Build a tag
		#print hashValues(\%input);
		#exit;
	    my $field=$input{fieldname};
	    my $val=$input{$field};
	    my %meta=();
	    my $sql="select * from _fielddata where tablename like '$input{tablename}' and fieldname like '$input{fieldname}'";
		my $cnt=getDBData(\%meta,$sql,"nocount=1");
		if(!isNum($cnt)){abort($cnt);}
		if($cnt==1){
			my @mfields=@{$meta{fields}};
			foreach my $mfield (@mfields){
				if(!defined $input{$mfield} && length($meta{0}{$mfield})){$input{$mfield}=$meta{0}{$mfield};}
		          }
		     }
		if($input{_id}){
			my %list=();
			my $field=$input{fieldname};
			my $sql="select $field from $input{tablename} where _id=$input{_id}";
			my $cnt=getDBData(\%list,$sql,"nocount=1");
			#print "[$cnt] $sql<br>\n";
			if(isNum($cnt) && $cnt==1){$val=$list{0}{$field};}
			}
		#abort(hashValues(\%input));
		print &buildTag(\%input,$val);
		#If inputtype = multi or combo then call initDrop
		if($input{inputtype}=~/(multi|combo)/is){
			print qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="initDrop();">\n|;
			}
		}
	elsif(length($input{tablename})){
		#print hashValues(\%input);
		#exit;
		print AddEditForm(_table=>$input{tablename},_hide=>$input{_hide},_class=>$input{_class},_onsubmit=>$input{_onsubmit});
    	}
	else{
		print "Invalid WaSQL Request...<br>\n";
		print hashValues(\%input);
		}
	exit;
	}
#################################################################
###  Define Section,Topic,Option for management
if(length($input{_manage})){
	my @menu=split(/\:/,$input{_manage});
	$input{_section}=$menu[0];
	$input{_topic}=$menu[1];
	$input{_option}=$menu[2];
	#Log Off
	if($input{_topic}=~/^log off$/is && $input{_login}!=1){my $msg=&userLogout();}
	
	}
#################################################################
###  check for default page to view set in config file
if($input_count==0){
	if(isNum($Config{default})){
		$input{_view}=$Config{default};
		$input_count==1;
		}
	else{
		#if no inputs are passed in then manage WaSQL
		&manageWaSQL();
		exit;
		}
	}

#################################################################
###  Process _action value - add/edit/delete/view
my $ok=addDBAccess();
&processActions();
### Process actions
if($input_count==1){
     #Check to see if the page name was passed in.  wasql.pl?privacy
	if(defined $input{_view}){
		#View
		$input{_id}=$input{_view};
		$input{_action}="view";
		$input{_table}="_pages";
		$METAcnt=getDBData(\%META,"select * from _fielddata where tablename='$input{_table}'");
		my $view=&viewData($input{_table},"\_id\=$input{_id}");
		#$HEADER{'Content-Length'}=length($view);
		&printHeader();
		print $view;
		exit;
		}
	elsif(defined $input{env}){
		&printHeader();
		&printHead("Environment Variables");
		&Env();
		&printFooter();
		exit;
		}
	else{
		&manageWaSQL();
		exit;
		}
	}
elsif($input{_action}=~/^view$/is && length($input{_id}) && length($input{_table})){
	#View
	$input{_view}=$input{_id};
	my $view;
	if($Message=~/error/is){$view .= qq|<div style="background-color:#ffffff;border:1px solid #336699;font-size:12px;padding:2px;width:600px;">$Message</div>|;}
	$view .= &viewData($input{_table},"\_id\=$input{_id}");
	#$HEADER{'Content-Length'}=length($view);
	&printHeader();
	print $view;
	exit;
	}
if(isNum($input{_view})){
	#View
	my $id=$input{_view};
	if(!$input{_table}){$input{_table}="_pages";}
	my $view;
	if($Message=~/error/is){$view .= qq|<div style="background-color:#ffffff;border:1px solid #336699;font-size:12px;padding:2px;width:600px;">$Message</div>|;}
	$view .= &viewData("_pages","\_id\=$id");
	
	if($input{_header}=~/^xml$/is){
		&printHeader();
		#open(XF,">xmlout.log");
		print qq|<?xml version="1.0" encoding="ISO-8859-1" ?>\n|;
		print $view;
		#print XF qq|<?xml version="1.0" encoding="ISO-8859-1" ?>\n|;
		#print XF $view;
		#close XF;
		exit;
		}
	#$HEADER{'Content-Length'}=length($view);
	&printHeader();
	print $view;
	exit;
	}
#################################################################
###  If you get this far, go to WaSQL manager
&manageWaSQL($Message);
exit;
#################################################################
###  SUBROUTINES
###############
sub debugCgi{
	my $msg=shift;
	my ($package, $filename, $line) = caller();
	print $cgi->header;
	if(length($msg)){print "Message: $msg<br>\n";}
	print "Package: $package<br>\n";
	print "Filename: $filename<br>\n";
	print "Line: $line<br>\n";
	print "Names: @names<br>\n";
	print "Method:",$cgi->request_method(),"<br>\n";
	print "input_first: $input_first<br>\n";
	print "input_count: $input_count<br>\n";
	foreach my $key (sort(keys(%input))){
		print "$key\=[$input{$key}]<br>\n";
		}
	print qq|<h3>User</h3>\n|;
	foreach my $key (sort(keys(%USER))){
		print "$key\=[$USER{$key}]<br>\n";
		}
	$cgi->end_html;
	exit;
	}
###############
sub manageWaSQL{
	my $mTemplate=$Config{manage} || "wFiles/manage.html";
	if(-s $mTemplate){
		my $view=viewFile($mTemplate);
		&printHeader();
		print $view;
		exit;
		}
	my $msg=shift;
	my $evalstr=qq|require 'subs_manage.pl'|;
	eval($evalstr);
	if($@){
		if($@=~/^Can\'t locate/is){
			abort("The WaSQL manager is not installed.");
			}
		else{
			abort("$evalstr<hr>$@");
			}
		}
	&Manage($msg);
	}
###############
sub abort{
	#internal usage: abort($msg1,$msg2,...);
	#internal info:  aborts execution and prints messages to screen
	my @msgs=@_;
	if($ENV{ShowSystemErrors}==0 || (length($input{showsystemsrrors}) && $input{showsystemsrrors}==0)){
		$ENV{SystemError}="@msgs";
		$input{systemerror}="@msgs";
		return;
    	}

	if(!defined $ENV{HTTP_HOST}){
		#Command Line - Not in browser
		print "WaSQL Error!\r\n";
          foreach my $msg (@msgs){
			print "$msg\n";
			}
		exit;
	    }
	print $cgi->header;
	print qq|<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">\n|;
    print qq|<html>\n|;
  	print qq|<head>\n|;
	print qq|	<title>$title</title>\n|;
	print qq|	<style type="text/css">\n|;
	print qq|		body {font-size:12pt;color:#3366CC;margin:0px;padding:10px;}\n|;
	print qq|		.indent {margin-left:20px;padding-left:5px;font-size:12pt;}\n|;
	print qq|		.errtitle {font-size:12pt;float:left;color:#C40000;}\n|;
	print qq|		.error {}\n|;
	print qq|	</style>\n|;
	print qq|	<link href="/wfiles/wasql.css" rel="stylesheet" type="text/css">\n|;
  	print qq|	<script language="javascript" src="/wfiles/js/event.js"></script>\n|;
  	print qq|	<script language="javascript" src="/wfiles/js/common.js"></script>\n|;
  	print qq|	<script language="javascript" src="/wfiles/js/form.js"></script>\n|;
	print qq|</head>\n|;
	print qq|<body>\n|;
	print qq|<div style="border:1px solid #002E5B;margin:5px;float:left;padding:5px;">\n|;
	print qq|	<table cellspacing="0" cellpadding="0" border="0"><tr>\n|;
	print qq|		<td><img src="/wfiles/abort.gif" border="0"></td>\n|;
	print qq|		<td style="font-size:16pt;color:#C40000;font-weight:bold;padding-left:5px;">$title</td>\n|;
	print qq|	</tr></table>\n|;
	foreach my $msg (@msgs){
		print qq|	<div class="indent" id="w_error"> - $msg</div>\n|;
  		}
  	print createExpandDiv("Environment",hashValues(\%ENV));
  	if($input_count){print createExpandDiv("Inputs",hashValues(\%input));}
  	if($USER{_id}){print createExpandDiv("User",hashValues(\%USER));}
	print qq|</div>\n|;
	print $cgi->end_html;
	exit;
	}
###############
sub printHead{
	my $title=shift || $progname;
	if(length($HEADER{printed})==0){
		#print $cgi->header;
		printHeader();
		}
	print qq|<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">\n|;
	print qq|<html><head>\n|;
	print qq|<title>$title</title>\n|;
	print qq|<link href="/wfiles/wasql.css" rel="stylesheet" type="text/css">\n|;
	print qq|<script language="javascript" src="/wfiles/js/common.js"></script>\n|;
	print qq|<script language="javascript" src="/wfiles/js/form.js"></script>\n|;
	print qq|</head><body class="w_body" style="padding-left:5px;">\n|;
	}
###############
sub printFooter{
	print $cgi->end_html;
	}
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
###########
END {
	if($dbh){$dbh->disconnect;}
	}

exit;


