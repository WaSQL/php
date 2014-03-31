#subs_email.pl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
# my @files=("3parts.mlfx");
# foreach my $file (@files){
# 	print "File: $file\n";
# 	open(FH,$file);
# 	my @lines=<FH>;
# 	close(FH);
# 	my $content=join('',@lines);
# 	my %email=();
# 	my $verbose=1;
# 	my $ok=parseEmail(\%email,$content,$verbose);
# 	print "Headers:\n";
# 	foreach my $key (sort(keys(%{$email{header}}))){
# 		next if $key=~/^\_content$/is;
# 		print "\t$key=[[$email{header}{$key}]]\n";
# 		}
# 	if($email{body}{attachments}){
# 		my @afiles=split(/[\r\n]+/,$email{body}{attachments});
# 		my $afilecnt=@afiles;
# 		print "$afilecnt Attachments\n";
# 		foreach my $file (@afiles){
# 	  		print "\t$file\n";
# 	 		}
# 		}
# 	print "Message\n";
# 	print "$email{body}{message}\n";
# 	print "-"x30,"\n";
# 	}
###########################################
### subroutine to check for a spam message.
sub isSpam{
	my $msg=shift || return 0;
	if(length($msg->{subject}) && $msg->{subject}=~/\ wrote:$/is){return 1;}
	return 0;
	}
#################
sub parseEmail{
	my $content=shift || return "No email content";
	my $filepath=shift;
	#print "==========================parseEmail START===================================\n";
	#print "$content\n";
	#print "==========================parseEmail END===================================\n";
	my $verbose=shift || 0;
	my %email=();
	my @parts=parseEmailBoundary(\%email,$content);
	my $pcnt=@parts;
	my $index=0;
	$email{0}{content}=$content;
	foreach my $part (@parts){
		my %mpart=parseEmailParts($part,$filepath);
		foreach my $key (sort(keys(%mpart))){
			next if $key=~/^x\-/is;
			$email{$index}{$key}=strip($mpart{$key});
        	}
        $index++;
		}
	return %email;
	}
#################
sub parseEmailBoundary{
	#splits the content type into parts if there is a boundary,
	my $hash=shift || return;
	my $content=shift || return;
	#print "parseEmailBoundary\n";
     my ($head,$bod)=split(/[\r\n][\r\n]/,$content,2);
     $head=strip($head);
     return if $head=~/This is a multi-part message in MIME format\.$/is;
	return if $head=~/^\-\-$/is;
    my %list=parseHead($head);
	my @parts=();
	foreach my $key (keys(%list)){
		$hash->{0}{$key}=$list{$key};
	 	#print "\t$key = [$list{$key}]\n";
	 	}
	if(length($list{boundary})){
		#email with attachments or html content
		my @bparts=split(/\-\-$list{boundary}/,$bod);
		my $bpartcnt=@bparts;
#		print "[$bpartcnt] Boundary:$list{boundary}\n";
		foreach my $bpart (@bparts){
			my @tmp=parseEmailBoundary($hash,$bpart);
			push (@parts,@tmp);
			}
		}
	else{push(@parts,$content);}
	my $cnt=@parts;
	if(wantarray){return @parts;}
	return $cnt;
	}
#################
sub parseEmailParts{
	#returns type(1=text,2=attachment) and body
	#if content-type is attachment, returns path to attachment file
	my $content=shift || return (0);
	my $filepath=shift;
	my ($head,$bod)=split(/[\r\n][\r\n]/,$content,2);
    my %mpart=parseHead($head);
	#print "parseEmailParts\n";
	foreach my $key (sort(keys(%mpart))){
		my $val=$mpart{$key};
		if(length($val) < 300){print "\t$key = $val\n";}
    	}
	$mpart{ctype}=1;
	my $body=$bod;
	if($$mpart{'content-disposition'}=~/^(attachment|inline)/is && length($mpart{filename})){
		$mpart{ctype}=2;
		my $prefix=time();
		my $filename=$prefix . "\_$mpart{filename}";
		#print "parseEmailParts -- Filename:$filename\n";
		if(!-d $filepath){}
		elsif(open(FH,">$filepath/$filename")){
			binmode FH;
			print FH decodeMail($bod,$mpart{'content-transfer-encoding'});
			close(FH);
			$mpart{filename}="$filepath/$filename";
			}
		else{
			print "$!\n";
        	}
		}
	elsif($mpart{'content-transfer-encoding'}){
		$mpart{ctype}=1;
		$body=decodeMail($bod,$mpart{'content-transfer-encoding'});
		$body=strip($body);
		$mpart{body}=$body;
		}
	else{
		$mpart{ctype}=1;
		$body=strip($body);
		$mpart{body}=$body;
    	}
	return %mpart;
	}

