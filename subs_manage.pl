#subs_manage.pl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
#Create Database tables if this is the first time connecting to this database;
#subs_manage.pl

$Debug=1;
##############
sub Manage{
	if($input{_debug}==1){$Debug=1;}
	my $message=shift;
	my $m0=$input{_m0};
	my $m1=$input{_m1};
	my $m2=$input{_m2};
	#Database:Backup
	if($m0=~/^Database$/is && length($USER{_id})){
		if($m1=~/^Backup$/is){
			if($dbt=~/sqlite/is){
				$filename=$dbname;
				my $ck=&pushFile($filename,1);
				if($ck==1){exit;}
				&printHeader();
				print $ck;
				exit;
				}
			elsif(length($input{file}) && -s "$progpath/backups/$input{file}"){
				my $ck=&pushFile("$progpath/backups/$input{file}",1);
				if($ck==1){exit;}
				&printHeader();
				print $ck;
				exit;
            	}
			}
		elsif($m1=~/^Export$/is){
			#Export
			if($input{_ftype}=~/^csv$/is && length($input{xtables})){
				my $file=$input{_file};
				my @xtables=split(/\:/,$input{xtables});
				my $xtablecnt=@xtables;
				if($xtablecnt > 1){abort("CSV export only supports exporting one table at a time");}
				my @fields=getDBFields($input{xtables});
				@fields=sort(@fields);
				my $fcnt=@fields;
				my $csvfile=time() . "\.csv";
				#abort("HERE:$fcnt,$csvfile,$file [@fields], $input{xtables}");
				if($fcnt && open(CSV,">$csvfile")){

					print CSV join(",",@fields) . "\n";
					my $sql=qq|select * from $input{xtables} order by _id|;
					#print CSV qq|\#SQL: $sql\r\n|;
					my %clist=();
					my $cnt=getDBData(\%clist,$sql,"nocount=1");
					#print CSV qq|\#Cnt:$cnt\r\n|;
					for(my $x=0;$x<$cnt;$x++){
						my @vals=();
						foreach my $field (@fields){
							my $val=strip($clist{$x}{$field});
							if(length($val)>0){
								$val=~s/\n/\\n/sg;
								$val=~s/\r/\\r/sg;
								$val=~s/\"/\"\"/g;
								if($val=~/\,/is){$val=qq|"$val"|;}
								}
							else{$val='';}
							push(@vals,$val);
							}
						print CSV join(",",@vals) . "\n";
						}
					close(CSV);
					#&printHeader();
					#print "csvfile:$csvfile, file:$file\n";
					#return;
					my $ck=&pushFile($csvfile,1,$file);
					unlink $csvfile;
					if($ck==1){exit;}
					&printHeader();
					print $ck;
					}
				exit;
				}
			elsif($input{_ftype}=~/^XML$/is && length($input{xtables})){
				my %tableinfo=();
				#Build a tableinfo hash
				my $ck=0;
				@xtables=split(/\:+/,$input{xtables});
				$xtablecnt=@xtables;
				my $xmlfile=time() . "\.xml";
				if($xtablecnt ==1){$xmlfile=$dbname . "\_$xtables[0]\.xml";}
				foreach my $xtable (@xtables){
					if($input{"$xtable\_schema"} || $input{"$xtable\_meta"} || $input{"$xtable\_data"}){
						$ck++;
						$tableinfo{$xtable}{schema}=$input{"$xtable\_schema"} || 0;
						$tableinfo{$xtable}{meta}=$input{"$xtable\_meta"} || 0;
						$tableinfo{$xtable}{data}=$input{"$xtable\_data"} || 0;
						}
					}

				&exportDB(\%tableinfo,$xmlfile);
				my $ck=&pushFile($xmlfile,1);
				unlink $xmlfile;
				if($ck==1){exit;}
				&printHeader();
				print $ck;
				exit;
				}
			}
		}
	#Show manager Form
	&printHeader();
	#get only name of dbname
	my @tmp=split(/[\\\/]+/,$dbname);
	my $dname=pop(@tmp);
	if(!defined $input{_ajax} || $input{_ajax} !=1){
		#print qq|<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">\n|;
		print qq|<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n|;
		print qq|<html>\n|;
		print qq|<head>\n|;
		print qq|<title>WaSQL - $Config{name}</title>\n|;
		if(!$USER{_id}){print qq|<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">\n|;}
		print &waSQLCssJs();
		#print qq|<script language="javascript">OnLoad += "initDrop();";</script>\n|;
		print qq|</head>\n|;
		print qq|<body class="w_body">\n|;
		#process before menu is drawn
		my $message = &preManageMenu($m0,$m1,$m2);
		#Build Menu
		print buildManageMenu();
		print qq|<div class="w_pad">\n|;
		#hotlinks
		print qq|<table cellspacing="0" cellpadding="0" border="0"><tr>\n|;
		if($m0=~/^(Table|Pages|Templates)$/is){
			my $m1x='';
			if($m0=~/^Table$/is){$m1x=$m1;}
			elsif($m0=~/^Pages$/is){$m1x="_pages";}
			elsif($m0=~/^Templates$/is){$m1x="_templates";}
			if($m1 !~/^New Table$/is){
				print qq|<td>|;
				print qq|\&nbsp\;<a href="$ENV{SCRIPT_NAME}?_m0=Table&_m1=$m1x\&_m2=Properties&_action=Schema" title="Schema" class="w_boxlink">\&nbsp\;\&\#916\;\&nbsp\;</a>\&nbsp\;|;
				print qq|</td><td>|;
				print qq|<a href="$ENV{SCRIPT_NAME}?_m0=Table&_m1=$m1x\&_m2=Indexes" title="Indexes" class="w_boxlink">\&nbsp\;\&\#9788\;\&nbsp\;</a>\&nbsp\;|;
		 		print qq|</td><td>|;
				print qq|<a href="$ENV{SCRIPT_NAME}?_m0=Table&_m1=$m1x\&_m2=Properties" title="Properties" class="w_boxlink">\&nbsp\;\&\#1758\;\&nbsp\;</a>\&nbsp\;|;
				print qq|</td><td>|;
				print qq|<a href="$ENV{SCRIPT_NAME}?_m0=Table&_m1=$m1x\&_m2=List+Data" title="List Data" class="w_boxlink">\&nbsp\;\&\#8801\;\&nbsp\;</a>\&nbsp\;|;
				print qq|</td><td>|;
				print qq|<a href="$ENV{SCRIPT_NAME}?_m0=Table&_m1=$m1x\&_m2=Add+New" title="Add New" class="w_boxlink">\&nbsp\;\&\#43\;\&nbsp\;</a>\&nbsp\;|;
				print qq|</td>|;
				}	
			}
		#Build a Bread Crumb Link
		print qq|<td class="w_crumb">|;
		if(length($m0)){
			print qq|$m0|;
			if(length($m1)){
				print qq| &raquo; $m1|;
				if(length($m2)){
					print qq| &raquo; $m2|;
					}
				}
			}
		if($input{_newid} && $input{_table}=~/^_pages$/is){
			#Show record ID that was just added
	          print qq| &raquo; Added New Record: <a class="w_crumb w_link" href="$ENV{SCRIPT_NAME}?_m0=Pages&_m1=$input{_newid}">$input{_newid}</a>|;
		     }
		elsif($input{_editid} && $input{_table}=~/^_pages$/is){
			#Show record ID that was just added
	          print qq| &raquo; Edited Record: <a class="w_crumb w_link" href="$ENV{SCRIPT_NAME}?_m0=Pages&_m1=$input{_editid}">$input{_editid}</a>|;
		     }
	     elsif($input{_newid} && $input{_table}=~/^_templates$/is){
			#Show record ID that was just added
	          print qq| &raquo; Added New Record: <a class="w_crumb w_link" href="$ENV{SCRIPT_NAME}?_m0=Templates&_m1=$input{_newid}">$input{_newid}</a>|;
		     }
		elsif($input{_editid} && $input{_table}=~/^_templates$/is){
			#Show record ID that was just added
	          print qq| &raquo; Edited Record: <a class="w_crumb w_link" href="$ENV{SCRIPT_NAME}?_m0=Templates&_m1=$input{_editid}">$input{_editid}</a>|;
		     }
		print qq|<span id="ajaxstatus" style="padding-left:5px;"></span></td></tr></table>\n|;
		if(length($message)){print qq|<div id="premenu">$message</div>\n|;}
		if(!$USER{_id}){
			print &userLoginForm(_msg=>$input{_errmsg});
			}
		else{print processManageMenu();}
		print qq|</div>\n|;
	     print qq|</body>\n|;
	     print qq|</html>\n|;
	     }
	 else{
	 	if(!$USER{_id}){
			print &userLoginForm(_msg=>$input{_errmsg});
			}
		else{print processManageMenu();}
	 	}
	exit;
	}
#####################
sub preManageMenu{
	my ($m0,$m1,$m2)=@_;
	my $rtn='';
	#Create Table and Drop Table
	if($m0=~/^Table$/is && $USER{_id} && $USER{utype}==0){
		if($m2=~/^Drop Table$/is){
			#Drop Table
			my $sql=qq|delete from _tabledata where tablename='$m1'|;
			my $ck=executeSQL($sql);
			if($ck==1){
				$rtn .= qq|Table Meta data deleted. |;
			 	$sql=qq|delete from _fielddata where tablename='$m1'|;
				$ck=executeSQL($sql);
				if($ck==1){
					$rtn .= qq|Field Meta data deleted. |;
					$sql=qq|drop table $m1|;
					$ck=executeSQL($sql);
					if($ck==1){$rtn .= qq|Table $m1 deleted. |;}
					else{$rtn .= "$DBI::errstr ";}
					}
				else{$rtn .= "$DBI::errstr ";}
				}
			else{$rtn .= "$DBI::errstr ";}
			return $rtn;
			}
		if($input{_action}=~/^Create$/is && length($input{_table})){
			$rtn .= &NewSchema($input{_table});
			$rtn .= qq|<br>\n|;
			return $rtn;
			}
		}
	return $rtn;
	}
#####################
sub processManageMenu{
	return if !length($input{_m0});
	my $rtn='';
	my $m0=$input{_m0};
	my $m1=$input{_m1};
	my $m2=$input{_m2};
	if($m0=~/^Site$/is){
		if($m1=~/^File Manager$/is){
			$rtn .= fileManager();
			}
		}
	if($m0=~/^Database$/is){$rtn .= databaseMenu($m1);}
	elsif($m0=~/^Table$/is){$rtn .= tableMenu($m1,$m2);}
	elsif($m0=~/^Pages$/is){$rtn .= pagesMenu($m1,$m2);}
	elsif($m0=~/^Users$/is){$rtn .= usersMenu($m1,$m2);}
	elsif($m0=~/^Templates$/is){$rtn .= templatesMenu($m1);}
	elsif($m0=~/^Help$/is){
		#Help - About WaSQL
		if($m1 =~/^Variables$/is){$rtn .= &Variables();}
		elsif($m1 =~/^Help Contents$/is){$rtn .= &helpContents();}
		elsif($m1 =~/^Environment$/is){
			$rtn .= qq|<br><fieldset><legend>Environment Variables</legend>\n|;
			$rtn .= &Env($table);
			$rtn .= qq|</fieldset>\n|;
			}
		elsif($m1 =~/^Html Charset$/is){$rtn .= &htmlCharset();}
		elsif($m1 =~/^WebDings$/is){$rtn .= &webDings();}
		elsif($m1 =~/^Subroutines$/is){$rtn .= &Subs();}
        elsif($m1 =~/^About WaSQL$/is){
        	my $cyear=getDate("YYYY");
            $rtn .= qq|<fieldset><legend>About WaSQL</legend>\n|;
			$rtn .= qq|<div class="w_text w_smaller">\n|;
			$rtn .= qq|<img src="/wfiles/wasql.gif" border="0"><br>\n|;
			$rtn .= qq|Version $version<br>\n|;
			$rtn .= qq|Copyright 2004 - $cyear<br>\n|;
			$rtn .= qq|All rights reserved.<br>\n|;
			$rtn .= qq|<a href="http://www.wasql.com" class="w_link" title="Click to go to http://www.wasql.com" target="_new">http://www.wasql.com</a><br>\n|;
			$rtn .= qq|</div>\n|;
			my $exp .= qq|<div><b>Perl Version: </b>$]<br>\n|;
			$exp .= qq|<b>DBI Version: </b>$dbiversion<br>\n|;
			$exp .= qq|<b>DBD Type: </b>$dbt<br>\n|;
			$exp .= qq|<b>DBD Version: </b>$dbversion<br>\n|;
			$exp .= qq|<b>DB Name: </b>$dbname<br>\n|;
			$exp .= qq|<b>DB Host: </b>$dbhost</div>\n|;
			$rtn .= createExpandDiv("Perl & Database",$exp);
			#Current User
			if($USER{_id}){
				$exp='<div>';
				foreach my $key (sort(keys(%USER))){
					next if $key=~/^\_(cdate|edate)/is;
					next if $key=~/^(password)$/is;
					$exp .= qq|<b>$key</b> $USER{$key}<br>\n|;
					}
				$exp .= "</div>\n";
	            $rtn .= createExpandDiv("User Profile",$exp);
	  			}
			$rtn .= qq|</fieldset>\n|;
	        }
	    elsif($m1 =~/^Run Update$/is){
			#update any selected files
			if(isNum($input{_update})){
				my $xmap=(
					'cron.pl'=>1,
					'dbtest.pl'=>1,
					'fixCR.pl'=>1,
					'fixfiles.pl'=>1,
					'geturl.pl'=>1,
					'import.pl'=>1,
					'pb.pl'=>1,
					'pop.pl'=>1,
					'wasql.pl'=>1,
					);
				for(my $x=0;$x<$input{_update};$x++){
					my $key="update_$x";
					next if !length($input{$key});
					my $file=$input{$key};
					my $afile="$progpath/$file";
					next if !-e $afile;
					unlink($afile);
					my $cmd=qq|svn update "$file"|;
					my @lines=cmdResults($cmd,$progpath);
					#set permissions if found in xmap
					if(defined $xmap{$file}){
						fixCR($afile);
						chmod(0755,$afile);
						}
                	}
            	}
			#determine if there are any files to update
			my %updates=();
			my $cmd=qq|svn status -u|;
			my @lines=cmdResults($cmd,$progpath);
			my %umap=(
				'A'=>'Added',
				'C'=>'Current Version Conflicts',
				'D'=>'Missing File',
				'M'=>'Locally Modified',
				'R'=>'Replaced',
				'*'=>'Newer Version',
				);
			#$utype: A=Added, C=Conflicted, D=Deleted, I=Ignored, M=Modified, R=Replaced, *=Newer Version
			foreach my $line (@lines){
				$line=strip($line);
				$line=~s/[\ \s\t]+/\;/sg;
				next if $line=~/^\?/s;
				next if $line=~/^Status /is;
				my ($utype,$rev,$file)=split(/\;+/,$line);
				$utype=strip($utype);
				next if $utype!~/^(A|C|D|R|\*)$/is;
				$file=strip($file);
				$updates{$file}=$utype;
            	}
            my @ufiles=sort(keys(%updates));
			my $ufilecnt=@ufiles;
			#
			$rtn .= "<br>\n";
			if($ufilecnt){
				$rtn .= qq|<div><b>$ufilecnt updates found</b></div>\n|;
                $rtn .= qq|<form method="post" action="$cgiroot" class="w_form">\n|;
                $rtn .= qq|<input type="hidden" name="_m0" value="$m0">\n|;
                $rtn .= qq|<input type="hidden" name="_m1" value="$m1">\n|;
                $rtn .= qq|<input type="hidden" name="_update" value="$ufilecnt">\n|;
                $rtn .= qq|<table cellspacing="0" cellpadding="0" border="0" class="w_table">\n|;
                $rtn .= qq|	<tr>\n|;
                $rtn .= qq|		<th><input type="checkbox" checked onClick="checkAllElements('id','updatefile',this.checked);"></th>\n|;
                $rtn .= qq|		<th>Type</th>\n|;
                $rtn .= qq|		<th>File</th>\n|;
                $rtn .= qq|	</tr>\n|;
                my $u=0;
                foreach my $ufile (@ufiles){
					my $utype=$updates{$ufile};
					$rtn .= qq|	<tr>\n|;
	                $rtn .= qq|		<td><input type="checkbox" id="updatefile" name="update_$u" value="$ufile" checked></td>\n|;
	                $rtn .= qq|		<td>$umap{$utype}</td>\n|;
	                $rtn .= qq|		<td>$ufile</td>\n|;
	                $rtn .= qq|	</tr>\n|;
	                $u++;
	            	}
	            $rtn .= qq|	<tr><td colspan="3" align="right"><input type="submit" value="Update Selected Files"></td></tr>\n|;
	            $rtn .= qq|</table>\n|;
	            $rtn .= qq|</form>\n|;
	            #$rtn .= hashValues(\%input);
            	}
			else{
				$rtn .= qq|<fieldset><legend>Congratulations!</legend>\n|;
				$rtn .= qq|Your WaSQL installation is up to date.\n|;
				$rtn .= qq|</fieldset>\n|;
				}
			}
	     }
	return $rtn;
	}
#####################
sub pagesMenu{
	my $m1=shift;
	my $m2=shift;
	my $rtn='';
	return "No m1 (tableMenu)" if !length($m1);
	if($m1=~/^Add New$/is){return AddEditForm(_table=>"_pages",_manage=>1);}
	elsif($m1=~/^List$/is){

		if(length($input{_id}) && length($input{_delid})==0){return AddEditForm(_table=>"_pages",_id=>$input{_id},_manage=>1);}
		return &ListData(_table=>"_pages",_editlist=>1);
	     }
	elsif($m2=~/^View$/is){
		$rtn .= qq|<iframe frameborder="0" width="100%" height="100%" src="$ENV{SCRIPT_NAME}?_view=$m1"></iframe>\n|;
		return $rtn;
	     }
	elsif($m2=~/^Publish$/is){
		my %alist=();
		my $cnt = getDBData(\%alist,"select _id,publish from _pages where not(publish is null) and _id=$m1","nocount=1");
		if($cnt==1){
			#publish the records
			my $file=strip($alist{0}{publish});
			my $pfile=&publishData("_pages",$m1,$file);
			if(-e $pfile){
				my $pdate=getDate("YYYY-NM-ND MH:MM:SS");
				my $ck=editDBData("_pages","_id=$m1",_pdate=>$pdate);
				$rtn .= "Published $file to $pfile<br>Updated publish date on record $m1<br>\n";
				}
			else{$rtn .= "Unable to Publish $file\. $pfile<br>";}
			}
		elsif($cnt==0){
			$rtn .= "Unable to Publish - No publish name given to record $m1 in _pages table.<br>";
			}
		else{
			$rtn .= "Publish Error: $cnt<br>";
			}
		return $rtn;
		}
	elsif($m1=~/^Publish All$/is){
		my %alist=();
		my $cnt = getDBData(\%alist,"select _id,name,publish from _pages where not(publish is null)","nocount=1");
		if(!isNum($cnt)){
            $rtn .= "Publish Error: $cnt<br>";
            return $rtn;
        	}
        $rtn .= qq|<div style="margin-left:20px;margin-top:20px;">\n|;
        for(my $p=0;$p<$cnt;$p++){
			my $file=strip($alist{$p}{publish});
			my $id=$alist{$p}{_id};
			my $name=$alist{$p}{name};
			my $pfile=&publishData("_pages",$id,$file);
			if(-e $pfile){
				my $pdate=getDate("YYYY-NM-ND MH:MM:SS");
				my $ck=editDBData("_pages","_id=$id",_pdate=>$pdate);
				my $size=-s $pfile;
				my $vsize=verboseSize($size);
				$file = "/" . $file if $file!~/^\//;
				my $linkfile=$file;
				$file=~s/^[\\\/]+//s;
				$rtn .= qq|<div><img src="/wfiles/success.gif" border="0">$id\. <a href="$linkfile" target="_new">$file</a> - $vsize - $pdate - $pfile</div>\n|;
				}
			else{
				$rtn .= qq|<div><img src="/wfiles/x_red.gif" border="0">$id\. <b>$file</b> - Failed to publish. $pfile</div>\n|;
				}
        	}
        $rtn .= qq|</div>\n|;
		return $rtn;
		}
	elsif($input{_delid} && $input{_delid} == $m1){
		#They just deleted this record
		return "Record $m1 deleted.";
		}
     else{
	 	if(length($input{publish_msg})){$rtn .= qq|<div>$input{publish_msg}</div>\n|;}
	 	$rtn .= AddEditForm(_table=>"_pages",_id=>$m1,_manage=>1);
	 	return $rtn;
		}
	}