#############
sub decodeMail{
	my $body=shift || return '';
	my $enctype=shift;
	my $verbose=shift || 0;
	$enctype=strip($enctype);
	#print "decodeMail: $enctype\n";
	if($enctype=~/^7bit$/is){return $body;}
	if($enctype=~/^quoted-printable$/is){return decodeQP($body);}
	if($enctype=~/^base64$/is){return decodeBase64($body);}
	#otherwise, just return the body
	return $body;
	}
#############
sub parseHead{
	my $head=shift || return undef;
    my @headlines=split(/[\r\n]+/,$head);
	my %hash=();
    #print "parseHead:\n$head\n\n";
	foreach my $line (@headlines){
		$line=strip($line);
		#print "LINE:$line\n";
		if($line=~/([a-z\-\_]+?):(.+)/is){
			my $fld=lc(strip($1));
			my $val=strip($2);
			#if($val=~/^\"(.+?)\"$/s){$val=$1;}
			#if($val=~/^\<(.+?)\>$/s){$val=$1;}
			$val=~s/[\;]+$//s;
			if(length($fld) && length($val)){
				$hash{$fld}=$val;
				}
            #filename?
            #Content-Disposition: attachment; filename="wfind.zip_"
            if($fld=~/^Content\-Disposition/is && $val=~/filename\=(.+)/is){
				my $filename=$1;
				$filename=~s/^\"//;
				$filename=~s/\"$//;
				$hash{filename}=$filename;
	        	}
			}
	    elsif(!defined $hash{boundary} && $line=~/^boundary\=(.+)/is){
			#boundary?
			my $boundary=$1;
			$boundary=~s/^\"//;
			$boundary=~s/\"$//;
			$hash{boundary}=$boundary;
			#print "BOUNDARY:$boundary\n";
            }
        elsif(!defined $hash{filename} && $line=~/^filename\=(.+)/is){
			#boundary?
			my $filename=$1;
			$filename=~s/^\"//;
			$filename=~s/\"$//;
			$hash{filename}=$filename;
            }
	    }
	return %hash;
	}