#####################
sub usersMenu{
	my $m1=shift;
	my $m2=shift;
	my $rtn='';
	return "No m1 (usersMenu)" if !length($m1);
	if($m1=~/^Add New$/is){return AddEditForm(_table=>"_users",_manage=>1);}
	elsif($m1=~/^My Profile$/is){return AddEditForm(_table=>"_users",_id=>$USER{_id},_manage=>1);}
	elsif($m1=~/^My API Key/is){
		$rtn .= qq|<p><h2>API Key for $USER{username}:</h2><div style="margin-left:20px;"><input type="text" value="$USER{apikey}" style="font-size:12pt;width:700px;" onFocus="this.select();"></div>|;
		$rtn .= qq|<p><b>Note:</b> Keep your API Key confidential. You will need this API key and your username to use the WaSQL edit manager. If you change your password your API key will also change.</p>|;
		#$rtn .= qq||;
		return $rtn;
    	}
	elsif($m1=~/^List$/is){
		if(length($input{_id}) && length($input{_delid})==0){return AddEditForm(_table=>"_users",_id=>$input{_id},_manage=>1);}
		return &ListData(_table=>"_users",_editlist=>1);
	     }
	elsif($input{_delid} && $input{_delid} == $m1){
		#They just deleted this record
		return "Record $m1 deleted.";
		}
     else{
	 	if(length($input{publish_msg})){$rtn .= qq|<div>$input{publish_msg}</div>\n|;}
	 	$rtn .= AddEditForm(_table=>"_users",_id=>$m1,_manage=>1);
	 	return $rtn;
		}
	}
#####################
sub templatesMenu{
	my $m1=shift;
	my $m2=shift;
	my $rtn='';
	return "No m1 (tableMenu)" if !length($m1);
     if($m1=~/^Add New$/is){return AddEditForm(_table=>"_templates",_manage=>1);}
     elsif($m1=~/^List$/is){
		if(length($input{_id}) && length($input{_delid})==0){return AddEditForm(_table=>"_templates",_id=>$input{_id},_manage=>1);}
		return &ListData(_table=>"_templates",_editlist=>1);
	     }
	elsif($input{_delid} && $input{_delid} == $m1){
		#They just deleted this record
		return "Record $m1 deleted.";
		}     
     else{return AddEditForm(_table=>"_templates",_id=>$m1,_manage=>1);}
	}
#####################
sub tableMenu{
	my $m1=shift;
	my $m2=shift;
	my $rtn='';
	return "No m1 (tableMenu)" if !length($m1);
	if($m1=~/^New Table$/is){
		#New Table
		$rtn .= qq|<form class="w_form" name="newtable" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" onSubmit="if(isDBReservedWord(this._table.value)){return false;}return submitForm(this);">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
		$rtn .= qq|<b>Table Name: </b><input type="text" tabindex="1" name="_table" displayname="Table Name" required="1" mask="\^(\[a-zA-Z0-9\_\-]+)\$" maskmsg="Table Name must be alphanumeric" maxlength="23" maxlength="100" style="width:150px;font-size:12px;"><br>\n|;
		$rtn .= qq|<div style="width:400px;">\n|;
          $rtn .= qq|<div style="float:right;font-size:10pt;">\n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title,1);" title=" varchar(255) Default NULL">VCDN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title,1);" title=" integer Default NULL">IDN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title);" title=" varchar(255)">VC</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title);" title=" integer">INT</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title,1);" title=" Default NULL">DN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title,1);" title=" NOT NULL">NN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.newtable._schema,this.title,1);" title=" NOT NULL Unique">NNU</a> \n|;
          $rtn .= qq|</div>\n|;
		$rtn .= qq|Enter Fields Below|;
		$rtn .= qq|</div>\n|;
		$rtn .= qq|<textarea style="width:400px;height:300px;" tabindex="2" name="_schema" wrap="off"></textarea><br>\n|;
		$rtn .= qq|<input type="submit" tabindex="" name="_action" value="Create" style="font-size:12px;">\n|;
          $rtn .= qq|</form>\n|;
		$rtn .= qq|<script language="javascript">document.newtable._table.focus();</script>\n|;
		return $rtn;
		}
	elsif($input{_id} && length($input{_newid})==0 && length($input{_editid})==0){
		#Edit Record Form
		$table=$m1;
		if(length($input{_action}) && $input{_action}=~/^Delete$/is){$rtn .= &ListData(_table=>$table,_editlist=>1,_oddcolor=>"#F4F3F0");}
		else{$rtn .= &AddEditForm(_table=>$table,_id=>$input{_id});}
		return $rtn;
		}
	elsif(!length($m2) || $m2=~/^List Data$/is){
		$rtn .= &ListData(_table=>$m1,_editlist=>1,_oddcolor=>"#F4F3F0");
		return $rtn;
	 	}
 	elsif($m2=~/^Properties$/is){
		#Table Properties
		return &tableProperties($m1);
		}
	elsif($m2=~/^Indexes$/is){
		#Table Indexes
		my %list=();
		$rtn = qq|<br><div class="w_dblue">Indexes for $m1</div>\n|;
		my $cnt=&getDBIndex(\%list,$m1);
		$rtn .= hash2Html(\%list);
		return $rtn;
		}
	elsif($m2=~/^Truncate$/is){
		#Truncate data
		my $sql=qq|delete from $m1|;
		if($dbt=~/mysql/i){$sql="truncate $m1";}
		my $ck=executeSQL($sql);
		$rtn = qq|<br><div class="w_dblue">Table $m1 has been truncated<br>[$ck] $sql</div>\n|;
		return $rtn;
		}
	elsif($m2=~/^Drop Table$/is){
		#Drop Table
		my $sql=qq|delete from _tabledata where tablename='$table'|;
		my $ck=executeSQL($sql);
		$sql=qq|delete from _fielddata where tablename='$table'|;
		$ck=executeSQL($sql);
		$sql=qq|drop table $table|;
		$ck=executeSQL($sql);
		}
	elsif($m2=~/^Add New$/is){
		#Add New
		if(length($input{_newid})){$rtn .= &ListData(_table=>$m1,_editlist=>1,_oddcolor=>"#F4F3F0");}
		else{$rtn .= &AddEditForm(_table=>$m1,_action=>'manage');}
		}
	return $rtn;
	}