#################
sub buildMailData{
	my %params=@_;
	my $mail='';
	my $boundary=qq|----=_WaSql_SendMail_BoundaryString.060704|;
	#Message Header
	#Date
	#Sat, 31 Dec 2005 14:20:18 -0700
	#Check for Daylight savings Time and adjust the offset from it.
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	my $offset="-0700";
	if($isdst){$offset="-0600";}
	$mail .= "Date: " . getDate("AD, ND AM YYYY MH:MM:SS $offset") . "\r\n";
	#From
	$mail .= "From: <$params{from}>\r\n";
	#To
	$mail .= "To: $params{to}\r\n";
	#CC
	if(length($params{cc})){
		my @emails=split(/[\,\;]+/,$params{cc});
		my $ccstr=join(',',@emails);
		$mail .= "Cc: $ccstr\r\n" if $params{cc};
		}
	#Bcc - Do not show Bcc recipients in message header
	$mail .= "Reply-to: $params{to}\r\n" if $replyaddr;
	$mail .= "Subject: $params{subject}\r\n";
	#Message Body
	$mail .= "Mime-Version: 1.0\r\n";
	$mail .= qq|Content-Type: multipart/mixed;\r\n|;
	$mail .= qq|\tboundary="$boundary"\r\n|;
	#Priority
	if(length($params{priority}) && $params{priority}=~/^(1|high|important|urgent)$/is){
		$mail .= qq|X-Priority: 1\r\n|;
		$mail .= qq|X-MSMail-Priority: High\r\n|;
		$mail .= qq|Priority: High\r\n|;
		$mail .= qq|Importance: Urgent\r\n|;
		}
	else{
		$mail .= qq|X-Priority: 3\r\n|;
		$mail .= qq|X-MSMail-Priority: Normal\r\n|;
		$mail .= qq|Priority: Normal\r\n|;
		}
	$mail .= "X-Mailer: WaSQL Mailer\r\n";
	$mail .= "Status: RO\r\n\r\n";
	if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){$mail .= qq|This is a multi-part message in MIME format.\r\n\r\n|;}
	if($params{message}=~m/\<(.+?)\>/is){
		$mail .= qq|\-\-$boundary\r\n|;
		$mail .= qq|Content-Type: text/html;\r\n|;
		$mail .= qq|\tcharset="iso-8859-1"\r\n|;
		$mail .= qq|Content-Transfer-Encoding: base64\r\n\r\n|;
		my $message=&encodeBase64($params{message});
		my @message_lines=split(/[\r\n]+/,$message);
		foreach my $message_line (@message_lines){
			$message_line=strip($message_line);
			$mail .= qq|$message_line\r\n|;
			}
		$mail .= qq|\r\n\r\n|;
		}
	else{
		#if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){$mail .= qq|\-\-$boundary\r\n|;}
		$mail .= qq|\-\-$boundary\r\n|;
		$mail .= qq|Content-Type: text/plain;\r\n|;
		$mail .= qq|\tcharset="iso-8859-1"\r\n|;
		$mail .= qq|Content-Transfer-Encoding: 7bit\r\n\r\n|;
		my @message_lines=split(/[\r\n]+/,$params{message});
		foreach my $message_line (@message_lines){
			$message_line=strip($message_line);
			$mail .= qq|$message_line\r\n|;
			}
		$mail .= qq|\r\n\r\n|;
		}
	#Check for attachments
	if ($params{attach} ne ''){
		my $temp='';
		#split into files array
		my @files=split(/[:;,]+/,$params{attach});
		foreach my $file (@files){
			#get full path of file
			my $filepath;
			if(-e $file){$filepath=$file;}
			else{$filepath="$ENV{DOCUMENT_ROOT}/$file";}
			$filepath=~s/\/+/\//g;
			next if !-e $filepath;
			#capture file name
			my @fileparts = split(/\/|\\|:/, $file);
			my $filename=pop(@fileparts);;
			#get type
			my $type='';
			$type='image/jpeg' if -B $filepath and $filename=~/jpg|jpeg$/is;
			$type='image/gif' if -B $filepath and $filename=~/gif$/is;
			$type='image/png' if -B $filepath and $filename=~/png$/is;
			$type='image/tiff' if -B $filepath and $filename=~/tif|tiff$/is;
			$type='application/msword' if -B $filepath and $filename=~/doc$/is;
			$type='application/pdf' if -B $filepath and $filename=~/pdf$/is;
			$type='application/wordperfect' if -B $filepath and $filename=~/doc$/is;
			if(-T $filepath and $filename=~/htm|html$/is){$type='text/html';}
			elsif(-T $filepath and $type eq ''){$type='text/plain';}
			elsif(-B $filepath and $type eq ''){$type='application/unknown';}
			$mail .= qq|\-\-$boundary\r\n|;
			$mail .= qq|Content-Type: $type;\r\n|;
			$mail .= qq|\tname="$filename"\r\n|;
			if($type=~/text/is){
				$mail .= qq|Content-Transfer-Encoding: 7bit\r\n|;
				}
			else{
				$mail .= qq|Content-Transfer-Encoding: base64\r\n|;
				}
			$mail .= qq|Content-Disposition: attachment;\r\n|;
			$mail .= qq|\tfilename="$filename"\r\n\r\n|;
			my $temp='';
			open(ATT,$filepath) || return "SMTP attachment error - Cannot open attachment $filepath. " ;
			binmode(ATT) if -B $filepath;
			while (<ATT>) { $temp .= $_; }
			close(ATT);
			if($type=~/text/is){$mail .= $temp;}
			else{
				my $tx=&encodeBase64($temp);
				$mail .= $tx;
				}
			$mail .= qq|\r\n|;
			}
		}
	if($params{attach} ne '' or $params{message}=~m/\<(.+?)\>/is){$mail .= qq|\r\n\-\-$boundary\-\-\r\n|;}
	return $mail;
	}
return 1;