#####################
sub databaseMenu{
	my $m1=shift;
	my $rtn='';
	return "No m1 (databaseMenu)" if !length($m1);
	if($m1=~/^Backup$/is){
		if(length($Config{backup_cmd})){
			my @lines=cmdResults($Config{backup_cmd},$progpath);
			$rtn .=qq|<b>Backup Cmd Results: </b> $Config{backup_cmd}<br>\n|;
			$rtn .= qq|<textarea style="width:600px;height:450px;border:1px solid #818181;" wrap="off">\n|;
			foreach my $line (@lines){
				next if !length(strip($line));
				$rtn .= strip($line) . "\r\n";
            	}
			$rtn .= "</textarea>\n";
        	}
		else{
			my $backupDir="$progpath/backups";
			buildDir($backupDir) if !-d $backupDir;
			my $dumpdate=getDate("YYYY.NM.ND_RH.MM.SS.PM");
			my $backupFile="$backupDir/$dbname\_$dumpdate\.sql";
			if($dbt=~/mysql/is){
				#try to use Mysql Backup module if it has been installed
				my $useCmd='use ' . 'MySQL::Backup;';
				eval($useCmd);
				if(!$@){
	  				my $mb = new MySQL::Backup($dbname,$dbhost,$Config{dbuser},$Config{dbpass},{'SHOW_TABLE_NAMES' => 1});
	  				if($mb && setFileContents($backupFile,$mb->create_structure() . $mb->data_backup())){
						$rtn .= qq|Backup Success: $backupFile<br>\n|;
	                	}
	                else{$rtn .= qq|<div class="indent">Backup Command Failed<br>$^E</div>\n|;}
					}
				else{
					my $dumpcmd=$Config{backup} || "mysqldump";
					my $cmd=qq|$dumpcmd -u $Config{dbuser} -p$Config{dbpass} -h $Config{dbhost} $dbname >$backupFile|;
					$rtn .= qq|$cmd<hr>\n|;
					my $err=system($cmd);
					if($err==0){
						$rtn .= qq|Success: $backupFile<br>\n|;
		               	}
		          	else{$rtn .= qq|<div class="indent">Command Failed</div>\n|;}
					}
				if(-s $backupFile){
					#my $ck=&pushFile($backupFile,1);
					#if($ck==1){exit;}
					#return $ck;
					my $size=verboseSize(-s $backupFile);
					$rtn .= qq|<div class="indent">Backup File: <a href="$cgiroot\?_m0=Database&_m1=Backup&file=$dbname\_$dumpdate\.sql">$backupFile</a></div>\n|;
					$rtn .= qq|<div class="indent">Backup File Size: $size</div>\n|;
	            	}
				}
			}
		}
	elsif($m1=~/^Schema$/is){
		my @tables=getDBTables();

		my %Show=();
		if(length($input{_stable})){
			my @stables=split(/\:/,$input{_stable});
			foreach my $stable (@stables){$Show{$stable}=1;}
        	}
        $rtn .= qq|<div style="width:800px;">\n|;
        $rtn .= qq|<div style="float:right;font-size:11pt;font-family:arial;border:1px solid #000;margin:20px;">\n|;
        $rtn .= qq|<form name="DBSchema" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" onSubmit="return submitForm(this);">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
        $rtn .= qq|<div style="padding-left:5px;border-bottom:1px solid #000;"><input type="checkbox" onClick="checkAllElements('name','_stable',this.checked);"> <b>Table Filter</b></div>\n|;
		foreach my $table (sortTextArray(@tables)){
            $rtn .= qq|<div style="padding-left:5px;"><input type="checkbox" name="_stable" value="$table"|;
            if(!length($input{_stable}) || $Show{$table}){$rtn .= " checked";}
			$rtn .= qq|> $table </div>\n|;
        	}
        $rtn .= qq|<input type="submit" value="Filter">\n|;
        $rtn .= qq|</form>\n|;
        $rtn .= qq|</div>\n|;
        $rtn .= qq|<div class="w_bold w_lblue w_bigger">$dbt database named $dbname on $dbhost</div>\n|;
		foreach my $table (sortTextArray(@tables)){
			if(length($input{_stable}) && !$Show{$table}){next;}
			$rtn .= qq|<div style="float:left;font-size:11pt;font-family:arial;">\n|;
			if($table=~/^\_(users|history|pages|templates|fielddata|tabledata)$/is){
				$rtn .= qq|<img src="/wfiles/$table\.gif" border="0" style="vertical-align: bottom;"> |;
            	}
            my $cnt=getDBCount($table);
			$rtn .= qq|<b style="border-bottom:1px solid #000;">$table - $cnt records</b>\n|;
			my %ft=();
			my $info=getDBFieldInfo($table);
			$rtn .= qq|<div style="border-left:1px solid #000;border-bottom:1px solid #000;margin-left:20px;padding:3px;">\n|;
			my @fields=keys(%{$info});
			$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="1" border="0" style="border-collapse:collapse">\n|;
			$rtn .= qq|<tr><th>Field</th><th>TypeID</th><th>Type</th><th>Precision</th><th>Null?</th></tr>\n|;
			foreach my $field (sortTextArray(@fields)){
				$rtn .= qq|<tr>\n|;
				$rtn .= qq|	<td>$info->{$field}{name}</td>\n|;
				$rtn .= qq|<td>$info->{$field}{typeid}</td>\n|;
				my $type=ucfirst(lc($info->{$field}{type}));
				if($type=~/^unknown$/is){$type='';}
				$rtn .= qq|	<td>$type</td>\n|;

				$rtn .= qq|<td>$info->{$field}{precision}</td>\n|;
				my $null=$info->{$field}{nullable}?qq|<img src="/wfiles/check.gif" border="0">|:' ';
				$rtn .= qq|<td>$null</td>\n|;
				$rtn .= qq|</tr>\n|;
                #$rtn .= qq|<div> $info->{$field}{sql}</div>\n|;
            	}
            $rtn .= qq|</table>\n|;
            $rtn .= qq|</div>\n|;
        	}
        $rtn .= qq|</div>\n|;
        $rtn .= qq|</div>\n|;
		}
	elsif($m1=~/^SQL$/is){
		#SQL Command Form
		$rtn .= qq|<script language="javascript">\n|;
		$rtn .= qq| var selTxt='';\n|;
		$rtn .= qq|	function commandFormSubmit(){\n|;
		$rtn .= qq|		var frm=document.commandForm;\n|;
		$rtn .= qq|		if(selTxt.length){\n|;
		$rtn .= qq|			ajaxGet('$cgiroot','CommandResults','_action='+frm._action.value+'&_runsql='+selTxt);\n|;
		$rtn .= qq|			}\n|;
		$rtn .= qq|		else{\n|;
		$rtn .= qq|			ajaxPost(document.commandForm,'CommandResults');\n|;
		$rtn .= qq|			}\n|;
		$rtn .= qq|		document.getElementById('CommandResults').scrollTop=99999;\n|;
		$rtn .= qq|		return false;\n|;
		$rtn .= qq|		}\n|;
		$rtn .= qq|	</script>\n|;
		$rtn .= qq|<form name="commandForm" class="w_form" method="Post" action="$ENV{SCRIPT_NAME}" onSubmit="return commandFormSubmit();">\n|;
		$rtn .= qq|	<input type="hidden" name="_action" value="">\n|;
		$rtn .= qq|<div style="border:1px solid #000;">\n|;
		$rtn .= qq|<div style="background:#000;color:#fff;padding-left:10px;"><b>View SQL Query Results Below:</b></div>\n|;
		$rtn .= qq|	<div id="CommandResults"></div>\n|;
		$rtn .= qq|	<div style="background:#000;color:#fff;padding-left:10px;"><b>Enter SQL Query Below:</b></div>\n|;
		$rtn .= qq|<textarea style="font-size:11pt;width:100%;height:75px;border:0px;border:1px solid #CCC;" name="_runsql" onMouseUp="selTxt=getSelText(this);"></textarea>\n|;
        $rtn .= qq| <input type="button" value="Run SQL" onClick="document.commandForm._action.value='run';return commandFormSubmit();">\n|;
		$rtn .= qq| <input type="button" value="Test SQL" onClick="document.commandForm._action.value='test';return commandFormSubmit();">\n|;
	    $rtn .= qq|</div>\n|;
	    $rtn .= qq|<div>Connected to <b>$dbt</b> database named <b>$dbname</b></div>\n|;
	    $rtn .= qq|<div style="font-size:10pt;">\n|;
	    $rtn .= qq|	<div><b>Special Queries:</b></div>\n|;
	    $rtn .= qq|	<li><a href="#" class="w_lblue" onClick="document.commandForm._runsql.value=this.innerHTML;">show tables</a> - lists tables in the database\n|;
	    $rtn .= qq|	<li><b class="w_lblue">show fields {tablename}</b> - lists fields (name and type) of specified table<b></b>\n|;
	    $rtn .= qq|	<li><a href="#" class="w_lblue" onClick="document.commandForm._runsql.value=this.innerHTML;">show schema</a> - database schema\n|;
	    $rtn .= qq|	<li><a href="#" class="w_lblue" onClick="document.commandForm._runsql.value=this.innerHTML;">show index</a> - database indexes\n|;
		$rtn .= qq|</div>\n|;
		$rtn .= qq|</form>\n|;
		$rtn .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="document.commandForm._runsql.focus();">\n|;
		}
	elsif($m1=~/^Import$/is){
		#Import
		$rtn .= qq|<form name="DBImport" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" enctype="multipart/form-data" onSubmit="return submitForm(this);">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
		#import File
		$rtn .= qq|<div>Import File: <input type="file" name="_file" style="width:250px;font-size:12px;" value="$input{_file}" maxlength="255"></div>\n|;
		#CSV import options
		$rtn .= qq|<p></p><table class="w_table" cellspacing="6" cellpadding="0" border="0">\n|;
		$rtn .= qq|<tr><td><b>CSV</b></td>\n|;
		$rtn .= qq|<td>Table</td><td><select name="_itable" style="font-size:12px;"><option></option>\n|;
		my @tables=getDBTables();
		@tables=sortTextArray(@tables);
		foreach my $stable (@tables){
			my $dtable=ucfirst($stable);
			$sel=&selectCheck($stable,$input{_itable},1);
			$rtn .= qq|<option value="$stable" $sel>$dtable</option>\n|;
			}
		$rtn .= qq|</select>\n|;
		$rtn .= qq|</td><td>|;
		$rtn .= qq|<input name="_submit" type="submit" style="font-size:11px;" value="Import CSV">\n|;
		$rtn .= qq|</td></tr></table>\n|;
		#XML Import options
		$rtn .= qq|<p></p><table class="w_table" cellspacing="6" cellpadding="0" border="0">\n|;
		$rtn .= qq|<tr><td><b>XML</b></td>\n|;
		$rtn .= qq|<td>Types</td><td>\n|;
		$rtn .= qq|<table class="w_table" cellspacing="1" cellpadding="0" border="0">\n|;
		$rtn .= qq|<tr style="font-size:10px;"><td>Schema</td><td>Meta</td><td>Data</td></tr>\n|;
		$rtn .= qq|<tr style="font-size:10px;">|;
		my $sel=&selectCheck(1,$input{_schema},1);
		$rtn .= qq|<td><input type="checkbox" name="_schema" value="1" $sel></td>|;
		$sel=&selectCheck(1,$input{_meta},1);
		$rtn .= qq|<td><input type="checkbox" name="_meta" value="1" $sel></td>|;
		$sel=&selectCheck(1,$input{_data},1);
		$rtn .= qq|<td><input type="checkbox" name="_data" value="1" $sel></td>|;
		$rtn .= qq|</tr></table>\n|;
		$rtn .= qq|</td><td>|;
		$rtn .= qq|<input name="_submit" type="submit" style="font-size:11px;" value="Import XML">\n|;
		$rtn .= qq|</td></tr></table>\n|;
		#Process import Requests
		#Check to process
		if(length($input{_submit})){
			my $file=$input{_file};
			if(!-e $file){
				$file=$ENV{DOCUMENT_ROOT} . $input{_file};
	               }
			if($input{_submit}=~/^Import CSV$/is){
				if(length($file)==0){$rtn .= qq|Choose a CSV import file.<br>\n|;}
				elsif(!-e $file){$rtn .= qq|$file does not exist.<br>\n|;}
				elsif(length($input{_itable})==0){$rtn .= qq|Select a table to import CSV file to.<br>\n|;}
				else{
					&importCSV(file=>$file,table=>$input{_itable},startline=>$input{_start} || 0);
					}
				}
			elsif($input{_submit}=~/^Import XML$/is){
				if(length($file)==0){$rtn .= qq|Choose an XML import file.<br>\n|;}
				elsif(!-e $file){$rtn .= qq|$file does not exist.<br>\n|;}
				elsif(length($input{_schema})==0 && length($input{_meta})==0 && length($input{_data})==0){$rtn .= qq|Select XMl import options.<br>\n|;}
				else{
					&importXML(file=>$file,output=>1,schema=>$input{_schema},meta=>$input{_meta},data=>$input{_data});
					}
				}

			}
		}
	elsif($m1=~/^Export$/is){
		#Do some validation
		my ($msg,$ftype,$file,$xtablecnt);
		my %tableinfo=();
		my @DBTables=getDBTables();
		if(length($input{_submit})){
			#Check to make sure tables were selected.
			@xtables=split(/\:+/,$input{xtables});
			$xtablecnt=@xtables;
			if($xtablecnt==0){$msg .= "No Tables Selected. ";}
			#Build a tableinfo hash
			my $ck=0;
			foreach my $xtable (@xtables){
				if($input{"$xtable\_schema"} || $input{"$xtable\_meta"} || $input{"$xtable\_data"}){
					$ck++;
					$tableinfo{$xtable}{schema}=$input{"$xtable\_schema"} || 0;
					$tableinfo{$xtable}{meta}=$input{"$xtable\_meta"} || 0;
					$tableinfo{$xtable}{data}=$input{"$xtable\_data"} || 0;
					}
				}
			$ftype=$input{_ftype};


			if($input{_submit}=~/^Export$/is){
				#Verify schema, meta, or data has been marked
				if($ck==0){$msg .= "No Schema, Meta, or Data selected to export. ";}
				}
			}
		#Display options
		$rtn .= qq|<div style="width:475px;">\n|;
		if(length($msg)){$rtn .= qq|<div style="color:#C50101">Error: $msg</div>\n|;}
		$rtn .= qq|<form name="DBExport" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
		$rtn .= qq|<table class="w_table" cellspacing="1" cellpadding="0" border="1" bgcolor="#000000" width="100%">\n|;
		$rtn .= qq|<tr bgcolor="#336699" style="font-size:12px;color:#ffffff;" align="center">\n|;
		$rtn .= qq|<td align="left" nowrap="true"><input type="checkbox" name="all_table" value="1"|;
		$rtn .= &selectCheck(1,$input{all_table},1);
		$rtn .= qq| onClick="return checkAllElements('id','xat',this.checked)"></td>\n|;
		$rtn .= qq|<td nowrap="true"> Table </td>\n|;
		if($m1=~/^Export$/is){
			$rtn .= qq|<td align="left" nowrap="true"><input type="checkbox" name="all_schema" value="1"|;
			$rtn .= &selectCheck(1,$input{all_schema},1);
			$rtn .= qq| onClick="return checkAllElements('id','xas',this.checked)"> Schema</td>\n|;
			$rtn .= qq|<td align="left" nowrap="true"><input type="checkbox" name="all_schema" value="1"|;
			$rtn .= &selectCheck(1,$input{all_schema},1);
			$rtn .= qq| onClick="return checkAllElements('id','xam',this.checked)"> Meta</td>\n|;
			$rtn .= qq|<td align="left" nowrap="true"><input type="checkbox" name="all_schema" value="1"|;
			$rtn .= &selectCheck(1,$input{all_schema},1);
			$rtn .= qq| onClick="return checkAllElements('id','xad',this.checked)"> Data</td>\n|;
			}
		my $tablecnt=@DBTables;
		my $rowspan=$tablecnt+1;
		$rtn .= qq|<td  style="padding:3px;" width="100%" bgcolor="#ffffff" rowspan="$rowspan" valign="top">\n|;
		$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="1" border="0"  width="100%">\n|;
		#File Type
		$rtn .= qq|<tr style="font-size:12px;">\n|;
		$rtn .= qq|<td nowrap="true" width="100%">File Type</td>\n|;
		$input{_ftype} ||= "XML";
		my $sel=&selectCheck("CSV",$input{_ftype},1);
		$rtn .= qq|<td nowrap="true"><input type="radio" name="_ftype" value="CSV" $sel></td>\n|;
		$rtn .= qq|<td nowrap="true">CSV</td>\n|;
		$sel=&selectCheck("XML",$input{_ftype},1);
		$rtn .= qq|<td nowrap="true"><input type="radio" name="_ftype" value="XML" $sel></td>\n|;
		$rtn .= qq|<td nowrap="true">XML</td>\n|;
		$rtn .= qq|</tr>\n|;
		$rtn .= qq|<tr style="font-size:12px;">\n|;
		$rtn .= qq|<td nowrap="true" colspan="3" align="right"><input name="_submit" type="submit" value="Export"></td>\n|;
		$rtn .= qq|</tr>\n|;
		$rtn .= qq|</table>\n|;
		$rtn .= qq|</td>\n|;
		$rtn .= qq|</tr>\n|;
		my $cnt=0;
		foreach my $stable (@DBTables){
			my $dtable=ucfirst($stable);
			$cnt++;
			$rtn .= qq|<tr bgcolor="#ffffff" style="font-size:12px;" align="center">\n|;
			$sel=&selectCheck($stable,$input{xtables},1);
			$rtn .= qq|<td><input type="checkbox" id="xat" name="xtables" value="$stable" $sel></td>\n|;
			$rtn .= qq|<td align="left" style="padding-left:3px;">$dtable</td>\n|;
			$sel=&selectCheck(1,$input{"$stable\_schema"},1);
			$rtn .= qq|<td><input type="checkbox" id="xas" name="$stable\_schema" value="1" $sel></td>\n|;
			$sel=&selectCheck(1,$input{"$stable\_meta"},1);
			$rtn .= qq|<td><input type="checkbox" id="xam" name="$stable\_meta" value="1" $sel></td>\n|;
			$sel=&selectCheck(1,$input{"$stable\_data"},1);
			$rtn .= qq|<td><input type="checkbox" id="xad" name="$stable\_data" value="1" $sel></td>\n|;
			$rtn .= qq|</tr>\n|;
			}
		$rtn .= qq|</table>\n|;
		$rtn .= qq|</form>\n|;
		$rtn .= qq|</div>\n|;
		}
	elsif($m1=~/^Charset/is){
		#Character Set
		#Process conversion Request
		my $cmessage='';
		if(length($input{_charset})){
			$cmessage .= '<h3>Converting to '.$input{_charset}.'</h3><hr>'."\n";
			my @tables=getDBTables();
			foreach my $table (@tables){
				my $runsql='ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.$input{_charset};
				$cmessage .= 'Converting Table '.$table.'...'."\n";
				$ck=executeSQL($runsql);
				if($ck !=1){$cmessage .= "FAILED: $ck<br>\n";}
				else{$cmessage .= 'SUCCESS<br>'."\n";}
            	}
            my $runsql='ALTER DATABASE '.$dbname.' CHARACTER SET '.$input{_charset};
			$cmessage .= 'Converting Database '.$dbname.'...'."\n";
			$ck=executeSQL($runsql);
			if($ck !=1){$cmessage .= "FAILED: $ck<br>\n";}
			else{$cmessage .= 'SUCCESS<br>'."\n";}
	        }
		$rtn .= qq|<form name="charset" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" enctype="multipart/form-data" onSubmit="return submitForm(this);">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
		#Get Character Sets available?
		my $cset=getDBCharset();
		#$rtn .= '<b>Current Character Set:</b> '.$cset.'<br>'."\n";
		$rtn .= '<b>Available Character Sets</b><br>'."\n";
		my %sets=getDBCharsets();
		$rtn .= '<select name="_charset" required="1">'."\n";
		foreach my $set (sort(keys(%sets))){
			$rtn .= '<option value="'.$set.'"';
			if(isEqual($set,$cset)){$rtn .= ' selected>'.$sets{$set}.' ** CURRENT **</option>'."\n";}
			else{$rtn .= '>'.$sets{$set}.'</option>'."\n";}
        	}
        $rtn .= '</select>'."\n";
		$rtn .= qq|<input name="_submit" onClick="return confirm('Convert to Character set '+document.charset._charset.value+'?');" type="submit" style="font-size:11px;" value="Convert...">\n|;
		$rtn .= $cmessage;
		}
     elsif($m1=~/^SearchReplace$/is){
		#Import
		$rtn .= qq|<form name="SearchReplace" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" enctype="multipart/form-data" onSubmit="return submitForm(this);">\n|;
		$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
		$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
		$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
		#Search what table?
		$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="3" border="0" style="width:550px;">\n|;
		$rtn .= qq|	<tr valign="bottom" align="center">\n|;
		$rtn .= qq|		<td nowrap>Search in what Table/Fields?</td><td valign="bottom">\n|;
		$rtn .= qq|\t\t\t<select name="_table" required="1" requiredmsg="Search in what table?" style="font-size:12px;"|;
		$rtn .= qq| onChange="callWaSQL(0,'display_fields','&tablename='+this.value+'&fieldname=_table_fields&_table_fields=ALL&inputtype=MultiSelect&width=120&formname=SearchReplace&showname=Fields+for+'+this.value+'&tvals=getDBFields('+this.value+',1)');">\n\t\t\t\t<option value="" style="color:#ACA899;">Table</option>\n|;
		my @DBTables=getDBTables();
		foreach my $stable (sort(@DBTables)){
			my $dtable=ucfirst($stable);
			$sel=&selectCheck($stable,$input{_table},0);
			$rtn .= qq|\t\t\t\t<option value="$stable"$sel>$dtable</option>\n|;
			}
		$rtn .= qq|\t\t\t</select>\n|;
		if(length($input{_table})){
			#callWaSQL function on load to load up the field list.
			$rtn .= qq|<script language="javascript">|;
			$rtn .= qq|callWaSQL(0,'display_fields','&tablename=$input{_table}&fieldname=_table_fields&_table_fields=$input{_table_fields}&inputtype=MultiSelect&width=120&formname=SearchReplace&showname=Fields+for+$input{_table}&tvals=getDBFields($input{_table},1)');|;
			$rtn .= qq|</script>|;
	          }
		$rtn .= qq|\t\t</td>\n\t\t<td align="left" valign="bottom" style="width:100%" id="display_fields"></td>\n|;
		$rtn .= qq|\t\t<td align="left" nowrap style="font-size:9pt;" title="Limit this change to the first X number of records.">Limit<br><input type="text" mask="\^\[0-9\]\+\$" maskmsg="Limit must be a positive integer value." value="$input{_limit}" name="_limit" style="width:50" maxlength="5"></td>\n|;
		$sel=&selectCheck(1,$input{_ignorecase},1);
		$rtn .= qq|\t\t<td align="left" nowrap style="font-size:9pt;" title="Ignore case when replacing.">Ignore<br>Case<br><input type="checkbox" name="_ignorecase" value="1" $sel></td>\n|;
		$sel=&selectCheck(1,$input{_global},1);
		$rtn .= qq|\t\t<td align="left" nowrap style="font-size:9pt;" title="In each record, replace all occurances found.">Replace<br>All<br><input type="checkbox" name="_global" value="1" $sel></td>\n|;
		$rtn .= qq|	</tr>\n|;
		#Search For
		$rtn .= qq|	<tr valign="top"><td nowrap>Search for what?</td><td colspan="5"><textarea required="1" requiredmsg="Search for what?" name="_sstr" style="width:370px;height:50px;">$input{_sstr}</textarea></td></tr>\n|;
		#Replace With
		$rtn .= qq|	<tr valign="top"><td nowrap>Replace with what?</td><td colspan="5"><textarea required="1" requiredmsg="Replace with what?" name="_rstr" style="width:370px;height:50px;">$input{_rstr}</textarea></td></tr>\n|;
		#where
		$rtn .= qq|	<tr valign="top"><td nowrap>Limit to records where...</td><td colspan="5"><textarea name="_wstr" style="width:370px;height:50px;">$input{_wstr}</textarea></td></tr>\n|;
		#Result fields
		$rtn .= qq|	<tr valign="top"><td nowrap title="Display results in a table, otherwise only the id numbers are displayed.">Show results</td>|;
		$sel=&selectCheck(1,$input{_display},1);
		$rtn .= qq|\t\t<td align="left" colspan="2"><input type="checkbox" name="_display" value="1" $sel></td>\n|;

		$rtn .= qq|	</tr>\n|;
          $rtn .= qq|</table>\n|;
		$rtn .= qq|<input name="_submit" type="submit" style="font-size:11px;" value="Replace...">\n|;
		#Process searchReplace Requests
		if(length($input{_table}) && length($input{_sstr}) && length($input{_rstr})){
			my @pairs=();
			if(length($input{_wstr})){push(@pairs,_where=>$input{_wstr});}
			if(length($input{_table_fields})){push(@pairs,_fields=>$input{_table_fields});}
			if(length($input{_limit})){push(@pairs,_limit=>$input{_limit});}
			if(length($input{_ignorecase})){push(@pairs,_ignorecase=>$input{_ignorecase});}
			if(length($input{_global})){push(@pairs,_global=>$input{_global});}
			$rtn .= qq|<hr>\n|;
               my ($err,$sql,$cnt,@ids)=searchDBReplace($input{_table}, $input{_sstr}, $input{_rstr},@pairs);
               my $idcnt=@ids;
               #$rtn .= qq|($err,$sql,$cnt,$idcnt)<br>\n|;
               if($err){
				$rtn .= qq|<div class="w_inten"><b>Error:</b> $err</div>\n|;
				$rtn .= qq|<div class="w_inten"><b>Query:</b> $sql</div>|;
	               }
               elsif(!$idcnt){
				$rtn .= qq|<div class="w_inten"><b>Query:</b> $sql</div>|;
                    $rtn .= qq|<div class="w_inten"><b>Results:</b> Searched <b>$cnt</b> records, modified <b>$idcnt</b>.</div>\n|;
	               }
	          else{
				$rtn .= qq|<div class="w_inten"><b>Query:</b> $sql</div>|;
	               $rtn .= qq|<div class="w_inten"><b>Results:</b> Searched <b>$cnt</b> records, modified <b>$idcnt</b>.</div>\n|;
	               if($input{_display}==1){
					$rtn .= qq|<div class="w_intwenty">\n|;
					my %list=();
					my @fields=();
					if($input{_table_fields}){@fields=split(/[:,]/,$input{_table_fields});}
					else{@fields=getDBFields($input{_table},1);}
					my $fieldstr=join(',',@fields);
					my $idstr=join(',',@ids);
					my $sql=qq|select _id,$fieldstr from $input{_table} where _id in ($idstr) order by _id|;
					my $cnt=getDBData(\%list,$sql,"nocount=1");
					$rtn .= hash2Html(\%list,_highlight=>$input{_rstr});
					$rtn .= qq|</div>\n|;
					}
				else{
					$rtn .= qq|<div class="w_inten"><b>IDs of changed records:</b> \n|;
					$rtn .= join(',',@ids);
					$rtn .= qq|</div>\n|;
					}
				}
	          }
		}
	return $rtn;
	}
#####################
sub fileManager{
	my $rtn='';
	$rtn .= qq|<form name="fileManager" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}" enctype="multipart/form-data">\n|;
	$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
	$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
	$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
	$rtn .= qq|<input type="hidden" name="_fmactor" value="">\n|;
	$rtn .= qq|Document Root: $ENV{DOCUMENT_ROOT}<br>\n|;
	$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="2" border="1">\n|;
	$rtn .= qq|	<tr>\n|;
	$rtn .= qq|		<th>Directory</th>\n|;
	$rtn .= qq|		<th>Filter</th>\n|;
	$rtn .= qq|		<th>List</th>\n|;
	$rtn .= qq|	</tr>\n|;
	#directory

	$rtn .= qq|	<tr>\n|;
	$rtn .= qq|		<td>\n|;
	my $dir=$input{ipath_fmfile} || "/";
	$dir=~s/^[\\\/]+//s;
	my $ldir="$ENV{DOCUMENT_ROOT}/$dir";
	$rtn .= qq|<input type="text" name="ipath_fmfile" style="width:100%;background:#FFF;" value="$dir" READONLY>\n|;
# 	$rtn .= qq|		<select name="ipath_fmfile" style="width:100%;">\n|;
# 	foreach my $dir (@dirs){
# 	 	$dir=~s/[\/\\]+/\//sg;
# 		$dir=~s/^\Q$ENV{DOCUMENT_ROOT}\E//s;
# 		next if $dir=~/^cgi\-bin/is;
# 		$rtn .= qq|		<option value="$dir"|;
# 		if($input{ipath_fmfile}=~/^\Q$dir\E$/is){$rtn .= qq| selected|;}
# 		$rtn .= qq|>$dir</option>\n|;
# 		}
# 	$rtn .= qq|		</select>\n|;
	$rtn .= qq|		</td>\n|;
	#Filter
	$rtn .= qq|		<td align="right"><input type="text" name="_fmfilter" value="$input{_fmfilter}" maxlength="50" style="width:50px;text-align:right;"></td>\n|;
	$rtn .= qq|		<td align="right"><input type="submit" value="List"></td>\n|;
	$rtn .= qq|	<tr>\n|;
	#File Upload
	my $perm=$input{_fmperm} || 744;
	$rtn .= qq|	<tr>\n|;
	$rtn .= qq|		<th>File to Upload</th>\n|;
	$rtn .= qq|		<th>Perms</th>\n|;
	$rtn .= qq|		<th>Upload</th>\n|;
	$rtn .= qq|	</tr>\n|;
	$rtn .= qq|	<tr>\n|;
	$rtn .= qq|		<td style="width:225px;"><input type="file" name="fmfile" style="width:150px;"></td>\n|;
	$rtn .= qq|		<td align="right"><input type="text" name="_fmperm" value="$perm" maxlength="3" style="width:50px;text-align:right;"></td>\n|;
	$rtn .= qq|		<td align="right"><input type="submit" name="_fmaction" value="Upload"></td>\n|;
	$rtn .= qq|	</tr>\n|;
	$rtn .= qq|</table>\n|;
	#Check for _fmaction and perform action before listing files
	if($input{_fmaction}){
		my $action=$input{_fmaction};
		if($action=~/^del$/is && $input{_fmactor}){
			#delete file
			my $actor=$input{_fmactor};
			$actor=~s/^[\\\/]+//s;
			my $file="$ENV{DOCUMENT_ROOT}/$actor";
			unlink($file);
			}
		elsif($action=~/^Upload$/is && $input{fmfile}){
			my $actor=$input{fmfile};
			$actor=~s/^[\\\/]+//s;
			my $file="$ENV{DOCUMENT_ROOT}/$actor";
			my $perm=$input{_fmperm} || 777;
			my $mp=chmod(oct("0$perm"),$file);
	          }
	     }
	#List Files
	my @files=listFiles($ldir);
	@files=sortTextArray(@files);
	my $fcnt=@files;
	$rtn .= qq|<div class="w_smaller w_lblue">Files in $ldir</div>\n|;
	if($input{_fmfilter}){$rtn .= qq|<div class="w_smaller w_lblue" style="padding-left:20px;"><u>Filter Applied:</u> $input{_fmfilter}</div>\n|;}
	if($fcnt){
        $rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="2" border="1">\n|;
		$rtn .= qq|	<tr>\n|;
		$rtn .= qq|		<th>#</th>\n|;
		$rtn .= qq|		<th>Name</th>\n|;
		$rtn .= qq|		<th>Type</th>\n|;
		$rtn .= qq|		<th>Size</th>\n|;
		$rtn .= qq|		<th>Modified</th>\n|;
		$rtn .= qq|		<th>Perm</th>\n|;
		$rtn .= qq|		<th>action</th>\n|;
		$rtn .= qq|	</tr>\n|;
		my $r=0;
		my $total=0;

		foreach my $file (@files){
		#next if $stat{type}=~/^directory$/is;
			#filter?
			if(length(strip($input{_fmfilter})) && $file !~/\Q$input{_fmfilter}\E/is){next;}
			my %stat=();
			my $err=fileStat(\%stat,"$ldir/$file","AM ND YY at RH:MM PM");
			my $bgcolor='#F1F0ED';
			if(isEven($r)){$bgcolor='#FFFFFF';}
			$r++;
			$total += $stat{size};
			my $link="/$dir/$file";
			$link=~s/^[\\\/]+/\//s;
            $rtn .= qq|	<tr bgcolor="$bgcolor">\n|;
            $rtn .= qq|		<td>$r</td>\n|;
            if($stat{type}=~/^directory$/is){
				$rtn .= qq|		<td>$file</td>\n|;
            	}
            else{
				$rtn .= qq|		<td><a href="$link" class="w_link" target="_new">$file</a></td>\n|;
				}
			$rtn .= qq|<td>$stat{type}</td>\n|;
			if($stat{size_gb} >= 1){$rtn .= qq|		<td align="right" title="gigabytes">$stat{size_gb} G</td>\n|;}
			elsif($stat{size_mb} >= 1){$rtn .= qq|		<td align="right" title="megabytes">$stat{size_mb} M</td>\n|;}
			elsif($stat{size_kb} >= 1){$rtn .= qq|		<td align="right" title="kilobytes">$stat{size_kb} K</td>\n|;}
			else{$rtn .= qq|		<td align="right" title="bytes">$stat{size} B</td>\n|;}
			$rtn .= qq|		<td>$stat{modified}</td>\n|;
			$rtn .= qq|		<td>$stat{permissions}</td>\n|;
			$rtn .= qq|		<td>|;
			if($stat{type}=~/^directory$/is){
				$rtn .= qq|<input type="submit" value="List" onClick="document.fileManager.ipath_fmfile.value='$file';">\n|;
            	}
            else{
				$rtn .= qq|<input type="submit" name="_fmaction" value="Del" onClick="var ok=confirm('Delete $dir/$file?'); if(ok){document.fileManager._fmactor.value='$dir/$file';return true;}else{return false;}">|;
				}
			$rtn .= qq|</td>\n|;
			$rtn .= qq|	</tr>\n|;
	          }
   		$total_kb=sprintf("%.0f",($total/1024)) || 0;
		$total_mb=sprintf("%.1f",($total/1024/1024)) || 0;
		$total_gb=sprintf("%.2f",($total/1024/1024/1024)) || 0;
	     $rtn .= qq|	<tr align="right">\n|;
		$rtn .= qq|		<th colspan="2">Total Size</th>\n|;
		if($total_gb >= 1){$rtn .= qq|		<th title="gigabytes">$total_gb G</th>\n|;}
		elsif($total_mb >= 1){$rtn .= qq|		<th title="megabytes">$total_mb M</th>\n|;}
		elsif($total_kb >= 1){$rtn .= qq|		<th title="kilobytes">$total_kb K</th>\n|;}
		else{$rtn .= qq|		<th title="bytes">$total Bytes</th>\n|;}
		$rtn .= qq|		<th colspan="3"></th>\n|;
		$rtn .= qq|	</tr>\n|;
	     }
	$rtn .= qq|</form>\n|;
	return $rtn;
	}
#####################
sub buildManageMenu{
	my @m2opts=();
	my $menu='';
	my $sUrl=$ENV{SCRIPT_NAME};
	my $dot = '';
	#### '&' . '#149' . ';';
	#Begin Menu
	$menu .= qq|<script language="javascript" src="/wfiles/js/detect.js"></script>\n|;
	$menu .= qq|<table cellspacing="0" cellpadding="0" border="0"><tr><td>|;
	$menu .= qq|<ul class="menu menu-horizontal">\n|;
	#$menu .= qq|<div style="float:right;margin-right:10px;"><b>$ENV{HTTP_HOST}</b></div>\n|;
	$menu .= qq|<div style="float:left;margin-top:3px;" title="WaSQL Version $version"><img src="/wfiles/wasql.gif" border="0"></div>\n|;
	if($USER{_id} && $USER{utype} == 0){
		#User is An Admin
		#Site
		my $m0="Site";
		my $gif=lc($m0);
		$menu .= qq|	<li class="expanded"><a href="#" class="w_topmenu"><img src="/wfiles/$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=File+Manager">$dot File Manager</a></li>\n|;
		my $wburl=$cgiroot;
		$wburl=~s/wasql\.pl$/wb\.pl/is;
		my $dcurl=$cgiroot;
		$dcurl=~s/wasql\.pl$/dc\.pl/is;
		$menu .= qq|			<li><a href="$wburl" target="_new">$dot Benchmark</a></li>\n|;
		$menu .= qq|			<li><a href="$dcurl" target="_new">$dot DomainCheck</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_logout=1" title="$USER{username}">$dot Log off</a></li>\n|;
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		#Database
		$m0="Database";
		my $gif=lc($m0);
		$menu .= qq|	<li  class="expanded"><a href="#" class="w_topmenu"><img src="/wfiles/$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Sql"><img src="/wfiles/sqlprompt.gif" border="0" style="vertical-align: bottom;"> SQL Prompt</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Backup">$dot Backup</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Schema"><img src="/wfiles/schema.gif" border="0" style="vertical-align: bottom;">Schema</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Import">$dot Import</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Export">$dot Export</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Charset">$dot Character Sets</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=SearchReplace" title="Search and Replace text in multiple records of a table">$dot Search&Replace</a></li>\n|;
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		#Table
		$m0="Table";
		my $gif=lc($m0);
		$menu .= qq|	<li class="w_tablemenu expanded"><a href="#" class="w_topmenu"><img src="/wfiles/$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=New+table"> + New Table</a></li>\n|;
		my @tables=getDBTables();
		@tables=sortTextArray(@tables);
		my $divider=0;
		my $tcnt=0;
		my %ShowTable=();
		#only show internal tables if $Config{showtables}
		if($Config{showtables}){
			if($Config{showtables}=~/^all$/is){$ShowTable{all}=1;}
			else{
				my @showtables=split(/[\;\,]/,$Config{showtables});
				foreach my $showtable (@showtables){
					$showtable=strip($showtable);
					$ShowTable{$showtable}=1;
                    }
                }
			}
		my $tablecnt=@tables;
		for (my $t=0;$t<$tablecnt;$t++){
			my $table=$tables[$t];
			my $show=0;
			next if $table=~/^\_/ && !$ShowTable{$table} && !$ShowTable{all};
			my $dname=capitalize($table);
			$tcnt++;
			if(length($dname) > 20){$dname=substr($dname,0,18) . "...";}
			if($tcnt==1){$menu .= qq|			<li id="w_spacer" class="expanded">|;}
			else{
				if($table !~/^\_/is && $tables[$t-1]=~/^\_/){
					$menu .= qq|			<li id="w_spacer" class="expanded">|;
	            	}
            	else{$menu .= qq|			<li class="expanded">|;}
            	}
            my $tableimgfile="$ENV{DOCUMENT_ROOT}/wfiles/$table\.gif";
			if($table=~/^\_(users|forms|history|pages|templates|fielddata|tabledata)$/is){
				$dot=qq|<img src="/wfiles/$table\.gif" border="0" style="vertical-align: bottom;">|;
            	}
            else{$dot='';}
			$menu .= qq|<a href="$sUrl\?_m0=$m0\&_m1=$table" title="$table">$dot $dname</a>\n|;
			$dot='';
			$dname=capitalize($table);
			$menu .= qq|				<ul title="$table">\n|;
			$menu .= qq|					<div align="center" id="menutitle">$dname</div>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Properties&_action=Schema">$dot Schema</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Indexes">$dot Indexes</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Properties">$dot Properties</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=List+Data">$dot List Data</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Add+New">$dot Add New</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Truncate" onClick="return confirm('Truncate $table\?\\nThis will delete all records and reset the auto_incriment.\\nAre you sure\?');">$dot Truncate</a></li>\n|;
			if($table !~/^\_/s){
				$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$table\&_m2=Drop+Table" onClick="return confirm('Drop table $table\?\\n Are you sure\?');">$dot Drop Table</a></li>\n|;
				}
			$menu .= qq|				</ul>\n|;
			$menu .= qq|			</li>\n|;
			}
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		#Pages
		$m0="Pages";
		my $gif=lc($m0);
		$menu .= qq|	<li class="expanded"><a href="#" class="w_topmenu"><img src="/wfiles/_$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Add+New"> + New Page</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=List"> - List All</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Publish+All" onclick="return confirm('Publish all pages with a publish filename specified?');"> - Publish All</a></li>\n|;
		my %list=();
		my $sql=qq|select _id,name,publish from _pages order by _edate desc,_cdate desc|;
		my $cnt=getDBData(\%list,$sql,"nocount=1;limit=15");
		for(my $x=0;$x<$cnt;$x++){
			my $name=$list{$x}{name};
			my $id=$list{$x}{_id};
			my $dname=$name;
			if(length($dname) > 18){$dname=substr($dname,0,15) . "...";}
			if($x==0){$menu .= qq|			<li id="w_spacer" class="expanded">|;}
			else{$menu .= qq|			<li class="expanded">|;}
			$menu .= qq|<a href="$sUrl\?_m0=$m0\&_m1=$id" title="$id\.$name">$dot $id\. $dname</a>\n|;
			$dname=$name;
			$menu .= qq|				<ul title="$name">\n|;
			$menu .= qq|					<div align="center" id="menutitle">$dname</div>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$id\&_m2=View">$dot View</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$id\&_m2=Edit">$dot Edit</a></li>\n|;
			$menu .= qq|					<li><a href="$sUrl\?_m0=$m0\&_m1=$id\&_m2=Publish">$dot Publish</a></li>\n| if length($list{$x}{publish});
			$menu .= qq|				</ul>\n|;
			$menu .= qq|			</li>\n|;
			}
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		#Templates
		$m0="Templates";
		my $gif=lc($m0);
		$menu .= qq|	<li class="expanded"><a href="#" class="w_topmenu"><img src="/wfiles/_$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Add+New"> + New Template</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=List">- List All</a></li>\n|;
		my %list=();
		my $sql=qq|select _id,name from _templates order by _edate desc,_cdate desc|;
		my $cnt=getDBData(\%list,$sql,"nocount=1;limit=15");
		for(my $x=0;$x<$cnt;$x++){
			my $name=$list{$x}{name};
			my $id=$list{$x}{_id};
			my $dname=$name;
			if(length($dname) > 15){$dname=substr($dname,0,13) . "...";}
			if($x==0){$menu .= qq|			<li id="w_spacer">|;}
			else{$menu .= qq|			<li>|;}
			$menu .= qq|<a href="$sUrl\?_m0=$m0\&_m1=$id" title="$id\.$name">$dot $id\. $dname</a></li>\n|;
			}
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		#Users
		my $m0="Users";
		my $gif="_" . lc($m0);
		$menu .= qq|	<li class="expanded"><a href="#" class="w_topmenu"><img src="/wfiles/$gif\.gif" border="0" style="vertical-align: bottom;">$m0</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=Add+New"> + New User</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=List"> - List All</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=My+Profile">$dot My Profile</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=$m0\&_m1=My+API+Key">$dot My API Key</a></li>\n|;
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		}
	#Help
	if($USER{_id}){
		$menu .= qq|	<li class="expanded"><a href="#"  class="w_topmenu"><img src="/wfiles/help\.gif" border="0" style="vertical-align: bottom;">Help</a>\n|;
		$menu .= qq|		<ul>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=help&_m1=Help+Contents">Help Contents</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=help&_m1=About+Wasql">$dot About WaSQL</a></li>\n|;
		$menu .= qq|			<li id="w_spacer"><a href="$sUrl\?_m0=help\&_m1=Environment">$dot Environment</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=help\&_m1=Html+Charset">$dot ASCII Charset</a></li>\n|;
		$menu .= qq|			<li id="w_spacer"><a href="http://www.wasql.com" target="_new">$dot Website</a></li>\n|;
		$menu .= qq|			<li><a href="$sUrl\?_m0=help&_m1=Run+Update">$dot Check for Updates</a></li>\n|;
		$menu .= qq|			<li><a href="#" onclick="bugBase(1,'category=WaSQL');return false;">Report Problems</a></li>\n|;
		$menu .= qq|			<li><a href="http://www.wasql.com/forum.htm" target="_new">$dot WaSQL Forum</a></li>\n|;
		$menu .= qq|		<br><br></ul>\n|;
		$menu .= qq|	</li>\n|;
		}
	#End Menu
	$menu .= qq|</ul>\n|;
	$menu .= qq|</td></tr></table>\n|;
	return $menu;
	}
#####################
sub selectCheck{
	my $val=shift || return;
	my $inpval=shift || return;
	my $checkbox=shift;
	if($checkbox){
		my @vals=split(/\:/,$inpval);
		foreach my $bval (@vals){
			if($val=~/^\Q$bval\E$/is){return " checked";}
			}
		}
	elsif($val=~/^\Q$inpval\E$/is){return " selected";}
	return;
	}
#####################
sub tableProperties{
	my $rtn='';
	my $table=shift || return;
	$rtn .= qq|<form class="w_form" name="tableProperties" class="w_form" method="POST" action="$ENV{SCRIPT_NAME}">\n|;
	$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n|;
	$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n|;
	$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n|;
	$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="2" border="0"><tr valign="top"><td>\n|;
	$rtn .= qq|<table class="w_table" cellspacing="0" cellpadding="2" border="1">\n|;
	$rtn .= qq|	<tr align="center"><th colspan="7">$table Table</th></tr>\n|;
	$rtn .= qq|	<tr align="center">\n|;
	$rtn .= qq|		<th>Field Name</th><th>Display Name</th><th>Width</th><th>Height</th><th>Max</th><th>Req</th><th>Action</th>\n|;
	$rtn .= qq|</tr>\n|;
	my %plist=();
	my @flds=qw(displayname width height inputmax required);
	my @fields=getDBFields($table);
	@fields=sort(@fields);
	#Apply Changes
	#$input{_debug}="Here<br>\n";
	if($input{_action}=~/^Alter Schema$/is){
		&AlterSchema($table);
		$input{_debug}="alterSchema<br>\n";
		@fields=getDBFields($table);
		@fields=sort(@fields);
		}
	elsif($input{_action}=~/^(Show Admin Form|Show Non-Admin Form|Apply Changes)$/is){
		#_tabledata
		my $cnt=getDBData(\%plist,"select _id from _tabledata where tablename like '$table'","nocount=1");
		my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
		$input{_debug} .= "tabledata[$cnt]<br>\n";
		if($cnt==1){
			my $id=$plist{0}{_id};
			my $ck=editDBData("_tabledata","_id=$id",
				_edate		=> $cdate,
				listfields	=> $input{listfields} || 'NULL',
				formfields	=> $input{formfields} || 'NULL',
				listfields_mod	=> $input{listfields_mod} || 'NULL',
				formfields_mod	=> $input{formfields_mod} || 'NULL',
				);
			$input{_debug} .= "tabledata - editDBData[$ck]<br>\n";
			}
		elsif($cnt==0){
			my $ck=addDBData("_tabledata",
				_cdate		=> $cdate,
				tablename	=> $table,
				listfields	=> $input{listfields} || 'NULL',
				formfields	=> $input{formfields} || 'NULL',
				listfields_mod	=> $input{listfields_mod} || 'NULL',
				formfields_mod	=> $input{formfields_mod} || 'NULL',
				);
			$input{_debug} .= "tabledata - addDBData[$ck]<br>\n";
			}
		#_fielddata
		foreach my $field (@fields){
			my @sets=();
			foreach my $fld (@flds){
				my $val=strip($input{"$field\_$fld"});
				if(length($val)==0){$val='NULL';}
				push(@sets,$fld=>$val);
				#$input{_debug} .= qq|[$field\_$fld] $fld => $val <br>\n| if $fld=~/mask/is;
				}
			%plist=();
			my $cnt=getDBData(\%plist,"select _id from _fielddata where tablename like '$table' and fieldname like '$field'","nocount=1");
			#$input{_debug} .= "<br>$field fielddata[$cnt]<br>\n";
			if($cnt==1){
				my $id=$plist{0}{_id};
				my $ck=editDBData("_fielddata","_id=$id",
					_edate		=> $cdate,
					@sets
					);
				#$input{_debug} .= "$field - editDBData[$ck][@sets]<br>\n";
				}
			elsif($cnt==0){
				my $ck=addDBData("_fielddata",
					_cdate		=> $cdate,
					tablename	=> $table,
					fieldname	=> $field,
					@sets
					);
				#$input{_debug} .= "$field - addDBData[$ck][@sets]<br>\n";
				}
			}
		}
	#Gather _fielddata record for this table
	my %Finfo=();
	%plist=();
	my $moreid;
	my $tcnt=getDBData(\%plist,"select * from _fielddata where tablename like '$table'","nocount=1");
	for(my $x=0;$x<$tcnt;$x++){
		my $field=$plist{$x}{fieldname};
		if(length($input{"$field\_more"})){$moreid=$plist{$x}{_id};}
		foreach my $fld (@flds){$Finfo{$field}{$fld}=$plist{$x}{$fld};}
		}
	#check to see if we should add a
	my $moreadd;
	if(!$moreid){
		foreach my $field (@fields){
			if(length($input{"$field\_more"})){$moreadd=$field;}
			}
		}
	#Gather _tabledata record for this table
	%plist=();
	my $tcnt=getDBData(\%plist,"select listfields,formfields,listfields_mod,formfields_mod from _tabledata where tablename like '$table'","nocount=1;limit=1");
	#Show field information
	my $tabindex=0;
	foreach my $field (@fields){
		$rtn .= qq|<tr id="$field">\n|;
		$rtn .= qq|	<td>$field</td>|;
		$tabindex++;
		$rtn .= qq|	<td><input tabindex="$tabindex" type="text" maxlength="150" style="border:0px;font-size:12px;width:125px;" name="$field\_displayname" value="$Finfo{$field}{displayname}"></td>|;
		$tabindex++;
		$rtn .= qq|	<td><input tabindex="$tabindex" type="text" maxlength="5" style="border:0px;font-size:12px;width:40px;" name="$field\_width" value="$Finfo{$field}{width}"></td>|;
		$tabindex++;
		$rtn .= qq|	<td><input tabindex="$tabindex" type="text" maxlength="5" style="border:0px;font-size:12px;width:40px;" name="$field\_height" value="$Finfo{$field}{height}"></td>|;
		#max
		$tabindex++;
		$rtn .= qq|	<td><input tabindex="$tabindex" type="text" maxlength="10" style="border:0px;font-size:12px;width:40px;" name="$field\_inputmax" value="$Finfo{$field}{inputmax}"></td>|;
		$tabindex++;
		$rtn .= qq|	<td align="center"><input tabindex="$tabindex" type="checkbox" style="font-size:12px;" name="$field\_required" value="1"|;
		if($Finfo{$field}{required}==1){$rtn .= qq| checked|;}
		$rtn .= qq|></td>|;
		$rtn .= qq|	<td><input type="submit" value="More" name="$field\_more" style="border:0px;background-color:#ffffff;color:#336699;cursor:pointer;"></td>|;
		$rtn .= qq|</tr>\n|;
		}
	#Admin List Fields
	$rtn .= qq|<tr valign="top"><td style="background-color:#6699cc;color:#ffffff" nowrap="true">Administrator<br>List Data Fields</td><td colspan="6">|;
	#<input type="text" maxlength="255" style="border:0px;font-size:12px;width:330px;" name="listfields" value="$plist{0}{listfields}">
	my %ftag=(
		inputtype=>"textarea",
		fieldname=>"listfields",
		tablename=>"_tabledata",
		formname=>"tableProperties",
		width=>350,
		height=>60,
		style=>"border:1px solid #E0E0E0;font-size:11pt;",
		help=>"List layout for Admins. List the fields or supply an html template with fields in square brackets."
		);
	$rtn .= &buildTag(\%ftag,$plist{0}{listfields});
	$rtn .= qq|</td></tr>\n|;
	#Admin Form Fields
	$rtn .= qq|<tr valign="top"><td style="background-color:#6699cc;color:#ffffff" nowrap="true">Administrator<br>Form Layout</td><td colspan="6">|;
	my %ftag=(
		inputtype=>"textarea",
		fieldname=>"formfields",
		tablename=>"_tabledata",
		formname=>"tableProperties",
		tvals=>"drag",
		width=>350,
		height=>100,
		style=>"border:1px solid #E0E0E0;font-size:11pt;",
		help=>"Form layout for Admins. Colon separate for same line, comma or line separate for new row. Or supply an html template with fields in square brackets."
		);
	$rtn .= &buildTag(\%ftag,$plist{0}{formfields});
	#$rtn .= qq|<input type="text" maxlength="255" style="border:0px;font-size:12px;width:330px;" name="formfields" value="$plist{0}{formfields}">|;
	$rtn .= qq|</td></tr>\n|;
	#Mod List Fields
	$rtn .= qq|<tr valign="top"><td style="background-color:#6699cc;color:#ffffff" nowrap="true">Non-Admin<br>List Data Fields</td><td colspan="6">|;
	#<input type="text" maxlength="255" style="border:0px;font-size:12px;width:330px;" name="listfields_mod" value="$plist{0}{listfields_mod}">
	my %ftag=(
		inputtype=>"textarea",
		fieldname=>"listfields_mod",
		tablename=>"_tabledata",
		formname=>"tableProperties",
		width=>350,
		height=>60,
		style=>"border:1px solid #E0E0E0;font-size:11pt;",
		help=>"List layout for Non-Admins. List the fields or supply an html template with fields in square brackets."
		);
	$rtn .= &buildTag(\%ftag,$plist{0}{listfields_mod});
	$rtn .= qq|</td></tr>\n|;
	#Mod Form Fields
	$rtn .= qq|<tr valign="top"><td style="background-color:#6699cc;color:#ffffff" nowrap="true">Non-Admin<br>Form Layout</td><td colspan="6">|;
	%ftag=(
		inputtype=>"textarea",
		fieldname=>"formfields_mod",
		tablename=>"_tabledata",
		formname=>"tableProperties",
		width=>350,
		height=>100,
		style=>"border:1px solid #E0E0E0;font-size:11pt;",
		help=>"Form layout for Non-Admins. Colon separate for same line, comma or line separate for new row. Or supply an html template with fields in square brackets."
		);
	$rtn .= &buildTag(\%ftag,$plist{0}{formfields_mod});
	#$rtn .= qq|<input type="text" maxlength="255" style="border:0px;font-size:12px;width:330px;" name="formfields_mod" value="$plist{0}{formfields_mod}">|;
	$rtn .= qq|</td></tr>\n|;
	$rtn .= qq|\n|;
	$rtn .= qq|<tr align="center"><th colspan="7">|;
	$rtn .= qq|<input type="submit" name="_action" value="Schema" style="font-size:12px;">|;
	$rtn .= qq|<input type="submit" name="_action" value="Show Admin Form" style="font-size:12px;">|;
	$rtn .= qq|<input type="submit" name="_action" value="Show Non-Admin Form" style="font-size:12px;">|;
	$rtn .= qq|<input type="submit" name="_action" value="Apply Changes" style="font-size:12px;">|;
	$rtn .= qq|</th></tr>\n|;
	$rtn .= qq|</table>\n|;
	$rtn .= qq|</td>|;
	#More
	if($moreid){
		$rtn .= qq|</form>\n|;
		$rtn .= qq|<td>Edit Field Properties [$moreid]|;
		$rtn .= &AddEditForm(_table=>"_fielddata",_id=>$moreid);
		$rtn .= qq|</td>|;
		}
	elsif($moreadd){
		$rtn .= qq|</form>\n|;
		$rtn .= qq|<td>Add Field Properties [$moreadd]|;
		$rtn .= &AddEditForm(_table=>"_fielddata",tablename=>$table,fieldname=>$moreadd);
		$rtn .= qq|</td>|;
		}
	elsif($input{_action}=~/^Show Admin Form$/is){
		$rtn .= qq|</form>\n|;
		$rtn .= qq|<td>Form for $table|;
		$rtn .= &AddEditForm(_table=>$table);
		$rtn .= qq|</td>|;
		}
	elsif($input{_action}=~/^Show Non-Admin Form$/is){
		$rtn .= qq|</form>\n|;
		$rtn .= qq|<td>Form for $table|;
		$rtn .= &AddEditForm(_table=>$table,_mod=>1);
		$rtn .= qq|</td>|;
		}
	elsif($input{_action}=~/^Schema$/is){
		$rtn .= qq|<td><div style="width:350px;">|;
		$rtn .= qq|<div style="float:right;font-size:10pt;">\n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title,1);" title=" varchar(255) Default NULL">VCDN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title,1);" title=" integer Default NULL">IDN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title);" title=" varchar(255)">VC</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title);" title=" integer">INT</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title,1);" title=" Default NULL">DN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title,1);" title=" NOT NULL">NN</a> \n|;
          $rtn .= qq|<a href="#" class="w_link" onClick="appendText(document.tableProperties._schema,this.title,1);" title=" NOT NULL Unique">NNU</a> \n|;
          $rtn .= qq|</div><b>Schema for $table</b>\n|;
          $rtn .= qq|</div>\n|;
		$rtn .= qq|<textarea style="width:350px;height:275px;" name="_schema" wrap="off">\n|;
		my $schema=&getTableSchema($table,1);
		$rtn .= $schema;
		$rtn .= qq|\n</textarea>\n|;
		$rtn .= qq|<div align="right"><input type="submit" name="_action" value="Alter Schema" style="font-size:12px;" onClick="var chk=confirm('Alter the existing schema for $table to only include the following fields:\\n'+document.tableProperties._schema.value+'\\nAre you sure you want to do this? Click OK to alter the schema.');if(!chk){return false;}"></div>|;
		$rtn .= qq|</td>|;
		$rtn .= qq|</form>\n|;
		}
	else{$rtn .= qq|</form>\n|;}
	$rtn .= qq|</table>\n|;
	return $rtn;

	}
###########
return 1;
