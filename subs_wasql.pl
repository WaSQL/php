#subs_wasql.pl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
our %PerlID=();
##################
sub includePage{
	my $page=shift || return;
	my %params=@_;
	my $pcnt=@_;
	my @opts=();
	if(isNum($page)){push(@opts,_id=>$page);}
	else{push(@opts,name=>$page);}
	my %rec=getDBRecord(-table=>"_pages",@opts);
	if($rec{-error}){
		return qq|<img src="/wfiles/alert.gif" title="$rec{-error}"> Error: $rec{-error}.|;
    	}
    #initialize any inputs passed in as params
    local %input=();
    foreach my $key (keys(%params)){$input{$key}=$params{$key};}
    return evalPerl($rec{body});
	}
##################
sub viewPage{
	my $id=shift || return;
	my %params=@_;
	local %input=();
	foreach my $key (keys(%params)){$input{$key}=$params{$key};}
	return viewData("_pages","_id=$id");
	}
##################
sub getMediaTag{
	my $file=shift || return 'No file';
	my $autoplay=shift;
	my $start=$autoplay?'true':'false';
	my $rtn='';
	if($file=~/\.(jpg|png|gif|jpeg|bmp)$/is){
		#image file
		return qq|<img src="$file" border="0">|;
		}
	elsif($file=~/\.flv$/is){
		#flash video
		my $curl=qq|/wfiles/player.swf?file=$file&size=true&aplay=$start&autorew=false&title=|;
		$rtn .= qq|<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="406" height="359" id="player" align="middle">\n|;
		$rtn .= qq|<param name="movie" value="$curl" />\n|;
		$rtn .= qq|<param name="menu" value="false" />\n|;
		$rtn .= qq|<param name="quality" value="high" />\n|;
		$rtn .= qq|<param name="wmode" value="transparent" />\n|;
		$rtn .= qq|<param name="bgcolor" value="#000000" />\n|;
		$rtn .= qq|<embed src="$curl" menu="false" quality="high" wmode="transparent" bgcolor="#FFFFFF" width="406" height="359" name="player" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />\n|;
		$rtn .= qq|</object>\n|;
		return $rtn;
		}
	return qq|<embed src="$file" autostart="$start">|;
	}
##################
sub pageHref{
	#returns the page href - the published page if it exists, otherwise the _view page
	my $id=shift || return '#unknown';
	my %page=();
	if(isNum($id)){%page=getDBRecord(-table=>"_pages",_id=>$id);}
	else{%page=getDBRecord(-table=>"_pages",name=>$id);}
	return '#invalid' if !$page{_id};
	if(!length($page{publish})){return $cgiroot . '?' . $page{_id};}
	return $page{publish};
	}
##################
sub processActions{
	$ENV{SystemError}='';
	#abort(hashValues(\%input));
	if($input{_action}=~/^(del|delete)$/is){
		$XML{action}="delete";
		my $table=strip($input{_table}) || abort("Delete Error: No Table defined. [$input{_action}]");
		my $idstr=strip($input{_id}) || abort("Delete Error: No Record ID defined. [$input{_action}]");
		#format idstr into a comma separated list of values
		$idstr=~s/^\,+//sg;
		$idstr=~s/\,+$//sg;
		if(length($idstr)==0){abort("Delete Error: No Record ID defined. [$input{_action}]");}
		#Check for files associated with this record - delete it also
		my %filerec=getDBRecords(-table=>"_fielddata",tablename=>$table,inputtype=>"file");
		my @ids=split(/[\,\:\;\ ]+/,$idstr);
		foreach my $id (@ids){
			my %rec=getDBRecord(-table=>$table,_id=>$id);
			if(!$rec{_id}){abort("Record $id does not exist in $table");}
			my ($ok,$sql)=deleteDBData($table,"_id=$id");
			if(!isNum($ok)){abort("Error deleting record $id in $table [$ok]<br>\n$sql");}
			for(my $r=0;$r<$filerec{count};$r++){
				my $filefield=$filerec{$r}{fieldname};
				if($rec{$filefield}){
					my $afile="$docroot/$rec{$filefield}";
					$afile=~s/[\\\/]+/\//sg;
					if(-e $afile && !unlink($afile)){abort("Error Deleting $filefield file<br>\n$afile<br>\n$^E");}
                	}
            	}
        	}

		#delete the its
		my ($num,$sql)=deleteDBData($table,"_id in ($idstr)");
		if(!isNum($num)){abort("Delete Error:<br>\n$sql<br>\n$num");}
		$input{_delid}=$idstr;
		#@action=("Table",$table,"List Data");
		}
	elsif($input{_action}=~/^(new|add)$/is){
		$XML{action}="add";
		my $table=strip($input{_table}) || abort("Add Data Error: No Table defined. [$input{_action}]");
		#get table fields and field types
		my %Ftype=();
		my @fields=getDBFieldTypes(\%Ftype,$table);
		my $fieldcnt=@fields || abort("Add Data Error: $table has no fields. [$input{_action}]");
		#build a set of values to add
		my @sets=();
		foreach my $field (@fields){
			$field=lc($field);
			my $val='';
			next if $field=~/^\_/is;
			$val=strip($input{$field});
			next if length($val)==0;
			#Validate date formats
			if($Ftype{$field} && $Ftype{$field}=~/date$/is && $val=~/^([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{2,4})/s){
				$val=$3 . '-' . $1 . '-' . $2;
				}
			elsif($Ftype{$field} && $Ftype{$field}=~/datetime$/is && $val=~/^([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{2,4})$/s){
				$val=$3 . '-' . $1 . '-' . $2 . ' ' . getDate("MH:MM:SS");
				}
			push(@sets,$field=>$val);
			$XML{$field}=$val;
			#Blank out input value if remember me field is not checked
			my $rfield=$field . "_rm";
			if(length($input{$rfield})==0){$input{$field}='';}

			}
		#verify there are values to add
		#print "Sets:[@sets]\n";
		my $scnt=@sets || abort("Add Data Error: No values to add to $table" . hashValues(\%input));
		#add the record
		my ($num,$sql) = addDBData($table,@sets);

		#print "($num,$sql)\n";
		if(!isNum($num)){
			%XML=();
			abort("Add Data Error:<br>\n$num<br>\n$sql");
			}
		#set _newid
		$input{_newid}=$num;
		#set _newid_table
		$input{_newid_table}=$table;
		}
	elsif($input{_action}=~/^edit$/is){
		#Edit:tablename:ids
		$XML{action}="edit";
		my $table=strip($input{_table}) || abort("Edit Data Error: No Table defined. [$input{_action}]");
		my $idstr=strip($input{_id}) || abort("Edit Error: No Record ID defined. [$input{_action}]");
		#format idstr into a comma separated list of values
		$idstr=~s/[\,\:\ \;]+/\,/sg;
		$idstr=~s/^\,+//sg;
		$idstr=~s/\,+$//sg;
		if(length($idstr)==0){abort("Edit Error: No Record ID defined. [$input{_action}]");}
		#Get Fields
		#get table fields and field types
		my %Ftype=();
		my $cnt=getDBFieldTypes(\%Ftype,$table);
		my $fieldlist=strip($input{_fields}) || abort("Edit Error: No edit fields defined. [$input{_action}]");
		my @fields=split(/[,;:\ ]+/,$fieldlist);
		my $fieldcnt=@fields || abort("Edit Error: No edit fields found. [$input{_action}]");
		#get values to edit
		my @sets=();
		my $dbug='';
		my %efields=();
		foreach my $field (@fields){
			$field=lc(strip($field));
			next if length($field)==0;
			my $val='';
			next if $field=~/^\_/is;
			$val=strip($input{$field});
			if(length($input{"$field\_prev"}) && length($val)==0){
				if(isNum($input{"$field\_remove"}) && $input{"$field\_remove"}==1){}
				else{next;}
				}
			my $ftype=strip($Ftype{$field});
			$dbug .= qq|$field=[$ftype], sval=$val|;
			if($ftype && $ftype=~/date$/is && $val=~/^([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{2,4})/s){
				$val=$3 . '-' . $1 . '-' . $2;
				}
			elsif($ftype && $ftype=~/datetime$/is && $val=~/^([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{2,4})$/s){
				$val=$3 . '-' . $1 . '-' . $2 . ' ' . getDate("MH:MM:SS");
				}
			$dbug .= qq|, eval=$val<br>\n|;
			push(@sets,$field=>$val);
			#Check for size, type, width, and height
			my @extras=qw(size type width height);
			foreach my $extra (@extras){
				my $xfield="$field\_$extra";
				if(defined $Ftype{$xfield} && length($input{$xfield})){
					push(@sets,$xfield=>strip($input{$xfield}));
					$efields{$xfield}=1;
                	}
   				}
			$efields{$field}=1;
			$XML{$field}=$val;
			}
		#abort($dbug);
		#abort("@sets");
		#verify there are values to edit
		my $scnt=@sets || abort("Edit Data Error: No values to edit in $table. [$input{_action}]");
		#edit the record
		my $edate=getDate("YYYY-NM-ND MH:MM:SS");
		my ($num,$sql) = editDBData($table,"_id in ($idstr)",@sets);
		if(!isNum($num)){
			%XML=();
			abort("edit Data Error:<br>\n$num<br>\n$sql");
			}
		#publish?
		if(isNum($input{_publish}) && $input{_publish}==1 && $table=~/^\_pages$/is){
			#publish them.
			my %alist=();
			my $cnt = getDBData(\%alist,"select _id,publish from _pages where not(publish is null) and _id in ($idstr)","nocount=1");
			if(isNum($cnt) && $cnt>0){
				#publish the records
				for(my $p=0;$p<$cnt;$p++){
					my $file=strip($alist{$p}{publish});
					my $pfile=&publishData($table,$alist{$p}{_id},$file);
					}
        		}
  			}
  		#set edit fields
  		my @editfields=keys(%efields);
		$input{_edit_fields}=join(',',@editfields);
		#set _newid
		$input{_editid}=$idstr;
		#set _newid_table
		$input{_editid_table}=$table;
		}
	elsif($input{_action}=~/^publish$/is && $input{_table}=~/^_pages$/is && isNum($input{_id})){
		$XML{action}="publish";
		my %alist=();
		my $cnt = getDBData(\%alist,"select _id,publish from _pages where not(publish is null) and _id=$input{_id}","nocount=1");
		if($cnt==1){
			#publish the records
			my $file=strip($alist{0}{publish});
			my $pfile=&publishData("_pages",$alist{0}{_id},$file);
			if(-e $pfile){
				$XML{files} .= $pfile;
				my $pdate=getDate("YYYY-NM-ND MH:MM:SS");
				my $ck=editDBData("_pages","_id=$alist{0}{_id}",_pdate=>$pdate);
				$input{publish_msg}= "Published $file to $pfile<br>Updated publish date on record $alist{0}{_id}<br>\n";
				$XML{status}="success";
				}
			else{
				$input{publish_msg}= "Unable to Publish $file\. $pfile<br>";
				$XML{status}="failure";
				}
			}
		elsif($cnt==0){
			$input{publish_msg}= "Unable to Publish - No publish name given to record $alist{0}{_id} in _pages table.<br>";
			$XML{status}="failure";
			}
		else{
			$input{publish_msg}= "Publish Error: $cnt<br>";
			$XML{status}="failure";
			}
		}
	############
	#Check for mulitpart form data
	if($input{_enctype}=~/^multipart\/form\-data$/is){
		#open(EF,">$progpath/enctype.log") || return;
		#process file uploaded
		foreach my $inp (keys(%input)){
			my $path=$input{"ipath\_$inp"};
			my $ufile=$input{$inp};
			#print EF qq|$inp=[$ufile] and path=[$path] and docroot=$ENV{DOCUMENT_ROOT}\n|;
			if(-e $ufile && length($path) && length($ENV{DOCUMENT_ROOT})){
				#print EF "\tFOUND ONE $inp  == $input{$inp}\n";
				my $file=$input{_file};
				$file=~s/^\Q$progpath\E//s;
				$file=~s/^\/+//s;
				$file=~s/\/+/\//sg;
				my $perm="744";
				my $cp=chmod(oct("0$perm"),$file);
				my $path=$input{"ipath\_$inp"};
				my $newfile="$ENV{DOCUMENT_ROOT}/$path/$file";
				$newfile=~s/\/+/\//s;
				my $nfe=0;
				if(-e $newfile){$nfe=1;}
				#print EF "copyFile($input{$inp},$newfile,1)\n";
				$cp=copyFile($input{$inp},$newfile,1);
				unlink($input{$inp});
				}
			}
		#close(EF);
		}
	}
#####################
sub conf2Hash{
	my $hash=shift || return "No hash";
	my $file=shift || return "No File";
	%{$hash}=();
	return "$file does not exist" if ! -e $file;
	open(CF,$file) || return "Unable to open $file";
	my @lines=<CF>;
	close(CF);
	my $configstr;
	foreach my $line (@lines){
		$line=strip($line);
		$configstr .= $line . "\n";
		}
	#process perl logic in config file
	$configstr=evalPerl($configstr);
	while ($configstr=~m/\<host\ (.+?)\>(.+?)\<\/host\>/sig){
		my $chost=lc(strip($1));
		my $str=$2;
		my @lines=split(/[\r\n]+/,$str);
		foreach my $line (@lines){
			$line=strip($line);
			next if length($line)==0;
			next if $line=~/^\#/s;
			my ($key,$val)=split(/[\s\t\=]+/,$line,2);
			$key=lc(strip($key));
			$val=strip($val);
			next if length($key)==0;
			next if length($val)==0;
			$hash->{$chost}{$key}=$val;
			#print "hash->{$chost}{$key}=$hash->{$chost}{$key}\n";
			}
		if(!defined $hash->{$chost}{dbhost}){$hash->{$chost}{dbhost}="localhost";}
		}
	return 1;
	}
###########
sub getMeta{
	#parse the META hash and only return requested info.
	my $Meta=shift;
	my $tablename=lc(shift) || return;
	my $fieldname=lc(shift) || return;
	%{$Meta}=();
	$Meta->{tablename}=$tablename;
	$Meta->{fieldname}=$fieldname;
	my @fields=@{$META->{fields}};
	my $cnt=$Meta->{count};
	for(my $x=0;$x<$cnt;$x++){
		if($META->{$x}{tablename}=~/^$tablename$/is && $META->{$x}{fieldname}=~/^$fieldname$/is){
			foreach my $field (@fields){
				next if $field=~/^(tablename|fieldname)$/is;
				$Meta->{$field}=$META->{$x}{$field};
				}
			return 1;
			}
		}
	return 0;
	}
###########
sub getConfig{
	#usage: our %Config=getConfig($file[,$host]);
	my $host=shift || $ENV{HTTP_HOST};
	$host=lc(strip($host));
	my %Config=();
	if(-s "$progpath/config.xml"){
		my $data=getFileContents("$progpath/config.xml");
		$data=evalPerl($data);
		my %xml=readXML($data,'hosts');
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
            foreach my $skey (keys(%{$xml{$key}})){
				my $ckey=lc(strip($skey));
				$ConfigXml{$cname}{$ckey}=strip($xml{$key}{$skey});
	            }
	       }
		my @envs=qw(HTTP_HOST UNIQUE_HOST SERVER_NAME);
		foreach my $envname (@envs){
			my $env=strip(lc($ENV{$envname}));
			if(length($ConfigXml{$env}{name})){
				foreach my $skey (keys(%allhosts)){
					$Config{$skey}=strip($allhosts{$skey});
	            	}
                foreach my $skey (keys(%{$ConfigXml{$env}})){
					$Config{$skey}=strip($ConfigXml{$env}{$skey});
	            	}
	            $ENV{'WaSQL_HOST'}=$envname;
	            $ENV{'WaSQL_DBNAME'}=$Config{dbname};
	            last;
            	}
			}
		if(!defined $Config{name}){
			$Config{err}="getConfig Error: No configuration defined for $host<hr>\n" . hashValues(\%ConfigXml) ."<hr>\n" . hashValues(\%ENV);
			return %Config;
			}
		return %Config;
    	}
    $Config{err}="No config.xml";
	}
sub stripHost{
	my $str=shift;
	$str=~s/[^A-Z]//isg;
	return uc($str);
	}
###########
sub viewInTemplate{
	#usage: my $html=viewInTemplate($id,body=>$body);
	#info: returns the html in the template specified
	my $id=shift || return;
	my %params=@_;
	foreach my $param (keys(%params)){$input{$param}=$params{$param};}
	#get Template
	my %list=();
	my $sql="select * from _templates where _id=$id";
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return "viewInTemplate Error retrieving template: $cnt";}
	if($cnt==0){return "viewInTemplate Error - no template found with id of $id"}
	my $html=$list{0}{body};
	$html=evalPerl($html);
	$html=processTags($html,\%list);
	#Replace @self(field) with the page's field value
	while ($html=~m/\@self\((.+?)\)/sig){
		my $tag=$&;
		my $field=lc(strip($1));
		$html=~s/\Q$tag\E/$params{$field}/is;
		}
	#Check for old @body tag in template
	$html=~s/\@body/$params{body}/is;
	$html=evalPerl($html);
	}
###########
sub getPageTemplateId{
	#usage: my $template_id=getPageTemplate($id);
	#info: returns the html in the template specified
	my $id=shift || return;
	#get Template
	my %list=();
	my $sql="select template from _pages where _id=$id";
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return "getPageTemplateId Error retrieving template id: $cnt";}
	if($cnt==0){return 0;}
	return $list{0}{template};
	}
###########
sub viewData{
	#usage: my $pageview=viewData($tablename,$criteria);  or my $pageview=viewData("_pages","_id=3");
	#info: captures the first record based on the given criteria, processes any perl tags, etc and returns the page.
	my $tablename=lc(shift) || return "No Table in viewData";
	my $criteria=shift || return "No Criteria";
	my %params=@_;
	foreach my $key (keys(%params)){$input{$key}=$params{$key};}
	my %vlist=();
	$sql="select * from $tablename where $criteria";
	my $cnt=getDBData(\%vlist,$sql,"nocount=1");
	if($cnt==0){return abort("Sub: viewData","Error: No records found",$sql);}
	elsif($cnt > 1){return abort("$sql has more than one record");}
	#Edit the _adate field
	my $adate=getDate("YYYY-NM-ND MH:MM:SS");
	my $ck=editDBData($tablename,"_id=$vlist{0}{_id}",_adate=>$adate);
	#Set %PAGE hash
	%PAGE=();
	my @flds=@{$vlist{fields}};
	foreach my $fld (@flds){
		$PAGE{$fld}=$vlist{0}{$fld};
		if($PAGE{$fld}=~m/\@self\($fld\)/is){
			my $recurse=encodeHtml("\@self\($fld\)");
			abort("Recursive ViewData Error<br>$recurse found in page data.<br>Attempt to view Pages where $criteria");
   			}
   		if($PAGE{$fld}=~m/\$PAGE\{$fld\}/is){
			my $recurse=encodeHtml("\$PAGE\{$fld\}");
			abort("Recursive ViewData Error<br>$recurse found in page data.<br>Attempt to view Pages where $criteria");
   			}
		}
	#$WaSQL{MidViewData} = times();
	#Evaluate Perl tags
	my $ViewData='';
	#Check for Template
	my $template=$input{_template} || $vlist{0}{template};
	if(length($template)){
		if(isNum($template)){
		#retrieve template from templates table
			my %tlist=();
			my $tsql="select * from _templates where _id=$template";
			my $tcnt=getDBData(\%tlist,$tsql,"nocount=1");
			if($tcnt==1){
				my $TemplateBody=$tlist{0}{body};
				$TemplateBody=evalPerl($TemplateBody);
				$TemplateBody=processTags($TemplateBody,\%tlist);
				#Replace @self(field) with the page's field value
				while ($TemplateBody=~m/\@self\((.+?)\)/sig){
					my $tag=$&;
					my $field=lc(strip($1));
					$TemplateBody=~s/\Q$tag\E/$PAGE{$field}/is;
					}
				#Check for old @body tag in template
				$TemplateBody=~s/\@body/$PAGE{body}/is;
				#Assign TemplateBody to ViewData
				$ViewData=$TemplateBody;
				}
			else{$ViewData=$vlist{0}{body};}
			}
		elsif(isUrl($template)){
			my ($head,$body,$code)=getURL($template);
			if($code==200 && length($body)){
				my $TemplateBody=$body;
				$TemplateBody=evalPerl($TemplateBody);
				$TemplateBody=processTags($TemplateBody,\%tlist);
				#Replace @self(field) with the page's field value
				while ($TemplateBody=~m/\<\!\-\-WaSQL\((.+?)\)\-\-\>/sig){
					my $tag=$&;
					my $field=lc(strip($1));
					$TemplateBody=~s/\Q$tag\E/$PAGE{$field}/is;
					}
				#Check for  <!--WaSQL--> tag in template page and replace with page body
				$TemplateBody=~s/\<\!\-\-WaSQL\-\-\>/$PAGE{body}/is;
				#Assign TemplateBody to ViewData
				$ViewData=$TemplateBody;
            	}
            else{$ViewData="$code Page Template Error [$template]";}
        	}
		else{$ViewData=$vlist{0}{body};}
		}
	else{$ViewData=$vlist{0}{body};}
	$ViewData=evalPerl($ViewData);
	$ViewData=processTags($ViewData,\%vlist);
	return $ViewData;
	}
###########
sub viewFile{
	#usage: my $pageview=viewData($tablename,$criteria);  or my $pageview=viewData("_pages","_id=3");
	#info: captures the first record based on the given criteria, processes any perl tags, etc and returns the page.
	my $file = shift || return "No file to view in viewFile";
	my %params=@_;
	if(!open(FH,$file)){return "Unable to open $file in viewFile";}
	my @lines=<FH>;
	close(FH);
	my $ViewData=join('',@lines);
	if(isNum($params{_template})){
		#Check for Template
		my %tlist=();
		my $tsql="select * from _templates where _id=$params{_template}";
		my $tcnt=getDBData(\%tlist,$tsql,"nocount=1");
		#abort("[$tcnt] $tsql");
		if($tcnt==1){
			%PAGE=();
			my $TemplateBody=$tlist{0}{body};
			$TemplateBody=evalPerl($TemplateBody);
			#abort("going in");
			$TemplateBody=processTags($TemplateBody,'',self=>0);
			#Replace @self(body) with the file content <body>...</body>
			#abort($TemplateBody);
			while ($TemplateBody=~m/\@self\((.+?)\)/sig){
				my $tag=$&;
				my $field=lc(strip($1));
                    $ViewData=~/\<$field(.*?)\>(.+?)<\/$1\>/is;
                    $PAGE{$field}=$2;
                    #abort($field,$PAGE{$field}) if $field=~/body/is;
				$TemplateBody=~s/\Q$tag\E/$PAGE{$field}/is;
				}
			#Check for old @body tag in template
			$TemplateBody=~s/\@body/$PAGE{body}/is;
			#Assign TemplateBody to ViewData
			$ViewData=$TemplateBody;
			}
		}
	$ViewData=evalPerl($ViewData);
	$ViewData=processTags($ViewData);
	return $ViewData;
	}
###########
sub processTags{
	my $str=shift || return;
	my $recHash=shift;
	my %params=@_;
	#abort("HERE: $params{self}");
	#Template tags
	if($str=~/\<template id\=/is){
		my %template=();
		my $sql="select _id,name,body from _templates";
          my $ccnt=getDBData(\%template,$sql,"nocount=1;");
          while ($str=~m/\<template id\=\"(.+?)\"\>(.+?)\<\/template\>/sig){
			my $tag=$&;
			my $id=$1;
			my $tagbody=$2;
			$tagbody=evalPerl($tagbody);
			$tagbody=processTags($tagbody);
			my $val='';
			#See if we can find a template with that id
			my $index=0;
			for(my $x=0;$x<$ccnt;$x++){
				if($id=~/^[0-9]+$/is){
					if($template{$x}{_id}==$id){$index=$x;last;}
					}
			 	elsif($template{$x}{name}=~/^\Q$id\E$/is){$index=$x;last;}
			 	}
			if($index){
				$val=$template{$index}{body};
				$val=evalPerl($val);
				$val=processTags($val);
				#Extract data out of tagbody
				my %tagdata=();
				while($tagbody=~m/\<(.+?)>(.+?)\<\/\1\>/sig){
					my $field=lc(strip($1));
					my $data=strip($2);
					$tagdata{$field}=$data;
					}
				#Replace any tags found in val with tagdata
				while($val=~m/\<\$(.+?)\>/sig){
					my $xtag=$&;
					my $xstr=$1;
					my ($key,$default)=split(/\,/,$xstr,2);
					$key=lc(strip($key));
					$default=strip($default);
					my $data=strip($tagdata{$key});
					my $xval=length($data)?$data:$default;
					$val=~s/\Q$xtag\E/$xval/is;
					}
				}
			$str=~s/\Q$tag\E/$val/is;
			}
		}
	# $input{name}
	while ($str=~m/\$input\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val;
		if(length($key)){$val .= $input{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# @input(name)
	while ($str=~m/\@input\((.+?)\)/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val;
		if(length($key)){$val .= $input{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $user{field}
	while ($str=~m/\$user\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val;
		if(length($key)){$val .= $USER{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $ENV{HTTP_HOST}
	while ($str=~m/\$ENV\{(.+?)\}/sig){
		my $tag=$&;
		my $key=uc(strip($1));
		my $val;
		if(length($key)){$val .= $ENV{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# @env(HTTP_HOST)
	while ($str=~m/\@env\((.+?)\)/sig){
		my $tag=$&;
		my $key=uc(strip($1));
		my $val;
		if(length($key)){$val .= $ENV{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $PAGE{_id}
	while ($str=~m/\$PAGE\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val;
		if(length($key)){$val .= $PAGE{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# @PAGE(name)
	while ($str=~m/\@PAGE\((.+?)\)/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val;
		if(length($key)){$val .= $PAGE{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $PerlID{name}
	while ($str=~m/\$PerlID\{(.+?)\}/sg){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val='';
		if(length($key)){$val .= $PerlID{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}


	#########################################
	### in1 Compatibility
	# @Char(nbsp,4)
	while ($str=~m/\@char\((.+?)\,*([0-9]*)\)/sig){
		my $tag=$&;
		my $char=$1;
		my $cnt=$2 || 1;
		my $val;
		if(length($char)){
			if($char=~/^\#/s){$val .= "\&\#$cnt\;";}
			else{$val .= "\&$char\;"x$cnt;}
			}
		$str=~s/\Q$tag\E/$val/is;
		}

	#@url(url)
	while ($str=~m/\@url\((.+?)\)/sig){
		my $tag=$&;
		my $url=strip($1);
		my $val;
		if($url!~/^http/is){$url =qq|http://$url|;}
		if(length($url)){
			my($head,$body,$code)=getURL($url);
			if($code==200){$val=strip($body);}
			elsif($code==404){$val=qq|<!-- \@url_error - Page not found - $url -->|;}
			else{$val=qq|<!-- \@url_error - code=$code - $url -->|;}
			}
		$str=~s/\Q$tag\E/$val/is;
		}
	#@include(id)
	while ($str=~m/\@include\((.+?)\)/sig){
		my $tag=$&;
		my $id=strip($1);
		my $val='';
		if(isNum($id)){
			$val=&viewData("_pages","\_id\=$id");
			}
		else{$val=&viewData("_pages","name like '$id'");}
		$str=~s/\Q$tag\E/$val/is;
		}
	#@self(field)
	my $self=$params{self};
	if(!length($self) || $self != 0){
		#abort("Get out of here! [$self]");
		while ($str=~m/\@self\((.+?)\)/sig){
			my $tag=$&;
			my $field=lc(strip($1));
			my $val=$PAGE{$field};
			$str=~s/\Q$tag\E/$val/is;
			}
		}
	#SQLtag
	while ($str=~m/\<sqltag(.+?)\>/sig){
		my $stag=$&;
		my $params=$1;
		my %SQLTag=();
		my $sqlval;
		my @atts=getDBFields(_fielddata,1);
		push(@atts,"value","blank","style","class","showname","formname","onclick");
		foreach my $att (@atts){
			if($params=~/\ $att\ *=\ *"(.*?)"/is){
				$SQLTag{$att}=strip($1);
				}
			elsif($params=~/\ $att\ *=\ *(\S+)/is){
				$SQLTag{$att}=strip($1);
				}
			}
		my $table=$SQLTag{tablename};
		my $field=$SQLTag{fieldname};
		if($table && $field){
			my %slist=();
			my %tag=();
			my $sql=qq|select * from _fielddata where tablename like '$table' and fieldname like '$field'|;
			my $ck=getDBData(\%slist,$sql,"nocount=1;limit=1");
			#$sqlval .= qq|[$ck] $sql<br>\n|;
			if(!isNum($ck)){return $ck;}
			foreach my $field (@{$slist{fields}}){
				$tag{$field}=$slist{0}{$field};
            	}
			#$ck=&buildHash(\%slist,\%tag,0);
			if(length($tag{fieldname})==0){
				$tag{fieldname}=$field;
				}
			$tag{fieldname} ||= "text";
			foreach my $att (@atts){
				next if $att=~/^(value|blank)$/is;
				if(length($SQLTag{$att})){$tag{$att}=$SQLTag{$att};}
				}
	
			if(length($SQLTag{showname})){$tag{showname}=$SQLTag{showname};}
			if(length($SQLTag{blank})){$tag{blank}=$SQLTag{blank};}
			if(length($SQLTag{style})){$tag{style}=$SQLTag{style};}
			if(length($SQLTag{class})){$tag{class}=$SQLTag{class};}
			if(length($SQLTag{onclick})){$tag{class}=$SQLTag{onclick};}
			my $val;
			if(length($SQLTag{value})){$val=$SQLTag{value};}
			elsif(length($input{$field})){$val=$input{$field};}
			elsif(length($tag{defaultval})){$val=$tag{defaultval};}
			$tag{formname} ||= "addedit";
			if($SQLTag{formname}){$tag{formname} = $SQLTag{formname};}

			if(length($tag{mask}) && $tag{mask}=~/^Calendar$/is){$tag{inputtype}="date";}
			#$sqlval .= hashValues(\%tag);
			$sqlval .= &buildTag(\%tag,$val);
			}
		$str=~s/\Q$stag\E/$sqlval/is;
		}
# 	#<wiki></wiki>
# 	while($str=~m/\<wiki\>(.*?)\<\/wiki\>/sig){
# 		my $tag=$&;
# 		my $wiki=wiki2html($1);
# 		$str=~s/\Q$tag\E/$wiki/is;
# 		}
	return $str;
	}
##############
sub postEditList{
	my %list=();
	my $sql=qq|select _id,name,publish,body from _pages order by _id|;
	my $cnt=getDBData(\%list,$sql,"nocount=1;");
	if(!isNum($cnt)){return "pageList2Xml Error: $cnt";}
	my $rtn=qq|<?xml version="1.0" encoding="ISO-8859-1" ?>\n|;
	#Page id
	$rtn .= qq|<pid>\n|;
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{name};
		my $val=$list{$x}{_id};
		$rtn .= qq|	<$name>$val</$name>\n|;
    	}
    $rtn .= qq|</pid>\n|;
    #Publish
    $rtn .= qq|<publish>\n|;
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{name};
		my $val=$list{$x}{publish};
		$rtn .= qq|	<$name>$val</$name>\n|;
    	}
    $rtn .= qq|</publish>\n|;
    #Page Body
    $rtn .= qq|<pbody>\n|;
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{name};
		my $val=$list{$x}{body};
		if($val=~/[\<\>]/){$val = qq|<![CDATA[$val]]>\n|;}
		$rtn .= qq|	<$name>$val</$name>\n|;
    	}
    $rtn .= qq|</pbody>\n|;
    %list=();
	$sql=qq|select _id,name,body from _templates order by _id|;
	$cnt=getDBData(\%list,$sql,"nocount=1;");
	if(!isNum($cnt)){return "pageList2Xml Error: $cnt";}
	$rtn .= qq|<tid>\n|;
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{name};
		my $val=$list{$x}{_id};
		$rtn .= qq|	<$name>$val</$name>\n|;
    	}
    $rtn .= qq|</tid>\n|;
    $rtn .= qq|<tbody>\n|;
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{name};
		my $val=$list{$x}{body};
		if($val=~/[\<\>]/){$val = qq|<![CDATA[$val]]>\n|;}
		$rtn .= qq|	<$name>$val</$name>\n|;
    	}
    $rtn .= qq|</tbody>\n|;
	}
##############
sub postEditXml{
	my %list=getDBRecords(-table=>"_pages");
	my $rtn=qq|<?xml version="1.0" encoding="ISO-8859-1" ?>\n|;
	my %Ftype=();
	my @fields=getDBFieldTypes(\%Ftype,"_pages");
	$rtn .= qq|<xmlroot>\n|;
	#$rtn .= qq|	<pages>\n|;
	for(my $x=0;$x<$list{count};$x++){
		$rtn .= qq|		<WASQL\_page _id="$list{$x}{_id}"|;
		my @xmlfields=();
		foreach my $field (@fields){
			next if $field=~/^\_/is;
			my $val=$list{$x}{$field};
			next if !length($val);
			if($Ftype{$field}=~/^(varchar|int)/is){ $rtn .= qq| $field="$val"|;}
			elsif($Ftype{$field}=~/^text$/is){push(@xmlfields,$field);}
        	}
		$xmlfieldstr=join(",",@xmlfields);
		$rtn .= qq| _xmlfields="$xmlfieldstr"|;
        $rtn .= qq|>\n|;
        foreach my $field (@fields){
			next if $field=~/^\_/is;
			if($Ftype{$field}=~/^text$/is){
				my $val=$list{$x}{$field};
				#next if !length($val);
				if($val=~/[\<\>]/){$val = qq|<![CDATA[$val]]>\n|;}
				$rtn .= qq|			<PAGE\_$field>\n|;
				$rtn .= $val;
				$rtn .= qq|			</PAGE\_$field>\n|;
				}
        	}
        $rtn .= qq|		</WASQL\_page>\n|;
    	}
    #$rtn .= qq|	</pages>\n|;
    %list=getDBRecords(-table=>"_templates");
    %Ftype=();
	my @fields=getDBFieldTypes(\%Ftype,"_templates");
	#$rtn .= qq|	<templates>\n|;
	for(my $x=0;$x<$list{count};$x++){
		my @xmlfields=();
		$rtn .= qq|		<WASQL\_template _id="$list{$x}{_id}"|;
		foreach my $field (@fields){
			next if $field=~/^\_/is;
			my $val=$list{$x}{$field};
			next if !length($val);
			if($Ftype{$field}=~/^(varchar|int)/is){ $rtn .= qq| $field="$val"|;}
			elsif($Ftype{$field}=~/^text$/is){push(@xmlfields,$field);}
        	}
        $xmlfieldstr=join(",",@xmlfields);
		$rtn .= qq| _xmlfields="$xmlfieldstr"|;
        $rtn .= qq|>\n|;
        foreach my $field (@fields){
			next if $field=~/^\_/is;
			if($Ftype{$field}=~/^text$/is){
				my $val=$list{$x}{$field};
				next if !length($val);
				if($val=~/[\<\>]/){$val = qq|<![CDATA[$val]]>\n|;}
				$rtn .= qq|			<TEMPLATE\_$field>\n|;
				$rtn .= $val;
				$rtn .= qq|			</TEMPLATE\_$field>\n|;
				}
        	}
        $rtn .= qq|		</WASQL\_template>\n|;
    	}
    #$rtn .= qq|	</templates>\n|;
    $rtn .= qq|</xmlroot>\n|;
    return $rtn;
	}
##############
sub buildTag{
	#usage: $html .= &buildTag(\%tag,$val);
	#info: builds the html tag passed in the tag hash: %tag=(tablename=>"test",fieldname=>"name",...);
	my $Meta=shift;
	my $val=shift;
	my $remember=shift;
	my $formname=shift || $Meta->{formname} || "addedit";
	my $id=$formname . "_" . $Meta->{fieldname};
	my $container=$id . "_container";
	my $tag=qq|<span id="$container" inputtype="$Meta->{inputtype}">|;
	my $oval=$val;
	if($Meta->{inputtype}!~/^textarea$/is){$val=processString($val);}

	my ($width,$height,$max);
	my $help=$Meta->{help};
	$help=~s/[\r\n]+/\ /sg;
	$help=~s/[\;\:]+$//sg;
	$help=~s/^[\;\:]+//sg;
	$help=encodeHtml($help);
	my $title=$Meta->{fieldname};
	if(length($help)){$title .=".  -- $help";}
	#Get Codes from Meta->{codes}
	my %Codes=();
	if($Meta->{codes}){
		my @pairs=split(/[\r\n\&]+/,$Meta->{codes});
		foreach my $pair (@pairs){
			my ($k,$v)=split(/\=/,$pair,2);
			$k=lc(strip($k));
			$v=strip($v);
			next if length($k)==0;
			next if length($v)==0;
			$Codes{$k}=$v;
			}
		}
	#Input type determines what this html field is
	#TextArea
	if($Meta->{inputtype}=~/^textarea$/is){
		#textarea
		$width=$Meta->{width} || 600;
		$height=$Meta->{height} || 50;
		if($width !~/\%/s){$width .= "px";}
		if($height !~/\%/s){$height .= "px";}
		if($Meta->{behavior}=~/(html)/is || $Meta->{tvals}=~/html/is){
			#HTML toolbar
            $tag .= qq|<table  cellspacing="0" cellpadding="2" border="0" style="height:18px;"><tr>\n|;
            #$tag .= qq|<td><select style="font-size:8pt;font-family:arial;" onChange="showToolbar('$Meta->{fieldname}',this.value);"><option value="html">HTML</option><option value="wasql" selected>WaSQL</option></select></td>\n|;
            $tag .= qq|<td>|;
            $checkperl=1;
            $ENV{_perlcheck}=1;
            #HTML toolbar
 			$tag .= qq|<div style="height:16px;margin-bottom:2px;position:relative;" id="w_toolbar" field="$Meta->{fieldname}" category="html">\n|;
 			#preview
			$tag .= qq|	<div style="position:absolute;display:none;background:#FFF;border:2px outset #000" id="$Meta->{fieldname}\_preview_window">\n|;
			$tag .= qq|		<div align="right" style="padding-right:2px;padding-left:5px;background:#6699CC;border-bottom:1px solid #000;font-size:9pt;color:#FFF;"><b>Preview Window</b> <span style="color:#6699CC">. . . . . . . . . . . . . . . . . . . . .</span> <a href="#" onClick="setStyle('$Meta->{fieldname}\_preview_window','display','none');return false;" style="color:red;font-weight:bold;text-decoration:none;">X</a></div>\n|;
			$tag .= qq|		<div align="left" id="$Meta->{fieldname}\_preview" style="padding:3px;height:350px;overflow:auto;"></div>\n|;
			$tag .= qq|	</div>\n|;
			$tag .= qq|<table  cellspacing="0" cellpadding="2" border="0" style="margin-bottom:2px;"><tr>\n|;
			#toolbar buttons - disable the tabindex by setting it to a negative number
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick='surroundText("\t","", document\.$formname\.$Meta->{fieldname});' title="Tab">tab</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<b>','</b>', document\.$formname\.$Meta->{fieldname});" title="Bold"><b>B</b></a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<i>','</i>',document\.$formname\.$Meta->{fieldname});" title="Italic">I</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<center>','</center>', document\.$formname\.$Meta->{fieldname});" title="Center">center</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<p>','</p>',document\.$formname\.$Meta->{fieldname});" title="Paragraph tags">p</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<div>','</div>',document\.$formname\.$Meta->{fieldname});" title="Div">div</a></td>\n|;
 			#$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick='surroundText("<link href=\\"","\\" rel=\\"stylesheet\\" type=\\"text/css\\">",document\.$formname\.$Meta->{fieldname});' title="CSS Link">CSS</a></td>\n|;
 			#$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick='surroundText("<script language=\\"javascript\\">","</script>",document\.$formname\.$Meta->{fieldname});' title="Javascript tag">Js</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick='surroundText("<table cellspacing=\\"0\\" cellpadding=\\"0\\" border=\\"0\\">","</table>", document\.$formname\.$Meta->{fieldname});' title="Table">Table</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<tr>','</tr>',document\.$formname\.$Meta->{fieldname});" title="Table TR tag">tr</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="surroundText('<td>','</td>',document\.$formname\.$Meta->{fieldname});" title="Table TD tag">td</a></td>\n|;
 			$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="setStyle('$Meta->{fieldname}\_preview_window','display','block');setText('$Meta->{fieldname}\_preview',document\.$formname\.$Meta->{fieldname}.value);" title="Bold">Preview</a></td>\n|;
 			if($Meta->{tablename} =~ /^\_(pages)$/is){
 				$tag .= qq|	<td><a tabindex="-1" href="#" class="w_button" onClick="checkPerl('$Meta->{fieldname}',document\.$formname\.$Meta->{fieldname}.value);" title="Check Perl syntax">Perl</a></td><td id="$Meta->{fieldname}\_perlcheck"></td>\n|;
 				}
 			$tag .= qq|	</tr></table></div>\n|;
			#wasql toolbar
			$tag .= qq|<div style="height:1px;display:none;">\n|;
            $tag .= qq|<img src="/wfiles/busy.gif" border="0">\n|;
            $tag .= qq|<img src="/wfiles/success.gif" border="0">\n|;
            $tag .= qq|<img src="/wfiles/success.gif" border="0">\n|;
            $tag .= qq|<img src="/wfiles/failed.gif" border="0">\n|;
            $tag .= qq|</div>\n|;
			#
			$tag .= qq|</div>\n|;
			$tag .= qq|</td></tr></table>\n|;
			$tag .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="initBehaviors();">\n|;
			$tag .= qq|<input type="hidden" name="$Meta->{fieldname}\_checkperl" value="1">\n|;
	  		}
 		$val=encodeHtml($val);
		$tag .= qq|<textarea |;
		if($Meta->{behavior}=~/(html)/is || $Meta->{tvals}=~/html/is){
			$tag .= qq| onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);"|;
			}
		if($Meta->{displayname}){$tag .= qq| displayname="$Meta->{displayname}"|;}
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}" |;}
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{maskmsg}){$tag .= qq| maskmsg="$Meta->{maskmsg}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{disabled}){$tag .= qq| disabled|;}
		if($Meta->{readonly}){$tag .= qq| readonly|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		if(length($help)){$tag .= qq| onFocus="window.status='$help';"|;}
		my $wrap="off";
		if($Meta->{fieldname}=~/^body$/is){$wrap="off";}
		if($Meta->{tvals}=~/softwrap/is){$wrap="soft";}
		if(defined $Meta->{wrap}){$wrap=$Meta->{wrap};}
		$tag .= qq| title="$title" wrap="$wrap" id="$id" name="$Meta->{fieldname}" style="width:$width\;height:$height\;|;
		if(length($Meta->{style})){$tag .= $Meta->{style};}
		$tag .= qq|">$val</textarea>|;
		#store the wrap type incase we need it to process properly
		$tag .= qq|<input type="hidden" name="$Meta->{fieldname}\_wrap" value="$wrap">\n|;
		#Make it draggable  - need to fix the problem when there are more than one textarea on the page.
		#For now just hack it to work if tvals has drag as the value
		if((length($Meta->{tvals}) && $Meta->{tvals}=~/drag/is) || (length($Meta->{behavior}) && $Meta->{behavior}=~/(drag|resize)/is)){
			$tag .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="addDragToTextarea('$id')">\n|;
			}
		}

	#Select
	elsif($Meta->{inputtype}=~/^select$/is){
		$width=$Meta->{width}?$Meta->{width}."px":"auto";
		#select
		$Meta->{tvals}=evalPerl($Meta->{tvals});
		$Meta->{dvals}=evalPerl($Meta->{dvals});
		my @tvals=buildVals($Meta->{tvals});
		my @dvals=buildVals($Meta->{dvals});
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		#
		$tag .= qq|<select |;
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{displayname}){$tag .= qq| displayname="$Meta->{displayname}"|;}
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		else{$tag .= qq| style="width:$width\;"|;}
		if($Meta->{maskmsg}){$tag .= qq| maskmsg="$Meta->{maskmsg}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{disabled}){$tag .= qq| disabled|;}
		if($Meta->{readonly}){$tag .= qq| readonly|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if(length($Meta->{tabindex})){$tag .= qq| tabindex="$Meta->{tabindex}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		$tag .= qq| title="$title" name="$Meta->{fieldname}" onFocus="window.status='$help';">|;
		#show blank option?
		my $noblank=0;
		#If a required field does not have a default value, then show a blank
		if(length($Meta->{required}) && $Meta->{required}==1 && (length($Meta->{defaultval}) || length($val))){$noblank++;}
		if(length($Meta->{noblank})){$noblank++;}

		if($noblank==0){$tag .= qq|<option value="">$Meta->{blank}</option>|;}
		my $cnt=@tvals;
		for(my $x=0;$x<$cnt;$x++){
			my $dv=$dvals[$x];
			if(length($dv)==0){$dv=$tvals[$x];}
			$tag .= qq|<option value="$tvals[$x]"|;
			if($tvals[$x]=~/^\Q$val\E$/is || $dv=~/^\Q$val\E$/is){$tag .= qq| selected|;}
			$tag .= qq|>$dv</option>|;
			}
		$tag .= qq|</select>|;
		}
	#ComboBox
	elsif($Meta->{inputtype}=~/^combo$/is){
		#Combo Box
		$width=$Meta->{width} || 125;
		my $cwidth = int($width-15) . "px";
		my $dwidth = int($width-4) . "px";
		$width .= "px";
		my $style=$Meta->{style};
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		$Meta->{tvals}=evalPerl($Meta->{tvals});
		$Meta->{dvals}=evalPerl($Meta->{dvals});
		my @tvals=buildVals($Meta->{tvals});
		my @dvals=buildVals($Meta->{dvals});
		my $comboname="$Meta->{fieldname}\_combobox";
		$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #7F9DB9;width:$width;"><tr valign="bottom">|;
		$tag .= qq|<td nowrap="true">|;
		$tag .= qq|<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr valign="bottom"><td>|;
		$tag .= qq|<input |;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		$tag .= qq| type="text"|;
		$tag .= qq| onFocus="window.status='$help';"|;
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		$tag .= qq| title="$title" name="$Meta->{fieldname}" value="$val" style="width:$cwidth;border:0px;">|;
		#Build combo div with options
		$tag .= qq|</td><td align="right" valign="top"><img src="/wfiles/dropdown.png" width="13" height="14" border="0" onClick="showDrop('$comboname');return false;" style="cursor:pointer;border:1px solid #CAD8F9;" onMouseOver="this.style.border='1px solid #90ABE0';" onMouseOut="this.style.border='1px solid #CAD8F9'"></td>|;
          $tag .= qq|</tr></table>|;
		$tag .= qq|</td></tr>|;
		$tag .= qq|<tr valign="bottom"><td><div class="w_drop" id="$comboname" _behavior="dropdown" style="width:$dwidth\;|;
		if($Meta->{height}){
               $tag .= "height:" . $Meta->{height} . "px;overflow:auto\;";
	          }
		$tag .= qq|">|;
		my $cnt=@tvals;
		for(my $x=0;$x<$cnt;$x++){
			my $dval=$dvals[$x];
			my $row=$x+1;
			if(length($dval)==0){$dval=$tvals[$x];}
			my $tval=$tvals[$x];
			my $title=$tval;
			$title=~s/\"/\\\"/sg;
			$tval=~s/\"/\\\"/sg;
			$tval=~s/\'/\\\'/sg;
			$tag .= qq|<div><a class="w_block w_multiselect" href="\#" onclick="document\.$formname\.$Meta->{fieldname}.value='$tval';showDrop('$comboname',1);return false;">$dval</a></div>|;
			}
		$tag .= qq|</div>|;
		$tag .= qq|</td>|;

		$tag .= qq|</tr></table>|;
		}
	#Date
	elsif($Meta->{inputtype}=~/^date$/is || $Meta->{mask}=~/calendar/is || $Meta->{dbtype}=~/^date$/is){
		#Date - Calendar Control
		$width=$Meta->{width} || 90;
		my $cwidth = int($width-15) . "px";
		my $dwidth = int($width-4) . "px";
		$width .= "px";
		my $formname = $Meta->{formname} || "addedit";
		my $style=$Meta->{style};
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		if($val=~/^([0-9]{4,4})-([0-9]{2,2})-([0-9]{2,2})$/is){$val=$2 . "-" . $3 . "-" . $1;}
		my $comboname="$Meta->{fieldname}\_calendar";
		$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #7F9DB9;width:$width;height:20px;"><tr valign="bottom">|;
		$tag .= qq|<td nowrap="true" width="100%">|;
		$tag .= qq|<input mask="\^[0-9]{1,4}-[0-9]{1,2}-[0-9]{1,4}\$" maskmsg="Invalid date format" maxlength="10"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		$tag .= qq| type="text"|;
		$tag .= qq| onFocus="window.status='$help';"|;
		$tag .= qq| title="$title" name="$Meta->{fieldname}" value="$val" style="width:$cwidth;border:0px;">|;
		#Build combo div with options
		$tag .= qq|</td><td valign="top"><img src="/wfiles/calendar.png" width="13" height="13" border="0" onClick=showCalendarControl(document\.$formname\.$Meta->{fieldname});return false;" style="margin-top:2px;cursor:pointer;border:1px solid #CAD8F9;" onMouseOver="this.style.border='1px solid #90ABE0'" onMouseOut="this.style.border='1px solid #CAD8F9'">\n|;
		$tag .= qq|</td></tr>\n|;
		$tag .= qq|</table>\n|;
		}
	#Time
	elsif($Meta->{inputtype}=~/^time$/is || $Meta->{dbtype}=~/^time$/is){
		#Time Selector Control
		my $formname = $Meta->{formname} || "addedit";
		my $crc=encodeCRC($formname . $Meta->{fieldname});
		$tag .= qq|<input type="hidden" id="$crc" name="$Meta->{fieldname}" value="$val">\n|;
		my $hcrc=encodeCRC($formname . $Meta->{fieldname} . "Hour");
		my $mcrc=encodeCRC($formname . $Meta->{fieldname} . "Minute");
		my $pcrc=encodeCRC($formname . $Meta->{fieldname} . "PM");
		my ($inhour,$inmin,$inpm);
		if($val=~/^([0-9]{1,2})\:([0-9]{1,2}) (AM|PM)$/is){
               ($inhour,$inmin,$inpm)=($1,$2,$3);
	          }
	     elsif($val=~/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})$/is){
			my $sec;
            ($inhour,$inmin,$sec)=($1,$2,$3);
            if($inhour>12){
				$inhour=$inhour-12;
				$inpm="PM";
				}
            else{$inpm="AM";}
	        }
		#Hour Select
		$tag .= qq|<select id="$hcrc" onChange="setTimeBox('$crc',document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		#$tag .= qq|<select id="$hcrc"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		$tag .= qq|>\n|;
		foreach my $cval (1,2,3,4,5,6,7,8,9,10,11,12){
			$tag .= qq|<option value="$cval"|;
			if(length($inhour) && $inhour==$cval){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
          $tag .= qq|</select>\n|;
		#Minute Select
		$tag .= qq|<select id="$mcrc" onChange="setTimeBox('$crc',document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		#$tag .= qq|<select id="$mcrc"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		$tag .= qq|>\n|;
		foreach my $cval ("00","05","10","15","20","25","30","35","40","45","50","55"){
			$tag .= qq|<option value="$cval"|;
			if(length($inmin) && $inmin==$cval){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
          $tag .= qq|</select>\n|;
		#AM PM Select
		$tag .= qq|<select id="$pcrc" onChange="setTimeBox('$crc',document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		#$tag .= qq|<select id="$pcrc"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		$tag .= qq|>\n|;
		foreach my $cval ("AM","PM"){
			$tag .= qq|<option value="$cval"|;
			if(length($inpm) && $inpm=~/^\Q$cval\E$/is){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
          $tag .= qq|</select>\n|;
		}
	#Datetime
	elsif($Meta->{inputtype}=~/^datetime$/is || $Meta->{dbtype}=~/^datetime$/is){
		#Date - Calendar Control
		$width=$Meta->{width} || 90;
		my $cwidth = int($width-15) . "px";
		my $dwidth = int($width-4) . "px";
		$width .= "px";
		my $formname = $Meta->{formname} || "addedit";
		my $style=$Meta->{style};
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		my $timeval=$val;
		if($val=~/^([0-9]{4,4})-([0-9]{2,2})-([0-9]{2,2})/is){$val=$2 . "-" . $3 . "-" . $1;}
		my $comboname="$Meta->{fieldname}\_calendar";
		my $crc=encodeCRC($formname . $Meta->{fieldname});
		my $dcrc=encodeCRC($formname . $Meta->{fieldname} . "Date");
		my $hcrc=encodeCRC($formname . $Meta->{fieldname} . "Hour");
		my $mcrc=encodeCRC($formname . $Meta->{fieldname} . "Minute");
		my $pcrc=encodeCRC($formname . $Meta->{fieldname} . "PM");
		$tag .= qq|<input type="hidden" id="$crc" name="$Meta->{fieldname}" value="$val">\n|;

		$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #7F9DB9;width:$width;height:20px;"><tr valign="bottom">|;
		$tag .= qq|<td nowrap="true" width="100%">|;
		$tag .= qq|<input mask="\^[0-9]{1,4}-[0-9]{1,2}-[0-9]{1,4}\$" maskmsg="Invalid date format" maxlength="10"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		$tag .= qq| changed="setDateTimeBox('$crc',document.getElementById('$dcrc').value,document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		$tag .= qq| type="text"|;
		$tag .= qq| onFocus="window.status='$help';"|;
		$tag .= qq| title="$title" id="$dcrc" name="f$dcrc" value="$val" style="width:$cwidth;border:0px;">|;
		#Build combo div with options
		$tag .= qq|</td>|;
		#calendar image
		$tag .= qq|<td valign="top"><img src="/wfiles/calendar.png" width="13" height="13" border="0" onClick=showCalendarControl(document\.$formname\.f$dcrc);return false;" style="margin-top:2px;cursor:pointer;border:1px solid #CAD8F9;"></td>\n|;
		my ($inhour,$inmin,$inpm);
		if($timeval=~/\ ([0-9]{1,2})\:([0-9]{1,2}) (AM|PM)$/is){
               ($inhour,$inmin,$inpm)=($1,$2,$3);
	          }
	     elsif($timeval=~/\ ([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})$/is){
			my $sec;
            ($inhour,$inmin,$sec)=($1,$2,$3);
            if($inhour>12){
				$inhour=$inhour-12;
				$inpm="PM";
				}
            else{$inpm="AM";}
	        }
		#Hour Select
		$tag .= qq|<td><select style="border:0px;font-size:9pt;" id="$hcrc" onChange="setDateTimeBox('$crc',document.getElementById('$dcrc').value,document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		$tag .= qq|>\n|;
		foreach my $cval (1,2,3,4,5,6,7,8,9,10,11,12){
			$tag .= qq|<option value="$cval"|;
			if(length($inhour) && $inhour==$cval){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
          $tag .= qq|</select></td>\n|;
		#Minute Select
		$tag .= qq|<td><select style="border:0px;font-size:9pt;" id="$mcrc" onChange="setDateTimeBox('$crc',document.getElementById('$dcrc').value,document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		$tag .= qq|>\n|;
		foreach my $cval ("00","05","10","15","20","25","30","35","40","45","50","55"){
			$tag .= qq|<option value="$cval"|;
			if(length($inmin) && $inmin==$cval){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
          $tag .= qq|</select></td>\n|;
		#AM PM Select
		$tag .= qq|<td><select style="border:0px;font-size:9pt;" id="$pcrc" onChange="setDateTimeBox('$crc',document.getElementById('$dcrc').value,document.getElementById('$hcrc').value,document.getElementById('$mcrc').value,document.getElementById('$pcrc').value);"|;
		$tag .= qq|>\n|;
		foreach my $cval ("AM","PM"){
			$tag .= qq|<option value="$cval"|;
			if(length($inpm) && $inpm=~/^\Q$cval\E$/is){$tag .= qq| selected|;}
			$tag .= qq|>$cval</option>\n|;
			}
        $tag .= qq|</select></td>\n|;
          

		$tag .= qq|</tr>\n|;
		$tag .= qq|</table>\n|;
		}
     #MultiSelect ComboBox
	elsif($Meta->{inputtype}=~/^(MultiCombo|MultiSelect)$/is){
		my %Checked=();
		my @ivals=split(/\:/,$val);
		foreach my $ival (@ivals){$Checked{$ival}=1;}
		#Combo Box
		$width=$Meta->{width} || 125;
		my $cwidth = int($width-15) . "px";
		my $twidth = int($width-30) . "px";
		my $dwidth = int($width-4) . "px";
		$width .= "px";
		my $style=$Meta->{style};
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		$Meta->{tvals}=evalPerl($Meta->{tvals});
		$Meta->{dvals}=evalPerl($Meta->{dvals});
		my @tvals=buildVals($Meta->{tvals});
		my @dvals=buildVals($Meta->{dvals});
		#$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0"><tr valign="top">|;
		#$tag .= qq|<td align="right" style="height:14px;">|;
		$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0"|;
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		#else{$tag .= qq| style="background-color:#FFFFFF;"|;}
		$tag .= qq|><tr valign="bottom">|;
		$tag .= qq|<td nowrap="true" style="border:1px solid #7F9DB9;width:$width;height:14px;">|;
		$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0"><tr valign="bottom"><td>|;
		$tag .= qq|<input |;
		if(length($Meta->{tabindex})){$tag .= qq| tabindex="$Meta->{tabindex}"|;}
		my $showvalues=$Meta->{showvalues} || 0;
		$tag .= qq| type="text"|;
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		#if($Meta->{style}){$tag .= qq| class="$Meta->{style}"|;}
		if(length($Meta->{disabled})){$tag .= qq| DISABLED|;}
		$tag .= qq| onFocus="window.status='$help';"|;
		#$val may by multiple, so split it apart and
		my %Vlist=();
		my @vals=split(/\:+/,$val);
		foreach my $val (@vals){
			$val=strip($val);
			next if !length($val);
			$Vlist{$val}=1;
			}
		my $comboname="$Meta->{fieldname}\_multicombobox";
	     my $id=$Meta->{fieldname};
	     my $fieldname=$Meta->{fieldname};
	     my $attname="name";
	     my $attval=$Meta->{fieldname};
	     my $process=1;
	     if(length($Meta->{showname})){
			$comboname .= "\_" . $Meta->{showname};
			$id .= "\_" . $Meta->{showname};
			$attname="showname";
			$attval .= "\_" . $Meta->{showname};
			$process=0;
			}
		my $tcnt=0;
          my $vcnt=0;
          my $items=qq|<table cellspacing="0" cellpadding="0" border="0">|;
          my $tvalcnt=@tvals;
          for(my $x=0;$x<$tvalcnt;$x++){
			my $dval=$dvals[$x];
			my $row=$x+1;
			if(length($dval)==0){$dval=$tvals[$x];}
			my $tval=$tvals[$x];
			my $title=$tval;
			if($Vlist{$tval}){$vcnt++;}
			$tcnt++;
			$title=~s/\"/\\\"/sg;
			$tval=~s/\"/\\\"/sg;
			$tval=~s/\'/\\\'/sg;
			$items .= qq|<tr><td><input type="checkbox" name="$Meta->{fieldname}" $attname\="$attval" id="$Meta->{fieldname}\_cb$x" value="$tval"|;
			if($Checked{$tval}==1 || $Checked{$dval}==1 || $input{$fieldname}=~/^ALL$/s){$items .= qq| checked|;}
			if($process){$items .= qq| onClick="processMultiComboBox('$id','$Meta->{fieldname}\_cb$x',$tcnt,0,$showvalues);"|;}
			$items .= qq|></td><td width="100%" nowrap><a class="w_block w_multiselect" href="javascript:processMultiComboBox('$id','$Meta->{fieldname}\_cb$x',$tcnt,1,$showvalues);">$dval</a></td></tr>\n|;
			}
          $items .= qq|</table>\n|;
          my $svalue='';
		if(length($Meta->{showname})){
			$svalue=$Meta->{showname};
			}
          elsif($showvalues){
			$svalue=strip($val);
               $svalue=~s/^\:+//sg;
               $svalue=~s/\:+$//sg;
               $svalue=~s/\:+/\,/sg;
			if(length($svalue)){$svalue .= ',';}
			}
          else{$svalue="$vcnt/$tcnt selected";}

		$tag .= qq| title="$title" id="$id" value="$svalue" style="border:0px;width:$width;">|;
		#Build combo div with options
          $tag .= qq|</td><td align="right" valign="top"><img src="/wfiles/dropdown.png" width="13" height="14" border="0" onClick="showDrop('$comboname');return false;" style="cursor:pointer;border:1px solid #CAD8F9;" onMouseOver="this.style.border='1px solid #90ABE0'" onMouseOut="this.style.border='1px solid #CAD8F9'"></td>\n|;
          $tag .= qq|</tr></table>|;
		$tag .= qq|</td></tr>\n|;
          #$tag .= qq|</td>|;
          #Build the dropdown image
		#$tag .= qq|<td align="right" valign="top"><img src="/wfiles/dropdown.png" width="13" height="14" border="0" onClick="showDrop('$comboname');return false;" style="cursor:pointer;border:1px solid #CAD8F9;" onMouseover="this.style.border='1px solid #90ABE0'" onMouseout="this.style.border='1px solid #CAD8F9'"></td>|;
		#$tag .= qq|</tr>\n|;
		$tag .= qq|<tr valign="bottom"><td colspan="2"><div class="w_drop" id="$comboname" _behavior="dropdown" onhide="if(hasChanged('$id')){evalChange('$id');}" style="position:absolute\;width:$width;\;|;
		#$tag .= qq|<tr valign="bottom"><td colspan="2"><div class="w_drop" id="$comboname" _behavior="dropdown" style="position:absolute\;width:$width;\;|;
		if($Meta->{height}){
               $tag .= "height:" . $Meta->{height} . "px;overflow:auto\;";
	          }
		$tag .= qq|">\n|;
		if($tcnt > 1){
			$tag .= qq|<div style="border-bottom:1px solid #000;"><nobr><input type="checkbox" onClick="checkAllByAttribute(this.checked,'$attname','$attval');|;
			if($process){$tag .= qq|processMultiComboBox('$id','$Meta->{fieldname}\_cb$x',$tcnt,0,$showvalues);|;}
			$tag .= qq|" title="toggle Select All"|;
			if($input{$fieldname}=~/^ALL$/s){$tag .= " checked";}
			$tag .= qq|>All/None</nobr></div>\n|;
			}
		$tag .= $items;
		$tag .= qq|</div>|;
		$tag .= qq|</td>|;
		$tag .= qq|</tr></table>|;
		if($process){$tag .= qq|<img src="/wfiles/clear.gif" width="1" height="1" onLoad="setChangeState('$Meta->{fieldname}');">|;}
		if(length($Meta->{onchange})){
			my $change=$Meta->{onchange};
			$change=~s/\'/\\\'/sg;
			$tag .= qq|<img src="/wfiles/clear.gif" width="1" height="1" onLoad="setChangeValue('$Meta->{fieldname}','$change');">\n|;
			}
		}
     #Checkbox
	elsif($Meta->{inputtype}=~/^checkbox$/is){
		#select
		$Meta->{tvals}=evalPerl($Meta->{tvals});
		$Meta->{dvals}=evalPerl($Meta->{dvals});
		my @tvals=buildVals($Meta->{tvals});
		my @dvals=buildVals($Meta->{dvals});
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		my $cnt=@tvals;
		my $dcnt=@dvals;
		if($cnt==0){push(@tvals,1);}
		my $endtable=0;
		if($cnt>0){
			$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0"><tr align="center" valign="top">|;
			$endtable++;
			}
		my $tdcount=0;
		if($cnt == 1 && !length($Meta->{width})){$Meta->{width}=50;}
		for(my $x=0;$x<$cnt;$x++){
			my $tval=$tvals[$x];
			my $dval=$dvals[$x];
			if($endtable){$tag .= qq|<td style="padding-left:3px;padding-right:3px;" nowrap>|;}
			if(length($dval)){$tag .= qq|$dval<br style="font-size:6px;">|;}
			$title="$Meta->{fieldname} - $tval";
			if(length($help)){$title .=".  -- $help";}
			$tag .= qq|<input type="checkbox" title="$title" name="$Meta->{fieldname}" value="$tval"|;
			if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
			if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
			my @ivals=split(/\:/,$val);
			my $found=0;
			foreach my $ival (@ivals){
				last if $found==1;
				if($tval=~/^\Q$ival\E$/is){$tag .= qq| checked|;$found=1;}
				}
			$tag .= qq|>|;
			if($endtable){$tag .= qq|</td>|;}
			$tdcount++;
			if(defined $Meta->{width} && $tdcount > int($Meta->{width})){
				$tag .= qq|</tr><tr align="center" valign="top">|;
				$tdcount=0;
	               }
			}
		if($endtable){$tag .= qq|</tr></table>|;}
		}
	#Radio
	elsif($Meta->{inputtype}=~/^radio/is){
		#select
		my @tvals=buildVals($Meta->{tvals});
		my @dvals=buildVals($Meta->{dvals});
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		my $cnt=@tvals;
		my $dcnt=@dvals;
		if($cnt==0){push(@tvals,1);}
		if($cnt>1){$tag .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0"><tr align="center" valign="top">|;}
		for(my $x=0;$x<$cnt;$x++){
			my $tval=$tvals[$x];
			my $dval=$dvals[$x];
			if($cnt>1){$tag .= qq|<td style="padding-left:3px;padding-right:3px;">|;}
			if(length($dval)){$tag .= qq|$dval<br style="font-size:6px;">|;}
			$title="$Meta->{fieldname} - $tval";
			if(length($help)){$title .=".  -- $help";}
			$tag .= qq|<input type="radio" title="$title" name="$Meta->{fieldname}" value="$tval"|;
			if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
			if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
			my @ivals=split(/\:/,$val);
			my $found=0;
			foreach my $ival (@ivals){
				last if $found==1;
				if($tval=~/^\Q$ival\E$/is){$tag .= qq| checked|;$found=1;}
				}
			$tag .= qq|>|;
			if($cnt>1){$tag .= qq|</td>|;}
			}
		if($cnt>1){$tag .= qq|</tr></table>|;}
		}
	#Password
	elsif($Meta->{inputtype}=~/^password$/is){
		#text
		$width=$Meta->{width} || 125;
		$width .= "px";
		if($Meta->{tablename} !~ /^\_(fielddata)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		$tag .= qq|<input |;
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{displayname}){$tag .= qq| displayname="$Meta->{displayname}"|;}
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if($Meta->{maskmsg}){$tag .= qq| maskmsg="$Meta->{maskmsg}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{disabled}){$tag .= qq| disabled|;}
		if($Meta->{readonly}){$tag .= qq| readonly|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		$tag .= qq| type="password" onFocus="window.status='$help';" title="$title" name="$Meta->{fieldname}" value="$val" style="width:$width\;">|;
		}
	#File
	elsif($Meta->{inputtype}=~/^file$/is){
		#text
		my $path=evalPerl($Meta->{defaultval});
		$width=$Meta->{width} || 300;
		my $size=int($width/6);
		$width .= "px";
		if($path){$tag .= qq|<input type="hidden" name="ipath\_$Meta->{fieldname}" value="$path">|;}
		if($Meta->{mask}=~/autonumber/is){$tag .= qq|<input type="hidden" name="iname\_$Meta->{fieldname}" value="autonumber">|;}
		if($val && $val !~/^\Q$path\E$/is){
			$tag .= qq|<input type="hidden" name="$Meta->{fieldname}\_prev" value="$val">|;
			$tag .= qq|<div class="w_formfile"><input type="checkbox" name="$Meta->{fieldname}\_remove" value="1" title="Check to remove file"> Remove - Current Value: <a href="$val" target="_new" style="text-decoration:none">$val</a></div>|;
			}
		$tag .= qq|<input |;
		if(length($Meta->{tabindex})){$tag .= qq| tabindex="$Meta->{tabindex}"|;}
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		$tag .= qq| type="file" size="$size" onFocus="window.status='$help';" title="$title" name="$Meta->{fieldname}">|;

		}
	#Color
	elsif($Meta->{inputtype}=~/^color$/is || $Meta->{behavior}=~/color/is){
		#text
		my $path=$Meta->{defaultval};
		$width=$Meta->{width} || 300;
		my $size=int($width/6);
		$width .= "px";
		my $fieldname=$Meta->{fieldname};
		my $colordiv=$fieldname . "_colordiv";
		my $colorimg=$fieldname . "_colorimg";
		$tag .= qq|<table cellspacing="0" cellpadding="0" border="0"><tr><td>|;
		$tag .= qq|<input |;
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{displayname}){$tag .= qq| displayname="$Meta->{displayname}"|;}
		if($Meta->{maskmsg}){$tag .= qq| maskmsg="$Meta->{maskmsg}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{disabled}){$tag .= qq| disabled|;}
		if($Meta->{readonly}){$tag .= qq| readonly|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		$tag .= qq| type="text"|;
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		$tag .= qq| onFocus="window.status='$help';"|;
		$tag .= qq| title="$title" name="$Meta->{fieldname}" value="$val" style="width:$width\;">|;
        $tag .= qq|</td><td><div style="position:relative;">|;
		$tag .= qq|<a href="#" onClick="selectColor('$colordiv',$formname\.$fieldname,'$colorimg');return false;">|;
		$tag .= qq|<img title="Click to select a color from the color table" style="z-index:555;" src="/wfiles/colors.gif" width="18" height="18" border="0" id="$colorimg"></a>|;
		$tag .= qq|<div id="$colordiv" style="position:absolute;top:0px;left:0px;z-index:999;"></div>|;
		$tag .= qq|</div></td></tr></table>|;
		if(length($val)){
            $tag .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="setStyle('$colorimg','backgroundColor','$val');">|;
        	}
		}
     #Text
	else{
		#text
		$width=$Meta->{width} || 200;
		$width .= "px";
		if($Meta->{tablename} !~ /^\_(fielddata|pages)$/is){
			$val=evalPerl($val);
			}
		$val=encodeHtml($val);
		$tag .= qq|<input |;
		if($Meta->{mask}){$tag .= qq| mask="$Meta->{mask}"|;}
		if($Meta->{displayname}){$tag .= qq| displayname="$Meta->{displayname}"|;}
		if($Meta->{maskmsg}){$tag .= qq| maskmsg="$Meta->{maskmsg}"|;}
		if($Meta->{required}){$tag .= qq| required="1"|;}
		if($Meta->{disabled}){$tag .= qq| disabled|;}
		if($Meta->{readonly}){$tag .= qq| readonly|;}
		if($Meta->{requiredmsg}){$tag .= qq| requiredmsg="$Meta->{requiredmsg}"|;}
		if($Meta->{class}){$tag .= qq| class="$Meta->{class}"|;}
		if($Meta->{style}){$tag .= qq| style="$Meta->{style}"|;}
		if(length($Meta->{onchange})){$tag .= qq| onChange="$Meta->{onchange}"|;}
		if(length($Meta->{onclick})){$tag .= qq| onClick="$Meta->{onclick}"|;}
		$tag .= qq| type="text"|;
		if($Meta->{inputmax}){$tag .= qq| maxlength="$Meta->{inputmax}"|;}
		if(length($Meta->{calendar}) && $Meta->{calendar}==1){
			$tag .= qq| onfocus="showCalendarControl(this);"|;
			if($val=~/^([0-9]{4,4})[\-\/]([0-9]{2,2})[\-\/]([0-9]{2,2})/s){
				$val=$2 . '-' . $3 . '-' . $1;
				}
			}
		else{$tag .= qq| onFocus="window.status='$help';"|;}
		$tag .= qq| title="$title" name="$Meta->{fieldname}" value="$val" style="width:$width\;">|;
		}
	$tag .= qq|</span>|;
	return $tag;
	}
##############
sub buildVals{
	my $str=shift || return;
     $str=processString($str);
	#select
	#one:two:three
	my @vals=();
	if($str=~/^select\ /is){
		#SQL
		my %blist=();
		my $bcnt=getDBData(\%blist,$str,"nocount=1");
		if(! isNum($bcnt)){return $bcnt;}
		for(my $x=0;$x<$bcnt;$x++){
			my @tmp=();
			foreach my $fld (@{$blist{fields}}){
				push(@tmp,$blist{$x}{$fld});
				}
			my $cval=join(" ",@tmp);
			push(@vals,$cval);
			}
		}
	elsif($str=~/^getDBFields\((.+?)\)$/is){
		#subName(params)
		my $params=$1;
		my @tmp=split(/\,/,$params);
		@vals=getDBFields(@tmp);
		@vals=sort(@vals);
		}
	elsif($str=~/^\&/s){
		#Subroutine
		$str=~s/^\&//;
		@vals=&$str;
		}
	elsif($str=~/([0-9]+)?\.\.([0-9]+)/s){
		# 1..30  or a..z
		my $b=$1;
		my $e=$2;
		@vals=($b..$e);
		}
	elsif($str=~/\r\n/s){
		#Multi-Line Input - treat each line as a separate value
          @vals=split(/[\r\n]+/,$str);
		}
	else{
		@vals=split(/[:;,]+/,$str);
		}
	return @vals;
	}
##############
sub processString{
  my $str=shift;
  #Remove <perl> </perl> tags before.
  my @ptags=();
  my $pcnt=0;
  while ($str=~m/\<perl\>(.+?)\<\/perl\>/sig){
      my $tag=$&;
      $ptags[$pcnt]=$tag;
      my $ph=qq|<<<Ptag[$pcnt]>>>|;
      $pcnt++;
      $str=~s/\Q$tag\E/$ph/is;
      }
  #Remove <? ?> tags before.
  my @ptags2=();
  my $pcnt2=0;
  while ($str=~m/\<\?(.+?)\?\>/sig){
      my $tag=$&;
      $ptags2[$pcnt2]=$tag;
      my $ph=qq|<<<Ptag2[$pcnt2]>>>|;
      $pcnt2++;
      $str=~s/\Q$tag\E/$ph/is;
      }
  #abort($str);
  # $input{name}
	while ($str=~m/\$input\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val='';
		if(length($key)){$val .= $input{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $user{field}
	while ($str=~m/\$user\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val='';
		if(length($key)){$val .= $USER{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $ENV{HTTP_HOST}
	while ($str=~m/\$ENV\{(.+?)\}/sig){
		my $tag=$&;
		my $key=uc(strip($1));
		my $val='';
		if(length($key)){$val .= $ENV{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	# $PAGE{_id}
	while ($str=~m/\$PAGE\{(.+?)\}/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val='';
		if(length($key)){$val .= $PAGE{$key};}
		$str=~s/\Q$tag\E/$val/is;
		}
	#$date{NM-ND-YYYY}
	while ($str=~m/\$date\{(.+?)\}/isg){
		my $tag=$&;
		my $key=strip($1);
		my $val='';
		if(length($key)){$val .= &getDate($key);}
		$str=~s/\Q$tag\E/$val/is;
		}
  #Put back <perl></perl> tags
  $pcnt=@ptags;
  for(my $x=0;$x<$pcnt;$x++){
      my $ph=qq|<<<Ptag[$x]>>>|;
      $str=~s/\Q$ph\E/$ptags[$x]/is;
      }
  #Put back <? ?> tags
  $pcnt2=@ptags2;
  for(my $x=0;$x<$pcnt2;$x++){
      my $ph=qq|<<<Ptag2[$x]>>>|;
      $str=~s/\Q$ph\E/$ptags2[$x]/is;
      }
  return $str;
  }
##############  Manage Subs
sub Variables{
	my $rtn='';
	#print help on exposed variables that Wasql Offers
	#$PAGE, $USER,
	my $dsign="\&\#36\;";
	my $usign=$dsign . "USER";
	my $psign=$dsign . "PAGE";
	$rtn .= qq|<div class="w_text w_smaller">\n|;
	$rtn .= qq|<p><u class="w_big w_lblue"><b>$usign Hash</b></u><br>Can be used in perl tags and is parsed in pages as $usign\{field}. Returns the value in the _users table for the said field.<br>Example use in perl tags: if($usign\{_id}){...code here}.</p>\n|;
	$rtn .= qq|<p><u class="w_big w_lblue"><b>$psign Hash</b></u><br>Can be used in perl tags and is parsed in pages as $psign\{field}. Returns the value of the current page being viewed for the said field.<br>Example use in perl tags: if($psign\{name}){...code here}.</p>\n|;
	$psign=$dsign . "input";
	$rtn .= qq|<p><u class="w_big w_lblue"><b>$psign Hash</b></u><br>Can be used in perl tags and is parsed in pages as $psign\{field}. Returns the input value for the said field.<br>Example use in perl tags: if($psign\{go}=~/yes/is){...code here}.</p>\n|;
	$psign=$dsign . "ENV";
	$rtn .= qq|<p><u class="w_big w_lblue"><b>$psign Hash</b></u><br>Can be used in perl tags and is parsed in pages as $psign\{field}. Returns the environment variable value.<br>Example use in perl tags: if($psign\{SCRIPT_NAME}){...code here}.</p>\n|;
	$rtn .= qq|</div>\n|;
	return $rtn;
	}
#####################
sub helpContents{
	my %params=@_;
	my $rtn ='';
	$rtn .= qq|<div id="helpContents" style="margin:3px;">\n|;
	#Read in subs and perl5ref
	my %Sub=();
	foreach my $file (@reqFiles){
		my $cnt=readUsage($file,\%Sub);
    	}
    #read in perl5ref.txt
    my $ref=getFileContents("$progpath/helpcontents.xml");
    while($ref=~m/\<(.+?)\>(.+?)<\/\1\>/sig){
		my $cat=strip($1);
		$Sub{$cat}{ref}=strip($2);
    	}
    #Show search form
	$rtn .= qq|	<div id="helpContents_toc">\n|;
	$rtn .= qq|		<form name="helpform" method="post" action="$cgiroot" class="w_form">\n|;
	if($params{_view}){
		$rtn .= qq|<input type="hidden" name="_view" value="$params{_view}">\n|;
		}
	elsif($input{_view}){
		$rtn .= qq|<input type="hidden" name="_view" value="$input{_view}">\n|;
		}
	elsif(defined $input{'_m0'} && defined $input{'_m0'}){
		$rtn .= qq|			<input type="hidden" name="_m0" value="help">\n|;
		$rtn .= qq|			<input type="hidden" name="_m1" value="Help Contents">\n|;
		}
	elsif(length($input{_manage})){
		$rtn .= qq|<input type="hidden" name="_manage" value="$input{_manage}">\n|;
		}
	#Add any other params as hidded fields
	foreach my $key (sort(keys(%params))){
		next if $key=~/^\_/s;
		my $pval=$params{$key};
		$key=lc(strip($key));
		my $val=$input{$key} || $pval;
		next if length($val)==0;
		$val=encodeHtml($val);
		$rtn .= qq|<input type="hidden" name="$key" value="$val">\n|;
		}
	#Show Selection of categories
    $rtn .= qq|			<select name="helpcat" onChange="document.helpform.submit();">\n|;
    $rtn .= qq|			<option value="">- Category -</option>\n|;
    $rtn .= qq|			<option value="">------------</option>\n|;
    foreach my $sub (sort(keys(%Sub))){
		if(length($input{helpsearch})){
			my $found=0;
			$found++ if $Sub{$sub}{usage} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{info} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{tags} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{ref} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $sub =~ /\Q$input{helpsearch}\E/is;
			next if !$found;
        	}
    	$rtn .= qq|			<option value="$sub"|;
    	if(length($input{helpcat}) && $input{helpcat}=~/^\Q$sub\E$/is){$rtn .= " selected";}
		$rtn .= qq|>$sub</option>\n|;
    	}
    $rtn .= qq|			</select>\n|;
	$rtn .= qq|			<input type="text" name="helpsearch" value="$input{helpsearch}" onFocus="this.select();">\n|;
	$rtn .= qq|			<input type="submit" value="Search">\n|;
	if(length $input{helpsearch} || length $input{helpcat}){
		$rtn .= qq|			<input type="button" value="Clear" onClick="document.helpform.helpsearch.value='';document.helpform.helpcat.value='';document.helpform.submit();">\n|;
		}
	$rtn .= qq|		</form>\n|;
	$rtn .= qq|		<img src="/wfiles/clear.gif" width="100" height="1" border="0" onLoad="document.helpform.helpsearch.focus();">\n|;
	$rtn .= qq|	</div>\n|;
	if(length $input{helpsearch} || length $input{helpcat}){
		my @searchterms=split(/\s+/,$input{helpsearch});
		#only show results if they have entered a search term
		$rtn .= qq|	<div id="helpContents_results">\n|;
	    foreach my $sub (sort(keys(%Sub))){
			if(length($input{helpcat}) && $input{helpcat}!~/^\Q$sub\E$/is){next;}
			my $found=0;
			$found++ if $Sub{$sub}{usage} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{info} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{tags} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $Sub{$sub}{ref} =~ /\Q$input{helpsearch}\E/is;
			$found++ if $sub =~ /\Q$input{helpsearch}\E/is;
			next if !$found;
			$rtn .= qq|<div><b class="w_dblue">$sub</b>\n|;
	        $rtn .= qq|	<div style="margin-left:20px"><b class="w_lblue">Usage:</b> $Sub{$sub}{usage}</div>\n| if length $Sub{$sub}{usage};
	        $rtn .= qq|	<div style="margin-left:20px"><b class="w_lblue">Info:</b> $Sub{$sub}{info}</div>\n| if length $Sub{$sub}{info};
	        if(defined $Sub{$sub}{ref}){
				$rtn .= qq|	<div style="margin-left:20px">\n|;
				if($Sub{$sub}{ref} =~/\<div\>/s){$rtn .= $Sub{$sub}{ref};}
				else{
					my @lines=split(/[\r\n]+/,$Sub{$sub}{ref});
					foreach my $line (@lines){
						$line=strip($line);
						next if !length($line);
						my ($name,$desc)=split(/[\t]+/,$line);
						$name=encodeHtml($name);
						$rtn .= qq|<div><b class="w_lblue">$name</b> &nbsp;&nbsp;&nbsp;&nbsp; $desc</div>\n|;
		            	}
		  			}
				$rtn .= qq|</div>\n|;
				}
	        if(defined $Sub{$sub}{tags}){
				$rtn .= qq|	<div style="margin-left:20px"><b class="w_lblue">Tags:</b>|;
				my @tags=split(/[\,\s\;]+/,$Sub{$sub}{tags});
				foreach my $tag (sortTextArray(@tags)){
					$rtn .= qq|<a href="#" onClick="document.helpform.helpsearch.value='$tag';document.helpform.submit();return false;">$tag</a>\n|;
	            	}
				$rtn .= qq|</div>\n|;
				}
			$rtn .= qq|</div>\n|;
			}
	    $rtn .= qq|	</div>\n|;
		}
	$rtn .= qq|</div>\n|;
	return $rtn;
	}
##############
sub htmlCharset{
	my $rtn;
	$rtn .= qq|<table class="w_table" cellspacing="1" cellpadding="1" border="1">\n|;
	$rtn .= qq|<tr align="center">|;
	$rtn .= qq|<th>ID</th><th>Char</th>|x10;
	$rtn .= qq|</tr>\n|;
	$rtn .= qq|<tr align="center">|;
	my $c=0;
	foreach my $x (32..383,399,402,416,417,431,432,461..476,506..511,601,900..974,1025..1116,1118,1119,1168..1182,1197..1211,1240,1241,1256,1257,1329..1475,1757,1758,1769,4342,7840..7929,8211..8226,8362,8482,8531,8532,8539..8542,8592..8597,8706,8710,8719,8721,8730,8734,8745,8776,8800,8801,8804,8805,8962,9650,9658,9660,9668,9674,9675,9679,9688,9689,9786,9787,9788,9792,9794,9824,9827,9829,9830,9834,9835){
		if(($x>745 && $x<900) || ($x>1415 && $x<1475) || ($x>1836 && $x<1920) || ($x>1957 && $x<2309) || ($x>2799 && $x<2949) || ($x>3439 && $x<3585) || ($x>3675 && $x<4304) || ($x>4342 && $x<4978) || ($x>4987 && $x<5870) || ($x>5872 && $x<7680) || ($x>8260 && $x<8352) || ($x>8367 && $x<8448) || ($x>8995 && $x<9216) || ($x>9252 && $x<9312) || ($x>9710 && $x<9786) || ($x>9794 && $x<9824) || ($x>9841 && $x<10001)){next;}
		$rtn .= qq|<td align="right" style="background-color:#c0c0c0;color:#ffffff;">$x</td><td style="font-size:18px;">\&\#$x\;</td>|;

		$c++;
		my $f=isFactor($c,10);
		$rtn .= qq|<!-- isFactor($c,10)=$f -->\n|;
		if($f){
			$c=0;
			$rtn .= qq|</tr>\n<tr align="center">|;
			}
		}
	$rtn .= qq|</tr>\n|;
	$rtn .= qq|</table>\n|;
	return $rtn;
	}
##############
sub Subs{
	my %Sub=();
	my $filter=shift;
	my @files = ('subs_database.pl','subs_common.pl','subs_wasql.pl','subs_socket.pl');
	my $baseurl="$cgiroot\?_m0=help&_m1=Subroutines";
	#sort by file
	print qq|<div id="helpmenu" align="left">Search by File: \n|;
	foreach my $file (@files){
		my $cnt=readUsage($file,\%Sub);
		my $name=$file;
		$name=~s/\.pl$//is;
		$name=~s/^subs\_//is;
		print qq|<a href="$baseurl\&file=$file" title="$cnt references" class="w_link">$name</a> -\n|;
		}
	if($input{file}){print qq|<a href="$baseurl" title="Show all" class="w_link">Show All</a>\n|;}
	print qq|</div>\n|;
	#search by tag
	my %Tags=();
	foreach my $subkey (sort(keys(%Sub))){
		next if !defined $Sub{$subkey}{tags};
		my @tags=split(/[\,\s\;]+/,$Sub{$subkey}{tags});
		foreach my $tag (@tags){
			$tag=lc(strip($tag));
			$Tags{$tag} += 1;
        	}
 		}
 	print qq|<div id="helpmenu" align="left">Search by Tag: \n|;
	foreach my $tag (sort(keys(%Tags))){
        print qq|<a href="$baseurl\&tag=$tag" title="$Tags{$tag} references" class="w_link">$tag</a> -\n|;
		}
	if($input{tag}){print qq|<a href="$baseurl" title="Show all" class="w_link">Show All</a>\n|;}
	print qq|</div>\n|;
	print qq|<hr size="1">\n|;
	if($input{file}){print qq|<h2>File: $input{file}</h2>\n|;}
	foreach my $subkey (sort(keys(%Sub))){
		my $file=$Sub{$subkey}{file};
		next if $input{file} && $input{file}!~/^\Q$file\E$/is;
		if($input{tag}){
			next if !defined $Sub{$subkey}{tags};
			my $foundtag=0;
            my @tags=split(/[\,\s\;]+/,$Sub{$subkey}{tags});
			foreach my $tag (@tags){
				$tag=lc(strip($tag));
				if($input{tag}=~/^\Q$tag\E$/is){$foundtag++;}
				}
			next if !$foundtag;
			}
		print qq|<div style="font-size:14px"><b style="color:#6699CC;">$subkey</b> <span style="font-size:11px;color:#72A0CF;">($Sub{$subkey}{file})</span>\n|;
		print qq|<div style="padding-left:20px;font-size:12px"><b style="color:#72A0CF;">Usage: </b>$Sub{$subkey}{usage}</div>\n|;
		print qq|<div style="padding-left:20px;font-size:12px"><b style="color:#72A0CF;">Info: </b>$Sub{$subkey}{info}</div>\n|;
		if($Sub{$subkey}{tags}){
			print qq|<div style="padding-left:20px;font-size:12px"><b style="color:#72A0CF;">Tags: </b>$Sub{$subkey}{tags}</div>\n|;
			}
		print qq|</div>\n|;
		}
	}
###########
sub readUsage{
	my $file=shift || return;
	my $Sub=shift || return;
	#print "Reading $file<br>\n";
	my $afile="$progpath/$file";
	return if ! -e $afile;
	#print "Reading $file<br>\n";
	open(HF,$afile);
	my @lines=<HF>;
	close(HF);
	my $linecnt=@lines;
	my $cnt=0;
	#print "linecnt=$linecnt<br>\n";
	for(my $x=0;$x<$linecnt;$x++){
		my $line=strip($lines[$x]);
		if($line=~/sub\ (.+)/s){
			my $name=strip($1);
			$name=~s/\{$//s;
			$Sub->{$name}{file}=$file;
			my $y=$x+1;
			while($lines[$y]=~/\#(usage|info|tags):(.+)/is){
				my $key=strip(lc($1));
				my $val=strip($2);
				$Sub->{$name}{$key} .= $val . " ";
				$y++;
				}
			if(length($Sub->{$name}{usage})==0){delete $Sub->{$name};}
			else{$cnt++;}
			}
		}
	return $cnt;
	}
##############
sub Env {
	my $rtn='';
	$rtn .= qq|<div class="w_text w_smaller">\n|;
	foreach my $env (sort(keys(%ENV))){
		if($env=~/^(GUID|REMOTE_BROWSER|REMOTE_OS|UNIQUE_HOST|WASQLCSSJS|WASQL_VERSION)$/is){
			$rtn .= qq|<b class="w_red">$env: </b>$ENV{$env}<br>\n|;
			}
		else{$rtn .= qq|<b class="w_text">$env: </b>$ENV{$env}<br>\n|;}
		}
	$rtn .= qq|</div>\n|;	
	return $rtn;
	}
######################################
sub setupWaSQL{
	#Check to see if the database has a _users table, if so return
	my $h=getDBTables(2,1);
	return if defined $h->{_users};
	my $ck=0;
	#Check to Config{xml} to see if a particular XML should be used to create the base db.
	if($Config{xml} && -e $Config{xml}){
		$ck=&importXML(file=>$Config{xml},schema=>1,meta=>1,data=>1,output=>0);
		}
	elsif(-e "$progpath/wasql.xml"){
		$ck=&importXML(file=>"$progpath/wasql.xml",schema=>1,meta=>1,data=>1,output=>0);
		}
	else{abort("No schema xml file found");}
	if($ck==1 || $ck==2){
		&manageWaSQL("A new $dbt database has been created called $dbname");
		exit;
		}
	&printHeader("$progname - Database Creation Error");
	print "importXML ERROR: $ck<br>\n";
	printFooter();
	exit;
	}
###########
sub addEditForm{
	#usage: $rtn .= addEditForm(_table=>$table); or &AddEditForm(_table=>$table,_id=>$id);
	#info: Creates an add record form (or edit record form if _id is passed in)
	return AddEditForm(@_);
	}
###########
sub AddEditForm{
	my %params=@_;
	if(!$params{_table}){abort("No Table in AddEditForm");}
	my $rtnstr='';
	my %tlist=();
	#is the current user an admin?
	my $isAdmin=0;
	if($USER{_id} && $USER{utype}==0){$isAdmin=1;}
	#Get Form Fields from _tabledata
	my $formfieldstr='';
	my @formfields=();
    if($params{_formfieldstr}){
		$formfieldstr=$params{_formfieldstr};
		if(isHtml($formfieldstr)){@formfields=getDBFields($params{_table});}
		else{@formfields=split(/[\:\&\,\s\r\n]+/,$formfieldstr);}
		}
	elsif(isNum($params{_custom})){
		my %rec=getDBRecord(-table=>"_pages",_id=>$params{_custom});
		if(!$rec{_id}){return "Error - No custom page found $params{_custom}" . $rec{-error};}
		$formfieldstr=$rec{body};
		@formfields=getDBFields($params{_table});
		}
	elsif($params{_formfields}){
		$formfieldstr=$params{_formfields};
		@formfields=split(/[\:\&\,\s\r\n]+/,$formfieldstr);
	     }
	if(!length($formfieldstr)){
		#Get formfield info from _tabledata
		my $sql=qq|select formfields from _tabledata where tablename='$params{_table}'|;
		if(!$isAdmin || $params{_mod}==1){$sql=qq|select formfields_mod as formfields from _tabledata where tablename='$params{_table}'|;}
		my %list=();
        my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
		if($cnt==1){
			$formfieldstr=$list{0}{formfields};
			#abort($formfieldstr);
			if(isHtml($formfieldstr)){@formfields=getDBFields($params{_table});}
			else{@formfields=split(/[\:\&\,\s\r\n]+/,$formfieldstr);}
			}
		}
	if(length($formfieldstr)==0){
		#Nothing is defined in _tabledata - get a user field list
		@formfields=getDBFields($params{_table},1);
		$formfieldstr=join(',',@formfields);
		}
	#get a hash if field schema types
	my %Finfo=();
	getDBFieldTypes(\%Finfo,$params{_table});
	#Get table meta data
	my %meta=();
	my @tmp=();
	my $fcnt=@formfields;
	my $formfieldlist=join(",",@formfields);
	#abort("Formfields[$fcnt]: @formfields<hr>$formfieldlist");
	for(my $f=0;$f<$fcnt;$f++){
		my $field=$formfields[$f];
		if(defined $Finfo{$field}){push(@tmp,"\'$field\'");}
		else{delete($formfields[$f]);}
		}

	my $fieldstr=join(',',@tmp);
	my $sql="select * from _fielddata where tablename like '$params{_table}' and fieldname in ($fieldstr)";
	my $cnt=getDBData(\%meta,$sql,"nocount=1");
	#abort("[$cnt] $sql<br><br>$DBI::query<br>$DBI::debug");
	my %Findex=();
	my $enctype=$params{_enctype} || "application/x-www-form-urlencoded";
	for(my $x=0;$x<$cnt;$x++){
		my $field=$meta{$x}{fieldname};
		if($meta{$x}{inputtype}=~/^file$/is){$enctype="multipart/form-data";}
		$Findex{$field}=$x;
	     }
	my %Rec=();
	if(isNum($params{_id})){
		$sql="select * from $params{_table} where _id=$params{_id}";
		my $cnt=getDBData(\%Rec,$sql,"nocount=1");
		#my @ifields=("_cdate","_edate","_cuser","_euser");
		my @ifields=@{$Rec{fields}};
		#provide more information on internal fields
		foreach my $field (@ifields){
			if($field=~/^\_(cdate|edate)/is && $Rec{0}{$field}=~/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})\ ([0-9]{2,2})\:([0-9]{2,2})\:([0-9]{2,2})$/is){
   			     #Split apart internal date fields into date and time
                    $input{"$field\_date"}="$1\-$2\-$3";
                    $input{"$field\_time"}="$4\:$5\:$6";
                    #abort($input{"$field\_time"});
	               }
	          elsif($field=~/^\_(euser|cuser)$/is){
				#Get username for cuser and euser
                    $input{"$field\_username"}='Unknown';
				if(length($Rec{0}{$field})){$input{"$field\_username"}=getUserById($Rec{0}{$field},"username");}
				}
			$input{$field}=$Rec{0}{$field};
			}
		}
	#Check to see if $formfields is HTML. If so process as such
	my $onsubmit=$params{onsubmit} || $params{_onsubmit} || "return submitForm(this);";
	my $formname=$params{_formname} || $params{_name} || "addedit";
	$rtnstr .= waSQLCssJs() if $ENV{waSQLCssJs} != 1;
	my %Formfield=();
	#Beginnning form tag
	if(!defined $params{_viewonly} || $params{_viewonly}!=1){
		my $action=$params{_action} || $ENV{SCRIPT_NAME};
		if($action=~/^manage$/is){$action=$cgiroot;}
		$rtnstr .= qq|<!-- Begin form: $formname, action:$action, onsubmit: $onsubmit -->\n|;
		#$rtnstr .= qq|<form name="$formname">\n|;
  
		$rtnstr .= qq|<form name="$formname" class="w_form" action="$action" method="POST" enctype="$enctype" onSubmit="$onsubmit"|;
		if(defined $params{_target}){$rtnstr .= qq| target="$params{_target}"|;}
		$rtnstr .= qq|>\n|;
		#Hidden fields
        $rtnstr .= qq|<input type="hidden" name="_formname" value="$formname">\n|;
		$rtnstr .= qq|<input type="hidden" name="_enctype" value="$enctype">\n|;
		$rtnstr .= qq|<input type="hidden" name="_table" value="$params{_table}">\n|;
		$Formfield{'_table'}=1;
		my @wfields=("_host","_manage","_view","_m0","_m1","_m2","_searchfield","_search","_order","_limit","_offset");
		foreach my $wfield (@wfields){
			my $val=$params{$wfield} || $input{$wfield};
			$val=strip($val);
			next if !length($val);
			$rtnstr .= qq|<input type="hidden" name="$wfield" value="$val">\n|;
			$Formfield{$wfield}=1;
			}
		my $_sql=$params{_sql} || $input{_sql};
		if(length($input{_sql})){
			$rtnstr .= qq|<div style="display:none"><textarea name="_sql">$input{_sql}</textarea></div>\n|;
			$Formfield{_sql}=1;
			}
		if(length($input{_searchpairs})){
			$rtnstr .= qq|<div style="display:none"><textarea name="_searchpairs">$input{_searchpairs}</textarea></div>\n|;
			$Formfield{_searchpairs}=1;
			}

		}
	my $focusfield;
	my $formula=0;
	my $initdrop=0;
	my $publish=0;
	my @onloads=();
	#check for custom form
 	$rtnstr .= qq|<div align="left">\n|;
	if(isHtml($formfieldstr)){
		#Custom Form
		#parse the HTML in formfields,replacing [$field] with the input tag
		my %FoundFields=();
		foreach my $field (@formfields){
			next if ! defined $Finfo{$field};
			my $index=$Findex{$field};
			$formfieldstr=evalPerl($formfieldstr);
			while($formfieldstr=~m/\[$field\]/sig){
				my $tag=$&;
				$FoundFields{$field}=1;
				my %tag=();
	               $ck=&buildHash(\%meta,\%tag,$Findex{$field});

	               if(length($tag{fieldname})==0){
					$tag{fieldname}=$field;
					}
			     $tag{tablename}=$params{_table};
				$tag{formname}=$formname;
				$tag{dbtype}=$Finfo{$field};
				$tag{dbtype}=$Finfo{$field};
				if(!$tag{inputtype}){
					#Set some defaults based on dbtype
					if($Finfo{$field}=~/^text$/is){
                              $tag{inputtype}="textarea";
						$tag{height} ||= 50;
						$tag{width} ||= 300;
						#$tag{tvals} = "drag";
	                         }
	                    elsif($field=~/^\_(edate|cdate)/is || $Finfo{$field}=~/^date$/is){
                              $tag{inputtype}="date";
	                         }
	                    elsif($field=~/^\_(euser|cuser)/is || $field=~/^user\_id$/is){
                              $tag{inputtype}="select";
                              $tag{tvals}="select _id from _users order by username,_id";
                              $tag{dvals}="select username from _users order by username,_id";
	                         }
	                    elsif($Finfo{$field}=~/^datetime$/is){
                              $tag{inputtype}="datetime";
	                         }
	                    elsif($Finfo{$field}=~/^time$/is){
                              $tag{inputtype}="time";
	                         }
	                    elsif($Finfo{$field}=~/varchar/is){
                              $tag{inputtype}="text";
	                         }

				 	}  
				if($params{_wrap} && $tag{inputtype}=~/^textarea$/is){$tag{wrap}=$params{_wrap};}
				if($params{_class}){$tag{class}=$params{_class};}
				if($params{"$field\_wrap"} && $tag{inputtype}=~/^textarea$/is){$tag{wrap}=$params{"$field\_wrap"};}
				if($params{"$field\_disabled"}){$tag{disabled}=$params{"$field\_disabled"};}
				if($params{"$field\_readonly"}){$tag{readonly}=$params{"$field\_readonly"};}
				if($params{"$field\_style"}){$tag{style}=$params{"$field\_style"};}
				if($params{"$field\_class"}){$tag{class}=$params{"$field\_class"};}
				if(length($tag{mask})){
					my $mask=$tag{mask};
					if($tag{mask}=~/^Calendar$/is){
						$tag{inputtype}="date";
						$tag{calendar}=1;
						}
					}
				my $tagval='';
				if($params{_id}){
					if(length($Rec{0}{$field})){$tagval=$Rec{0}{$field};}
					elsif(length($input{$field})){$tagval=$input{$field};}
					}
				else{
					if(length($params{$field})){$tagval=$params{$field};}
					elsif(length($input{$field})){$tagval=$input{$field};}
					elsif(length($tag{defaultval})){$tagval=$tag{defaultval};}
					}
                if(length($tag{onchange}) && length($tagval)){
					my $onchange=$tag{onchange};
					while($onchange=~m/\$input\{(.+?)\}/sig){
						my $tag=$&;
						my $key=lc($1);
						$onchange=~s/\Q$tag\E/$input{$key}/is;
	                    }
	                while($onchange=~m/\$params\{(.+?)\}/sig){
						my $tag=$&;
						my $key=lc($1);
						$onchange=~s/\Q$tag\E/$params{$key}/is;
	                    }
	                $onchange=~s/addedit/$formname/sg;
                    $tag{onchange}=$onchange;
                    $onchange=~s/this\./document\.$formname\.$field\./sg;
					push(@onloads,$onchange);
                    }
				if($field=~/^publish$/is && length($tagval)){$publish=1;}
				if($field=~/^body$/is && $params{_table}=~/^\_(pages|templates)$/is){$tag{tvals}="drag html";}
				if($meta{$index}{inputtype}=~/^formula$/is){
					$tag{inputtype}="textarea";
					$tag{height} ||= 150;
					$tag{width} ||= 325;
					$tag{displayname} ||=ucfirst($field);
					$tag{displayname} .= qq| <i>(Formula)</i>|;
					}
                    my $ctag='';
				if($field=~/^\_(id|cdate|edate|adate|euser|cuser)$/is || $tag{inputtype}=~/hidden/is || $params{_viewonly}==1 || $params{_viewonly}=~/^$field$/is || $params{_readonly}=~/^$field$/is){
					$ctag=$tagval if $tag{inputtype}!~/hidden/is;
					my $cval='';
					if(($params{_viewonly}==1 && $tag{inputtype}=~/^textarea$/is) || $params{_tidy}=~/$field/is){
						$ctag=$tagval;
						$ctag=~s/\r\n/<br>/sg;
	                    }
					else{$cval=encodeHtml($tagval);}
					if($tag{inputtype}=~/checkbox/is){
						if(isNum($tagval)){
							if($tagval==1){$ctag=qq|<img src="http://$ENV{HTTP_HOST}/wfiles/success.gif" border="0">|;}
							elsif($tagval==0){$ctag=qq|<img tagval="$tagval" val="$input{$field}" src="http://$ENV{HTTP_HOST}/wfiles/clear.gif" border="0">|;}
							}
						elsif($tagval=~/\:/s){
							$ctag=~s/\:/\ /sg;
                        	}
						}
					if(!$Formfield{$field}){
						$Formfield{$field}=1;
						if($tag{inputtype}=~/textarea/is){
								$ctag .= qq|<textarea style="display:none;" name="$field">$cval</textarea>\n|;
							}
						else{
		                         $ctag .= qq|<input type="hidden" name="$field" value="$cval">|;
		    					}
						}
					elsif($tag{width} && $tag{height}){
						my $w=$tag{width} . "px";
						my $h=$tag{height} . "px";
						$ctag=qq|<div style="width:$w;height:$h">$ctag</div>|;
                    	}
					}
				else{$ctag = &buildTag(\%tag,$tagval);}
				$Formfield{$field}=1;
				$formfieldstr=~s/\Q$tag\E/$ctag/is;
	            }
	          }
		$rtnstr .= $formfieldstr;
		@formfields=sort(keys(%FoundFields));
		$formfieldlist=join(',',@formfields);
	     }
     else{
		#Build the Basic form
		#linebreaks are denoted by a :
		my @lineparts=split(/[\,&\r\n]+/,$formfieldstr);
		my $tabindex=0;
		foreach my $linepart (@lineparts){
			#$rtnstr .= qq|<!-- LinePart: $linepart -->\n|;
			$rtnstr .= qq|<table id="w_formtable" class="w_table" cellspacing="0" cellpadding="0" border="0">\n|;
			$rtnstr .= qq|\t<tr class="w_formrow" align="left" valign="top">\n|;
			my @fields=split(/[:\s]+/,$linepart);
			foreach my $field (@fields){
				my $index=$Findex{$field};
				#$rtnstr .= qq|<!-- field: $field, Finfo: $Finfo{$field}, index: $index, type:$meta{$index}{inputtype} -->\n|;
				next if ! defined $Finfo{$field};
				my %tag=();
                $ck=&buildHash(\%meta,\%tag,$index);
	            if(length($tag{fieldname})==0){
					$tag{fieldname}=$field;
					}
			    $tag{tablename}=$params{_table};
				$tag{formname}=$formname;
				$tag{dbtype}=$Finfo{$field};
				if(!$tag{inputtype}){
					#Set some defaults based on dbtype
					if($Finfo{$field}=~/^text$/is){
                              $tag{inputtype}="textarea";
						$tag{height} ||= 50;
						$tag{width} ||= 300;
	                         }
	                    elsif($field=~/^\_(edate|cdate)/is || $Finfo{$field}=~/^date$/is){
                              $tag{inputtype}="date";
	                         }
	                    elsif($field=~/^\_(euser|cuser)/is || $field=~/^user\_id$/is){
                              $tag{inputtype}="select";
                              $tag{tvals}="select _id from _users order by username,_id";
                              $tag{dvals}="select username from _users order by username,_id";
	                         }
	                    elsif($Finfo{$field}=~/^datetime$/is){
                              $tag{inputtype}="datetime";
	                         }
	                    elsif($Finfo{$field}=~/^time$/is){
                              $tag{inputtype}="time";
	                         }
	                    elsif($Finfo{$field}=~/varchar/is){
                              $tag{inputtype}="text";
	                         }
				 	}
				if($params{"$field\_wrap"} && $tag{inputtype}=~/^textarea$/is){$tag{wrap}=$params{"$field\_wrap"};}
				if($params{"$field\_disabled"}){$tag{disabled}=$params{"$field\_disabled"};}
				if($params{"$field\_readonly"}){$tag{readonly}=$params{"$field\_readonly"};}
				if($params{"$field\_style"}){$tag{style}=$params{"$field\_style"};}
				if($params{"$field\_class"}){$tag{class}=$params{"$field\_class"};}
				if($params{"$field\_displayname"}){$tag{displayname}=$params{"$field\_displayname"};}
				if($params{"$field\_height"}){$tag{height}=$params{"$field\_height"};}
				if($params{"$field\_width"}){$tag{width}=$params{"$field\_width"};}
				if(length($tag{mask})){
					my $mask=$tag{mask};
					if($tag{mask}=~/^Calendar$/is){
						$tag{inputtype}="date";
						$tag{calendar}=1;
						}
					}
				#Tag Value
				my $tagval='';
				if($params{_id}){
					if(length($Rec{0}{$field})){$tagval=$Rec{0}{$field};}
					elsif(length($input{$field})){$tagval=$input{$field};}
					}
				else{
					if(length($params{$field})){$tagval=$params{$field};}
					elsif(length($input{$field})){$tagval=$input{$field};}
					elsif(length($tag{defaultval})){$tagval=$tag{defaultval};}
					}
				if(length($tag{onchange}) && length($tagval)){
					my $onchange=$tag{onchange};
					while($onchange=~m/\$input\{(.+?)\}/sig){
						my $tag=$&;
						my $key=lc($1);
						$onchange=~s/\Q$tag\E/$input{$key}/is;
	                    }
	                while($onchange=~m/\$params\{(.+?)\}/sig){
						my $tag=$&;
						my $key=lc($1);
						$onchange=~s/\Q$tag\E/$params{$key}/is;
	                    }
	                $onchange=~s/addedit/$formname/sg;
	                $tag{onchange}=$onchange;
	                $onchange=~s/this\./document\.$formname\.$field\./sg;
	                $onchange=~s/addedit/$formname/sg;
					push(@onloads,$onchange);
	                }
     			if($field=~/^body$/is && $params{_table}=~/^\_(pages|templates)$/is){$tag{tvals}="drag html";}
				if($field=~/^publish$/is && length($tagval)){$publish=1;}
				if($meta{$index}{inputtype}=~/^formula$/is){
					$tag{inputtype}="textarea";
					$tag{height} ||= 150;
					$tag{width} ||= 325;
					$tag{displayname} ||=ucfirst($field);
					$tag{displayname} .= qq| <i>(Formula)</i>|;
					}
				#$rtnstr .= qq|<!-- Field: $field, Val:$tagval, Rec:$Rec{0}{$field}, Input:$input{$field}, Default:$tag{defaultval} -->\n|;
				#focusfield
				if(length($focusfield)==0){
					if($params{_focus} && $params{_focus}=~/^\Q$field\E$/is){$focusfield=$params{_focus};}
					elsif(length($tag{inputtype})==0 || $tag{inputtype}=~/text/is){$focusfield=$tag{fieldname};}
					}
				my $dname=$tag{displayname};
				if(!length($dname)){
					$dname=$field;
					$dname=~s/\_/\ /sg;
					#capitalize the first letter of each word
					$dname=capitalize($dname);
					}

				#my $hval=encodeHtml($val);
				my $nowrap="nowrap";
				if($params{_viewonly}==1 || $params{_viewonly}=~/^$field$/is || $params{_readonly}=~/^$field$/is){
					$nowrap='';
                    if($meta{$index}{width}){$nowrap="style=\"width:$meta{$index}{width}" . "px\"";}
					}
				$rtnstr .= qq|\t\t<td $nowrap id="\_$field\_" class="w_formtext">|;
				#Required
				if($meta{$index}{required}==1 || $params{_required}=~/$field/is){
					$rtnstr .= qq|<span class="w_red">*</span> |;
					$tag{required}=1;
					}
				#Help or not Help
				if($meta{$index}{help} && (!length($params{_help}) || $params{_help}!~/^(off|0|false)$/is)){
					$rtnstr .= qq|<div _behavior="dropdown" id="$field\_hlp" class="w_drop w_helpbox">$meta{$index}{help}</div>|;
					$rtnstr .= qq|<nobr><span class="w_formlabel w_help" title="Click for more information" onClick="showDrop('$field\_hlp');">$dname</span></nobr><br>|;
					}
				else{$rtnstr .= qq|<span class="w_formlabel">$dname</span><br>|;}
				my $ctag='';

				if($field=~/^\_(id|cdate|edate|adate|euser|cuser)$/is || $tag{inputtype}=~/hidden/is || $params{_viewonly}==1 || $params{_viewonly}=~/^$field$/is || $params{_readonly}=~/^$field$/is){
					$ctag=$tagval if $tag{inputtype}!~/hidden/is;
					my $cval=$tagval;
					if(($params{_viewonly}==1 || $params{_viewonly}=~/^$field$/is || $params{_readonly}=~/^$field$/is || $params{_tidy}=~/^$field$/is)){
						$ctag=$tagval;
						$ctag=~s/\r\n/<br>/sg;
	                         }
					#Hide the value in hidden input fields
     				if(!defined $Formfield{$field}){
						$Formfield{$field}=1;
						if($tag{inputtype}=~/textarea/is || $cval=~/[\<\>\"]/s){
							$cval=encodeHtml($cval);
							$ctag .= qq|<textarea style="display:none;" name="$field">$cval</textarea>\n|;
							}
						else{
		                    $ctag .= qq|<input type="hidden" name="$field" value="$cval">|;
		    				}
						}
					}
				else{$ctag = &buildTag(\%tag,$tagval);}
				$Formfield{$field}=1;
				$rtnstr .= $ctag;
	            $rtnstr .= qq|</td>\n|;
	            }
			$rtnstr .= qq|\t</tr>\n|;
			$rtnstr .= qq|</table>\n\n|;
			}
		}

     #$rtnstr .= qq|</td></tr><tr valign="top" align="center"><td>\n|;
	#Submit buttons and _action
	if($params{_viewonly}!=1){
		$rtnstr .= qq|<div style="margin-top:5px;">\n|;
		$rtnstr .= qq|<input type="hidden" name="_action" value="">\n|;
		my $button="Save";
		if(length($params{_button})){$button=$params{_button};}
		#abort($rtnstr);
		my $class=$params{_class} || $params{_class_class}|| "w_formsubmit";
		if($params{_button_style}){$class=qq|$class" style="$params{_button_style}|;}
		if(length($params{_id})){
			$fieldstr=~s/\'//sg;
			$rtnstr .= qq|\t<input type="hidden" name="_id" value="$params{_id}">\n| if !$Formfield{_id};
			$rtnstr .= qq|\t<input type="hidden" name="_fields" value="$formfieldlist">\n|;
			#Save
			$rtnstr .= qq|\t<input onClick="document\.$formname\._action.value='Edit';" type="submit" class="$class" value="$button" title="Edit this record">\n  |  if $params{_hide}!~/edit/is;
			#Clone
			$rtnstr .= qq|\t<input type="submit" class="$class" value="Save as new" title="Save as a new record" onClick="document\.$formname._id.value='';document\.$formname\._action.value='Add';">\n|  if $params{_hide}!~/clone/is;
			#Delete
			$rtnstr .= qq|\t<input type="submit" class="$class" title="Delete this record" value="Delete"  onClick="var x=confirm('Delete this record?');if(x){document\.$formname\._action.value='Delete';return true;}else{return false;}">\n| if $params{_hide}!~/delete/is;
			#Reset
			$rtnstr .= qq|\t<input type="reset" class="$class" value="Reset" title="Reset the form back to what it was when you brought up the form.">\n|  if $params{_hide}!~/reset/is;
			my @tmps=qw(_id _fields _action);
			foreach my $tmp (@tmps){$Formfield{$tmp}=1;}
			}
		else{
			#Save
			$rtnstr .= qq|\t<input onClick="document\.$formname\._action.value='Add';" type="submit" class="$class"  value="$button" title="Create a new record in the database with the data entered in this form">\n|  if $params{_hide}!~/add/is;
			#Reset
			$rtnstr .= qq|\t<input class="$class" type="reset" value="Reset"  title="Clear the form data entered.">\n|  if $params{_hide}!~/reset/is;
			$Formfield{_action}=1;
			}

		#Cancel Button
		$rtnstr .= qq|\t<input class="$class" onClick="if(document.referrer.length){window.location=document.referrer;}else{history.back(1);}" type="button" value="Cancel"  title="Cancel this request.">\n|  if $params{_hide}!~/cancel/is;
		#View and Publish buttons for _pages table
		if($params{_table}=~/^\_pages$/is && length($params{_id})){
			#View
			$rtnstr .= qq|\t<input class="$class" type="button"  style="font-family:arial" title="View page" value="View \&\#186\;\&\#152\;\&\#186\;" onClick="window.open('$ENV{SCRIPT_NAME}\?$params{_id}','_view','width=640,height=480,location=1,status=1,scrollbars=1');">\n|  if $params{_hide}!~/view/is;
			if($publish){
				#Publish
				$rtnstr .= qq|\t<input onClick="document\.$formname\._action.value='Publish';"  title="Publish this page as a file with the name in the Publish Page Name field" type="submit" class="$class" value="Publish">\n|  if $params{_hide}!~/publish/is;
				$Formfield{_action}=1;
				}
			}
	     $rtnstr .= qq|</div>\n|;
		#Add any other params as hidded fields
		foreach my $key (sort(keys(%params))){
			$key=lc($key);
			next if $key=~/^\_(onsubmit|hide|button)$/is;
			next if $Formfield{$key}==1;
			my $val=$params{$key};
			next if length($val)==0;
			$val=encodeHtml($val);
			$rtnstr .= qq|\t<input from="params" type="hidden" name="$key" value="$val">\n|;
			}
		$rtnstr .= qq|</div>\n|;
		$rtnstr .= qq|</form><br>\n|;
		if($ENV{_perlcheck}==1 || $params{_table}=~/^\_(pages|templates)$/is){
			$rtnstr .= qq|<form method="post" name="_perlcheck" action="$cgiroot" enctype="application/x-www-form-urlencoded">\n|;
			$rtnstr .= qq|<input type="hidden" name="_view" value="0">\n|;
			$rtnstr .= qq|<textarea style="display:none" name="perlcheck"></textarea>\n|;
			$rtnstr .= qq|</form>\n|;
			}	
		#Set focus
		if($params{_focus}=~/(none|off)/is){}
		elsif(length($focusfield)){$rtnstr .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="document\.$formname\.$focusfield\.focus();">\n|;}
		#Inititalize dropdowns
		$rtnstr .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" id="initialize" onLoad="initDrop();">\n|;
		#onloads
		my $onloadcnt=@onloads;
		if($onloadcnt){
			my $onloadstr=join(";",@onloads);
			$rtnstr .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" id="initialize" onLoad="$onloadstr">\n|;
	        }
		}

	return $rtnstr;
	}
###########
sub buildHash{
	#usage:my $val=buildHash(\%myhash,*shash,$index);
	#info: builds a single key/value hash from a multi-dim hash using record $index
	my $hash=shift;
	my $shash=shift;
	my $index=shift;
	if(length($index)==0){return 0;}
	my @fields=@{$hash->{fields}};
	%{$shash}=();
	my $cnt=0;
	foreach my $field (@fields){
		next if $field=~/^\_/s;
		my $val=strip($hash->{$index}{$field});
		#print "hash->{$index}{$field}=$val\n";
		next if length($val)==0;
		$field=lc($field);
		$shash->{$field}=$hash->{$index}{$field};
		#print "shash->{$field}=$shash->{$field}\n";
		$cnt++;
		}
	return $cnt;
	}
###########
sub listData{
    #usage: listData(_sql=>$sql);
	#usage: or ListData(_table=>$table,field=>value,field2=>value2...);
	#usage: or ListData($table,field=>value,field2=>value2...);
	#info:  Displays results of $sql or $table in a table format.
	my @inpairs=@_;
	my %params=@inpairs;
	my ($table,$sql,$listtype)=('','',0);
	my @searchpairs=();
	my $rtnstr='';
	if(length($params{_sql})){
		$sql=$params{_sql};
		$listtype=1;
    	}
    elsif($inpairs[0]=~/^\_table$/is){
        shift(@inpairs);
        $table=shift(@inpairs);
    	}
	elsif(isDBTable($inpairs[0])){
		$table=shift(@inpairs);
    	}
    if(!length($sql) && !length($table)){return "listData Error:no table or sql defined";}
    #build search pairs from the remaining inpairs
    my %Ftype=();
    my %SearchField=();
    my @fields=();
    # Offset
	my $offset=0;
	if(length($input{_offset})){$offset=$input{_offset};}
    if($table){
		getDBFieldTypes(\%Ftype,$table);
		#what fields to return
		@fields=keys(%Ftype);
		if(length($params{_custom})){@fields=sort(@fields);}
		elsif($params{_listfields}){
			if(isHtml($params{_listfields})){
            	@fields=sort(@fields);
                $params{_custom}=$params{_listfields};
                }
			else{@fields=split(/[\s\,\:\;]+/,$params{_listfields});}
        	}
        else{
			my $listfield="listfields_mod";
			if(length($USER{_id}) && $USER{utype}==0){$listfield="listfields";}
			my $listfields=getDBFieldValue("_tabledata",$listfield,tablename=>$table);
			if(length($listfields)){
				if(isHtml($listfields)){
                    @fields=sort(@fields);
                    $params{_custom}=$listfields;
                	}
                else{@fields=split(/[\s\,\:\;]+/,$listfields);}
            	}
        	}
        my @valid=();
        my $hasid=0;
        foreach my $field (@fields){
			next if !defined $Ftype{$field};
			if($field=~/^_id$/is){$hasid++;}
			if($Ftype{$field}=~/^(date|datetime)$/is && $dbt=~/mysql/is){
				if($params{"$field\_dateformat"}){
					my $format=$params{"$field\_dateformat"};
					push(@valid,"DATE_FORMAT($field,'$format') as $field");
					}
				elsif($params{"$field\_unix"}){
                    push(@valid,"UNIX_TIMESTAMP($field) as $field");
                	}
				else{
					if(isNum($params{_custom}) || $params{_unix}==1){push(@valid,"UNIX_TIMESTAMP($field) as $field\_utime");}
					push(@valid,$field);
					}
				}
			else{push(@valid,$field);}
        	}
        if(!$hasid){unshift(@valid,'_id');}
        if(scalar @valid ==0){@valid=keys(%Ftype);}
        @fields=@valid;
        my $listfieldstr=join(',',@fields);
		$sql=qq|select $listfieldstr from $table where 1=1|;
		if(length($params{_noresult}) && $params{_noresult}==1 && !$input{_noresult}){
			$sql=qq|select $listfieldstr from $table where 1=2|;
			}
		#where
		my @wheres=();
		my @pairs=@inpairs;
		if($input{_newsearch}){
			delete($input{_searchpairs});
            $offset=0;
			}
		if(length($input{_search}) && length($input{_searchfield})){
			push(@pairs,$input{_searchfield}=>$input{_search});
        	}
		if($input{_searchpairs}){
			my $nlc=$/;
			my @lines=split(/[$nlc\r\n]+/i, $input{_searchpairs});
			my $crumbcnt=0;
			foreach my $line (@lines){
				$line=strip($line);
				my ($field,$val)=split(/\=/,$line,2);
				$field=lc($field);
				next if !length($val);
				next if $field !~/^ALL$/is && !defined $Ftype{$field};
				my $searchcrc=encodeCRC(lc($field) . $val);
				if(!defined $SearchField{$searchcrc}){
					$SearchField{$searchcrc}=1;
					push(@searchpairs,$field=>$val);
					push(@wheres,$field=>$val);
					}
				if(isNum($input{_crumbindex}) && $crumbcnt==$input{_crumbindex}){last;}
				$crumbcnt++;
            	}
        	}
		while(scalar @pairs > 1){
			my $field=shift(@pairs);
			my $val=shift(@pairs);
			next if !length($val);
			next if $field =~/^_id$/is;
			next if $field !~/^ALL$/is && !defined $Ftype{$field};
			my $searchcrc=encodeCRC(lc($field) . $val);
			if(!defined $SearchField{$searchcrc}){
				$SearchField{$searchcrc}=1;
				push(@searchpairs,$field=>$val);
            	push(@wheres,$field=>$val);
            	$offset=0;
   				}         	
        	}
        #_filter params
        if(length($params{_filter})){
			$sql .= qq| and ($params{_filter})|;;
        	}
		while(scalar @wheres > 1){
			my $field=shift(@wheres);
			my $val=shift(@wheres);
			next if !length($val);
			if($field =~/^ALL$/is){
				$sql .= qq| and (1=2|;
				if($params{_or}){$sql .= qq| or ($params{_or})|;}
				my @sfields = sort(keys(%Ftype));
				if($params{_searchfields}){@sfields=split(/[\s\,\:\;]+/,$params{_searchfields});}
				foreach my $cfield (@sfields){
					my $ftype=$Ftype{$cfield};
					if($ftype=~/^(date|datetime)$/is){
						#date field
						if($val=~/^([0-9]{2,2})[\-\/]([0-9]{2,2})[\-\/]([0-9]{4,4})/s){
							#10/12/2004  or 10-12-2004  => 2004-10-12
							$val=$3 . '-' . $1 . '-' . $2;
							$sql .= qq| or $cfield='$val'|;
							}
						}
					elsif($ftype=~/^time$/is){
						my ($hr,$min,$sec);
						if($val=~/^([0-9]{1,2})\:([0-9]{1,2})(AM|PM)$/is){
				            ($hr,$min)=($1,$2);
				            if($3=~/^PM$/is){$hr=$hr+12;}
				            my $newval=$hr . ':' . $min . ':' . $sec;
				            $sql .= qq| or $cfield='$newval'|;
					        }
						elsif($val=~/^([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})(.*)/is){
				            ($hr,$min,$sec)=($1,$2,$3);
				            my $newval=$hr . ':' . $min . ':' . $sec;
				            $sql .= qq| or $cfield='$newval'|;
				            }
						}
					elsif($ftype=~/^(bit|tinyint|bigint|decimal|integer|smallint|float|real|number)$/is){
                    	if($val=~/^[0-9\.]+$/is){$sql .= qq| or $cfield=$val|;}
						elsif($val=~/^[0-9\.\,]+$/is){
							$val=~s/^\,+//s;
							$val=~s/\,+$//s;
							$sql .= qq| or $cfield in ($val)|;
							}
                    	}
                    else{
						#should be quoted
						if($val=~/^\"/s && $val=~/\"$/s){
							#value is in quotes "something" - look for exact match
							$val=~s/^\"//s;
							$val=~s/\"$//s;
							my $fval=prepDBFieldValue($ftype,$val);
							$sql .= qq| or $cfield like $fval|;
	                    	}
	                    else{
							my $fval=prepDBFieldValue($ftype,$val);
							$fval=~s/^\'/\'\%/s;
							$fval=~s/\'$/\%\'/s;
							$sql .= qq| or $cfield like $fval|;
							}
                    	}
                	}
                $sql .= qq|)|;
            	}
            else{
				my $ftype=$Ftype{$field};
				if($ftype=~/^(date|datetime)$/is){
					#date field
					if($val=~/^([0-9]{2,2})[\-\/]([0-9]{2,2})[\-\/]([0-9]{4,4})/s){
						#10/12/2004  or 10-12-2004  => 2004-10-12
						$val=$3 . '-' . $1 . '-' . $2;
						$sql .= qq| and $field='$val'|;
						}
					}
				elsif($ftype=~/^time$/is){
					my ($hr,$min,$sec);
					if($val=~/^([0-9]{1,2})\:([0-9]{1,2})(AM|PM)$/is){
			            ($hr,$min)=($1,$2);
			            if($3=~/^PM$/is){$hr=$hr+12;}
			            my $newval=$hr . ':' . $min . ':' . $sec;
			            $sql .= qq| and $field='$newval'|;
				        }
					elsif($val=~/^([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})(.*)/is){
			            ($hr,$min,$sec)=($1,$2,$3);
			            my $newval=$hr . ':' . $min . ':' . $sec;
			            $sql .= qq| and $field='$newval'|;
			            }
					}
				elsif($ftype=~/^(bit|tinyint|bigint|decimal|integer|smallint|float|real|number)$/is){
                    if($val=~/^[0-9\.]+$/is){
						$sql .= qq| and $field=$val|;
						}
					elsif($val=~/^[0-9\.\,]+$/is){
						$val=~s/^\,+//s;
						$val=~s/\,+$//s;
						$sql .= qq| and $field in ($val)|;
						}
                    }
                else{
					#should be quoted
					if($val=~/^\"/s && $val=~/\"$/s){
						#value is in quotes "something" - look for exact match
						$val=~s/^\"//s;
						$val=~s/\"$//s;
						my $fval=prepDBFieldValue($ftype,$val);
						$sql .= qq| and $field like $fval|;
                    	}
                    else{
						my $fval=prepDBFieldValue($ftype,$val);
						$fval=~s/^\'/\'\%/s;
						$fval=~s/\'$/\%\'/s;
						$sql .= qq| and $field like $fval|;
						}
                    }
            	}
        	}
    	}
	# Limit
	if(isNum($input{_limit}) && $input{_limit} > 0 && isNum($USER{_paging}) && $input{_limit} != $USER{_paging}){
		#set users _paging to this value
		$ok=editDBData("_users","_id=$USER{_id}",_paging=>$input{_limit});
    	}
	my $limit=$params{_limit} || $USER{_paging} || $input{_limit} || $Config{limit} || 15;
	
	#Order by
	my $order='';
	if(length($input{_order})){$order=$input{_order};}
	elsif(length($params{_order})){$order=$params{_order};}
	elsif(length($input{_manage}) || defined $input{'_m0'}){$order="_id";}
	if(length($order)){
		$sql=~s/order\ by\ .+//is;
		$sql .= qq| order by $order|;
		}
	#if $params{_sqlonly} just return the sql statement
	if(isNum($params{_sqlonly}) && $params{_sqlonly}==1){return $sql;}
	#Execute SQL
	my %alist=getDBRecords(-sql=>$sql,-limit=>$limit,-offset=>$offset);
	#if $params{_hashonly} just return the sql statement
	if(isNum($params{_hashonly}) && $params{_hashonly}==1){return %alist;}
	my $acnt=$alist{count};
	if($alist{-error}){
		$rtnstr .= qq|<b>listData Error</b>\n<p>$sql</p>\n<p>$alist{-error}</p>\n|;
		return $rtnstr;
		}
	#abort($acnt,$DBI::query);
	#get fields from query
	@fields=@{$alist{fields}};
	my $fieldcnt=@fields;
	if($fieldcnt==0){
		$rtnstr .= qq|<b>No Fields were returned to display.</b><p>$sql</p>\n|;
		return $rtnstr;
		}
	#$rtnstr .= qq|<!-- SQL Query\nSort Fields: $sfstr\nList Fields:$lfstr\nFields: @fields\n$sql\n-->\n|;
	#Get _fielddata for fields in Table
	my %Finfo=();
	my %flist=();
	my $fsql=qq|select * from _fielddata where tablename like '$table'|;
	my $rcnt=getDBData(\%flist,$fsql,"nocount=1");
	#$rtnstr .= qq|<!-- Getting FieldData [$rcnt] $fsql -->\n|;
	for(my $x=0;$x<$rcnt;$x++){
		my $field=$flist{$x}{fieldname};
		$Finfo{$field}{displayname}=$flist{$x}{displayname};
		$Finfo{$field}{editlist}=$flist{$x}{editlist};
		$Finfo{$field}{inputtype}=$flist{$x}{inputtype};
		$Finfo{$field}{help}=$flist{$x}{help};
		$Finfo{$field}{tvals}=$flist{$x}{tvals};
		$Finfo{$field}{mask}=$flist{$x}{mask};
		$Finfo{$field}{index}=$x;
		#$rtnstr .= qq|<!-- Finfo{$field}=$Finfo{$field}{displayname} -->\n|;
		}
	#Build a form for editing records when the _id is clicked
	my $formname=$params{_name} || "listdata";
	my $method=$params{_method} || "POST";
	#$method="GET";
	if($params{_divid}){$input{_divid}=$params{_divid};}
	my $divid=$input{_divid} || $formname . time();
	my $submitform="document.$formname.submit();";
	if(!length($input{_divid})){$rtnstr .= qq|<div id="$divid">\n|;}
	$rtnstr .= qq|<form class="w_form" name="$formname" method="$method" action="$ENV{SCRIPT_NAME}" style="margin:0px;padding:0px;" enctype="application/x-www-form-urlencoded"|;
	if(length($params{_ajax}) || length($input{ajaxrequestuniqueid})){
		$rtnstr .= qq| onSubmit="ajaxSubmitForm(this,'$divid');return false;">\n|;
		$rtnstr .= qq|<input type="hidden" name="_ajax" value="1">\n|;
		$submitform="ajaxSubmitForm(document.$formname,'$divid');return false;";
		}
	elsif(length($params{_onsubmit})){
		$rtnstr .= qq| onSubmit="$params{_onsubmit}">\n|;
		$submitform=$params{_onsubmit};
		}
	else{$rtnstr .= qq|>\n|;}
	$rtnstr .= qq|<input type="hidden" name="_dbname" value="$input{_dbname}">\n| if $input{_dbname};
	$rtnstr .= qq|<input type="hidden" name="_table" value="$table">\n|;
	my $idname=$params{_id} || "_id";
	$rtnstr .= qq|<input type="hidden" name="$idname" value="">\n|;
	$rtnstr .= qq|<input type="hidden" name="_crumbindex" value="">\n|;
	$rtnstr .= qq|<input type="hidden" name="_noresult" value="1">\n|;
	$rtnstr .= qq|<input type="hidden" name="_divid" value="$divid">\n|;
	$rtnstr .= qq|<input type="hidden" name="_host" value="$input{_host}">\n| if length($input{_host});
	$rtnstr .= qq|<input type="hidden" name="_template" value="$params{_template}">\n| if length($params{_template});
	$rtnstr .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n| if length($input{_m0});
	$rtnstr .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n| if length($input{_m1});
	$rtnstr .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n| if length($input{_m2});
	$rtnstr .= qq|<input type="hidden" name="_order" value="$order">\n|;
	if(length($input{_manage})){
		$rtnstr .= qq|<input type="hidden" name="_manage" value="$input{_manage}">\n|;
		}
	elsif($params{_view}){
		$rtnstr .= qq|<input type="hidden" name="_view" value="$params{_view}">\n|;
		}
	elsif($input{_view}){
		$rtnstr .= qq|<input type="hidden" name="_view" value="$input{_view}">\n|;
		}
	#Add any other params as hidded fields
	foreach my $key (sort(keys(%params))){
		next if $key=~/^\_/s;
		my $pval=$params{$key};
		$key=lc(strip($key));
		my $val=$input{$key} || $pval;
		next if length($val)==0;
		$val=encodeHtml($val);
		$rtnstr .= qq|<input type="hidden" name="$key" value="$val">\n|;
		}
	# Paging
	my $tcnt=$alist{tcount};
	$rtnstr .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0">\n|;
	$rtnstr .= qq|<tr style="font-size:11px;font-family:arial;" valign="bottom" align="center">|;
	#Previous button
	if($offset>0 && $offset < $tcnt){
		my $newoffset=int($offset-$limit);
		if($newoffset<0){$newoffset=0;}
		my $previmage=$params{_previmage} || "/wfiles/prev.gif";
		$rtnstr .= qq|<td><input title="Previous" alt="Previous" style="cursor:pointer" type="image" src="$previmage"  name="_submit" value="Prev" onClick="document.$formname\._offset.value=$newoffset; document.$formname\.$idname.value='';"></td>\n|;
		}
	#Search Form
	my $searchButton=$params{_searchbutton} || "Search";
	if(defined $params{_simplesearch}){
		$rtnstr .= qq|<input type="hidden" name="_searchfield" value="ALL">\n|;
		my $checked='';
		if($params{_newsearch}){$checked=" checked";}
		$rtnstr .= qq|<div style="display:none"><input type="checkbox" name="_newsearch" value="1" $checked></div>\n|;
		$params{_nocrumb}=1;
		$rtnstr .= qq|<td colspan="3" align="center" valign="top">|;
		#search
		$rtnstr .= qq|<table cellspacing="0" cellpadding="0" border="0"><tr>\n|;
		$rtnstr .= qq|<td><input  title="Enter your search criteria" type="text" name="_search" style="width:200px;font-size:9pt;font-family:arial;" maxlength="255" value="$input{_search}" onFocus="this.select();"></td>|;
		$rtnstr .= qq|<td><input  type="submit" class="w_formsubmit" name="_submit" style="font-size:8pt;font-family:arial;" value="$searchButton" onClick="document.$formname\.$idname.value='';"></td>\n|;
		$rtnstr .= qq|</tr></table>\n|;
		$rtnstr .= qq|</td>|;
		}
	elsif(!defined $params{_hidesearch}){
		$rtnstr .= qq|<td>|;
		$rtnstr .= qq|<select name="_searchfield" style="font-size:11px;font-family:arial;"><option value="ALL">Search All Fields</option>\n|;
		foreach my $field (@fields){
			my $dname=$Finfo{$field}{displayname} || capitalize($field);
			$rtnstr .= qq|<option value="$field" title="$field"|;
			#if($input{_searchfield} && $input{_searchfield}=~/^\Q$field\E$/is){$rtnstr .= qq| selected|;}
			$rtnstr .= qq|>$dname</option>\n|;
			}
		$rtnstr .= qq|</select>\n|;
		$rtnstr .= qq|</td><td>|;
		#search
		$rtnstr .= qq|<input  title="Enter your search criteria" type="text" name="_search" style="width:150px;font-size:11px;font-family:arial;" maxlength="255" value="">|;
		$rtnstr .= qq|</td><td>|;
		$rtnstr .= qq|\&nbsp\;<input  type="submit" class="w_formsubmit" name="_submit" style="font-size:11px;font-family:arial;" value="$searchButton" onClick="document.$formname\.$idname.value='';">\n|;
		$rtnstr .= qq|</td>|;
		}
     else{
		$rtnstr .= qq|<td colspan="3"><img src="/wfiles/clear.gif" border="0" width="100" height="2"></td>|;
	     }
	#next
	if(($offset+$acnt) < $tcnt){
		my $newoffset=int($offset+$limit);
		my $nextimage=$params{_nextimage} || "/wfiles/next.gif";
		$rtnstr .= qq|<td><input title="Next" alt="Next" style="cursor:pointer" type="image" src="$nextimage" name="_submit" value="Next" onClick="document.$formname\._offset.value=$newoffset; document.$formname\.$idname.value='';"></td>\n|;
		}
	$rtnstr .= qq|</tr>\n|;
	if(!defined $params{_simplesearch} && !defined $params{_hidesearch}){
		$rtnstr .= qq|<tr style="font-size:10px;font-family:arial;" valign="bottom" align="center"><td colspan="5" nowrap="true">|;
		#limit
		$rtnstr .= qq|Show <input title="How many records to show at a time" type="text" name="_limit" style="width:30px;font-size:11px;font-family:arial;margin:1px;border:0px;border-left:1px dashed #6699CC;border-right:1px dashed #6699CC;border-bottom:1px solid #6699CC;text-align:center;" onFocus="this.select();" maxlength="3" value="$limit">|;
		$rtnstr .= qq| Records starting at |;
		#offset
		$rtnstr .= qq|<input type="text" name="_offset" style="font-size:11px;font-family:arial;margin:1px;border:0px;border-left:1px dashed #6699CC;border-right:1px dashed #6699CC;border-bottom:1px solid #6699CC;text-align:center;" onFocus="this.select();" size="4" maxlength="15" value="$offset">|;
		my $newsearch='';
		if($params{_newsearch}==1){$newsearch=' checked';}
		$rtnstr .= qq| <input type="checkbox" name="_newsearch" value="1" title="Search within resultset" $newsearch> new search|;
		$rtnstr .= qq|</td></tr>\n|;
		}
	else{
		$rtnstr .= qq|<input type="hidden" name="_offset" value="$offset">|;
		$rtnstr .= qq|<input type="hidden" name="_limit" value="$limit">|;
	     }
	$rtnstr .= qq|<tr style="font-size:10px;" align="center"><td colspan="5" id="w_showing">|;
	my $ocnt=$offset+$acnt;
	if($tcnt){
		$rtnstr .= qq|Showing $offset \- $ocnt of <b>$tcnt records found</b>|;
		}
	$rtnstr .= qq|</td></tr>\n|;
	$rtnstr .= qq|<tr style="font-size:10px;" align="center"><td colspan="5">|;
	#_sql
	$input{_sql}=$sql;
	$rtnstr .= qq|<div style="display:none;" id="_sql">$sql</div>\n|;
	#_searchpairs  crumb
	my @crumbs=();
    $rtnstr .= qq|<div style="display:none;" id="searchpairs">\n|;
	$rtnstr .= qq|<textarea name="_searchpairs">\n|;
	my $crumbcnt=0;
	while(scalar @searchpairs > 1){
		my $field=shift(@searchpairs);
		my $val=shift(@searchpairs);
		next if !length($val);
		$field=lc($field);
		$rtnstr .= qq|$field=$val\r\n|;
		my $jval=$val;
		$jval=~s/\'/\\\'/sg;
		my $crumblink=qq|<td style="font-size:8pt;"><a class="w_crumb" href="#" onClick="document.$formname._crumbindex.value=$crumbcnt\;document.$formname._searchfield.value='$field';document.$formname._search.value='$jval';$submitform;return false;">$field=$val</a></td>|;
		push(@crumbs,$crumblink);
		$crumbcnt++;
    	}
	$rtnstr .= qq|</textarea>\n|;
	$rtnstr .= qq|</div>\n|;
	my $crumb_divider=qq|<td><img src="/wfiles/crumb.gif" border="0"></td>|;;
	if(!defined $params{_nocrumb} && scalar @crumbs){
		$rtnstr .= qq|<div class="w_crumbs" align="center">\n|;
		$rtnstr .= qq|<table cellspacing="0" cellpadding="0" border="0"><tr>|;
		$rtnstr .= qq|<td><a class="w_crumb" href="#" onClick="document.$formname._newsearch.checked=true\;document.$formname._searchfield.value='$field';document.$formname._search.value='';$submitform;return false;">clear</a></td>|;
		$rtnstr .= qq|<td><img src="/wfiles/crumb.gif" class="w_crumbs_symbol" border="0" title="Search Crumbs. Click on a crumb to go back to that point"></td>|;

        $rtnstr .= join($crumb_divider,@crumbs);
        $rtnstr .= "</tr></table>\n";
		$rtnstr .= "</div>\n";
		}
	$rtnstr .= qq|</td></tr>\n|;
	$rtnstr .= qq|</table>\n|;
	
	#_noresult
	if(length($params{_noresult}) && $params{_noresult}==1 && !$input{_noresult}){
		$rtnstr .= qq|</form>\n<br>\n|;
		#Set focus on search form.
     	$rtnstr .= qq|<img src="/wfiles/clear.gif" border="0"  onLoad="document\.$formname\._search.select();">\n| if !defined $params{_hidesearch};
     	if(!length($input{_divid})){$rtnstr .= qq|</div>\n|;}
		return $rtnstr;
		}
	#Return if No records found
	if($acnt==0){
		$rtnstr .= qq|</form>\n<br>\n|;
		#Set focus on search form.
		my $nomsg=$params{_nomsg} || "No Records Found";
     	$rtnstr .= qq|<img src="/wfiles/clear.gif" border="0"  onLoad="document\.$formname\._search.select();">\n| if !defined $params{_hidesearch};
		$rtnstr .= qq|<b>$nomsg</b>\n|;
		if(!length($input{_divid})){$rtnstr .= qq|</div>\n|;}
		return $rtnstr;
		}
	#Custom html results template
	if(length($params{_custom})){
		$rtnstr .= "\n";
		my $customcode=$params{_custom};
		if(isNum($customcode)){
			#if custom is a number, read in that page as the custom value
			$customcode=getDBFieldValue("_pages","body",_id=>$customcode);
        	}
        #$rtnstr .= "<pre><xmp>$custom</xmp></pre><hr>fields: @fields<hr>\n";
        local %row=();
        local %custom=();
        foreach my $key (keys(%params)){$custom{$key}=$params{$key};}
        $custom{count}=$alist{count};
		$custom{tcount}=$alist{tcount};
		$custom{formname}=$formname;
		$custom{offset}=$offset;
		$custom{sql}=$alist{sql};
		$custom{'fields'}=$alist{'fields'};
		#header
        while($customcode=~m/<header>(.+?)<\/header>/sig){
			my $hcode=$1;
			my $tag=$&;
			$rtnstr .= evalPerl($hcode);
			$customcode=~s/\Q$tag\E//s;
        	}
        #footer
        my $footer='';
        while($customcode=~m/<footer>(.+?)<\/footer>/sig){
			my $hcode=$1;
			my $tag=$&;
			$footer .= $hcode;
			$customcode=~s/\Q$tag\E//s;
        	}
        #rows
        for(my $x=0;$x<$acnt;$x++){
			%row=();
			$row{x}=$x;
			my $crow=$customcode;
            foreach my $field (@fields){
				$row{$field}=$alist{$x}{$field};
            	}
            $crow=evalPerl($crow);
            $rtnstr .= $crow;
        	}
        #add footers
        $rtnstr .= evalPerl($footer);
        #clean up
        undef(%custom);
        undef(%row);
        $rtnstr .= qq|\n</form>\n|;
        $rtnstr .= qq|<img src="/wfiles/clear.gif" border="0"  onLoad="document\.$formname\._search.select();">\n| if !defined $params{_hidesearch};
        if(!length($input{_divid})){$rtnstr .= qq|</div>\n|;}
        return $rtnstr;
    	}
	#Show Data results
    if($params{_pre}){$rtnstr .= qq|<div align="center">$params{_pre}</div>\n|;}
	$rtnstr .= qq|<table class="w_table" cellspacing="0" cellpadding="1" border="1"|;
	if($params{_tablestyle}){$rtnstr .= qq| style="$params{_tablestyle}"|;}
	elsif($params{_tablewidth}){$rtnstr .= qq| width="$params{_tablewidth}"|;}
	$rtnstr .= qq|>\n|;
	#$rtnstr .= header row
	$rtnstr .= qq|<tr align="center">\n|;
	foreach my $field (@fields){
		next if $params{"$field\_hide"};
		my $dname;
		my $arrow='';
		if($field=~/^\_id$/is){$dname=$params{_id} || "ID";}
		else{$dname = $params{"$field\_title"} || $Finfo{$field}{displayname} || capitalize($field);}
		my $title = $Finfo{$field}{help} || "Click to sort by $field";
		$rtnstr .= qq|<th title="$title" style="|;
		if($params{_headerstyle}){$rtnstr .= $params{_headerstyle};}
		$rtnstr .= qq|" nowrap>|;
		my $orderby=$field;
		if($order=~/^$field\ desc$/is){
			$arrow = qq|<img src="/wfiles/up.gif" title="order by $order" border="0" width="11" height="6">|;
			}
		elsif($order=~/^$field$/is){
			$orderby .= " desc";
			$arrow = qq|<img src="/wfiles/down.gif" title="order by $order" border="0" width="11" height="6">|;
			}
		$rtnstr .= qq|<input type="button" style="cursor:pointer;border:0px;background-color:#6699CC;color:#FFFFFF;" onClick="document.$formname\._order.value='$orderby'; document.$formname\.$idname.value='';$submitform|;
		if($params{_onsubmit}){
			my $onsubmit=$params{_onsubmit};
			$onsubmit=~s/this/document.$formname/sg;
			$rtnstr .= qq|$onsubmit|;
			}
		$rtnstr .= qq|" value="$dname">|;
		$rtnstr .= qq|$arrow</th>|;
		}
	$rtnstr .= qq|</tr>\n|;
	#$rtnstr .= data rows
	my $editlistcnt=0;
	$params{_oddcolor} ||= '#F9F8F2';
	for(my $x=0;$x<$acnt;$x++){
		my $bgcolor='#FFFFFF';
		if($params{_oddcolor} && !isEven($x)){$bgcolor=$params{_oddcolor};}
		if($params{_evencolor} && isEven($x)){$bgcolor=$params{_evencolor};}
		$rtnstr .= qq|<tr style="font-size:12px;font-family:arial" bgcolor="$bgcolor" valign="top">\n|;
		foreach my $field (@fields){
			next if $params{"$field\_hide"};
			my $val='';
			if($Finfo{$field}{inputtype}=~/^password$/is){
				my $len=length($alist{$x}{$field});
				$val="*"x$len;
               	}
			elsif($Finfo{$field}{inputtype}=~/^formula$/is){
				my $evalstr=$Finfo{$field}{tvals};
				foreach my $xfield (@fields){
					my $rval=$alist{$x}{$xfield};
					$evalstr=~s/\<$xfield\>/$rval/sig;
					}
				my $result=eval($evalstr);
				if(!$@){$val=encodeHtml($result);}
				else{$val=qq|<span title="Formula Error">Err</span>|;}
				}
			else{
				$val=$alist{$x}{$field};
				if($Finfo{$field}{mask}=~/^link$/is){

					my $link='';
					if($val=~/^\\\\/s){$link = qq|<a href="file\:$val" target="_new">$val</a>|;}
					else{$link = qq|<a href="$val" target="_new">$val</a>|;}
					$val=$link;
					}
				else{$val=encodeHtml($val);}
				if(length($search) && (length($input{_searchfield})==0 || $input{_searchfield}=~/^(ALL|$field)$/is)){
					if($val=~/\</){}
					else{
						my @finds=();
						my $fcnt=0;
						while($val=~/$search/sig){
							my $str=$&;
							my $hsearch=qq|<span style="background-color:#ffffcc">$str</span>|;
							push(@finds,$hsearch);
							$val=~s/\Q$str\E/\[S\-$fcnt\-S\]/is;
							$fcnt++;
							}

						for(my $f=0;$f<$fcnt;$f++){
							$val=~s/\[S\-$f\-S\]/$finds[$f]/is;
							}
						}
					}
				}
			if($params{"$field\_eval"}){
				#eval
				my $evalstr=$params{"$field\_eval"};
				foreach my $fieldx (@fields){
					next if $fieldx=~/^\_sql$/is;
					my $valx=$alist{$x}{$fieldx};
					$evalstr=~s/\Q%$fieldx%\E/$valx/sig;
					}
				$val=eval($evalstr);
				}

			my $align="right";
			if(length($val) > 100){$align="left";}
			if($params{"$field\_align"}){$align=$params{"$field\_align"};}
			elsif($params{_align}){$align=$params{_align};}
			my $class="";
			my $classtr='';
               if($params{"$field\_class"} || $params{_class}){
				$class=$params{"$field\_class"} || $params{_class};
				foreach my $fieldx (@fields){
					next if $fieldx=~/^\_sql$/is;
					my $valx=$alist{$x}{$fieldx};
					$class=~s/\Q%$fieldx%\E/$valx/sig;
					}
                    }
               if(length($class)){$classtr=qq|class="$class"|;}
			if(defined $params{"$field\_nowrap"} && $params{"$field\_nowrap"}==1){$classtr .= " nowrap";}
			if($field=~/^\_id$/is){
				my $id=$alist{$x}{_id};
				my $onclick='';
				if(length($params{_onclick})){
					$onclick=$params{_onclick};
					foreach my $fieldx (@fields){
						next if $fieldx=~/^\_sql$/is;
						my $valx=$alist{$x}{$fieldx};
						$onclick=~s/\Q%$fieldx%\E/$valx/sig;
						}
				#	$rtnstr .= qq|<td align="$align" onClick="$onclick" style="cursor:pointer" title="$onclick">$id</td>|;
					}
				if($params{_edit}=~/^(0|off)$/is){
					$rtnstr .= qq|<td align="$align" $classtr>$id</td>|;
					}
				else{
					$rtnstr .= qq|<td align="$align" $classtr>|;
					#Ajax?
					#ajaxSubmitForm(this,'$divid');return false;
					$rtnstr .= qq|<input  type="submit" class="w_formsubmit" onClick="document\.$formname\.$idname\.value=this\.value;" value="$id" title="Click to select this record" style="cursor:pointer;border:0px;background-color:transparent;font-size:12px;"|;
					if(length($onclick)){$rtnstr .= qq| onClick="$onclick"|;}
                                        $rtnstr .= qq|>|;
					$rtnstr .= qq|</td>|;
					}
				}
			elsif($params{"$field\_url"}){
				#URL address
				my $url=$params{"$field\_url"};
				foreach my $fieldx (@fields){
					next if $fieldx=~/^\_sql$/is;
					my $valx=$alist{$x}{$fieldx};
					$url=~s/\Q%$fieldx%\E/$valx/sig;
					}
				$rtnstr .= qq|<td align="$align" $classtr><a class="w_link" href="$url">$val</a></td>|;
				}
			elsif($params{"$field\_onclick"}){
				#URL address
				my $onclick=$params{"$field\_onclick"};
				foreach my $fieldx (@fields){
					next if $fieldx=~/^\_sql$/is;
					my $valx=$alist{$x}{$fieldx};
					$onclick=~s/\Q%$fieldx%\E/$valx/sig;
					}
				$rtnstr .= qq|<td align="$align" $classtr><a class="w_link" href="#" onClick="$onclick">$val</a></td>|;
				}
			elsif($params{_editlist} && $Finfo{$field}{editlist}==1){
				#Editlist field - allow user to modify on the fly.
				#Fieldname: tablename--fieldname--id
				my $id=$alist{$x}{_id};
				my $fname=qq|$table\_$field\_$id|;
				my $fname_old = $fname . "_old";
				#Update?
				if($input{_editlist}=~/^Update$/is){
					my $newval=$input{$fname};
					my $oldval=$input{$fname_old};
					if(length($newval)==0 && length($oldval) > 0){
						my $ck=editDBData($table,"_id=$id",$field=>'NULL');
						$val='';
						}
					elsif(length($oldval)==0 && length($newval) > 0){
						my $ck=editDBData($table,"_id=$id",$field=>$newval);
						$val=$newval;
						}
					elsif(length($newval) && length($oldval) && $newval !~ /^\Q$oldval\E$/is){
						my $ck=editDBData($table,"_id=$id",$field=>$newval);
						$val=$newval;
						}
					}
				#incriment editlist counter
				$editlistcnt++;
				#build appropiate form tag for this field
				my %tag=();
				my $ck=&buildHash(\%flist,\%tag,$Finfo{$field}{index});
				$tag{fieldname}=$fname;
				$rtnstr .= qq|<td align="center" bgcolor="#ffffcc" $classtr>|;
				$rtnstr .= qq|<input type="hidden" name="$fname_old" value="$val">|;
				$rtnstr .= buildTag(\%tag,$val,0,$formname);
				$rtnstr .= qq|</td>|;
				}
			elsif($val=~/^.{1,25}\@.{1,50}\..{2,6}$/s){
				#email address
				$rtnstr .= qq|<td align="$align" $classtr><a href="mailto:$alist{$x}{$field}">$val</a></td>|;
				}
			elsif($val=~/^(http|https|file)\:\/\//is){
				#URL address
				my $hval=encodeHtml($alist{$x}{$field});
				$rtnstr .= qq|<td align="$align" $classtr><a href="$hval">$val</a></td>|;
				}
			elsif($params{"$field\_link"}==1){
				#URL address
				my $hval=encodeHtml($alist{$x}{$field});
				if($hval!~/^http/is && $hval!~/^\//s){$hval="http://" . $hval;}
				$rtnstr .= qq|<td align="$align" $classtr><a class="w_link" href="$hval">$val</a></td>|;
				}
			elsif($params{"$field\_image"}==1){
				#URL address
				my $img=$alist{$x}{$field};
				$rtnstr .= qq|<td align="$align" $classtr><img src="$img" border="0"></td>|;
				}
			elsif($params{"$field\_check"}==1 && isNum($alist{$x}{$field})){
				#URL address
				if($alist{$x}{$field}==0){$rtnstr .= qq|<td align="$align" $classtr></td>|;}
				else{$rtnstr .= qq|<td align="$align" $classtr><img src="/wfiles/check.gif" border="0"></td>|;}

				}


			else{$rtnstr .= qq|<td align="$align" $classtr>$val</td>|;}
			}
		$rtnstr .= qq|</tr>\n|;
		}

	$rtnstr .= qq|</table>\n|;
	if($editlistcnt){
		$rtnstr .= qq|<input  type="submit" class="w_formsubmit" name="_editlist" value="Update" style="font-size:11px;">\n|;
		}
	$rtnstr .= qq|</form>\n|;
	if(!length($input{_divid})){$rtnstr .= qq|</div>\n|;}
    #Set focus on search form.
    $rtnstr .= qq|<img src="/wfiles/clear.gif" border="0"  onLoad="document\.$formname\._search.select();">\n| if !defined $params{_hidesearch};
	return $rtnstr;
	}
###########
sub ListData{
	#left for backwards compatibility
	return listData(@_);
	}
###########
sub publishData{
	#published a page to a static file. returns name of file published or error msg on failure.
	my $table=shift || return;
	my $id=shift || return;
	my $file=shift || return;
	#publish the records
	my $path=$ENV{DOCUMENT_ROOT};
	my $fullname="$path/$file";
	$fullname=~s/\/+/\//sg;
	if(open(PF,">$fullname")){
		my @params=();
		foreach my $key (keys(%input)){
			push(@params,$key=>$input{$key});
        	}
		$input{_view}=$id;
		my $view=&viewPage($id,@params);
		$input{_view}='';
		print PF $view;
		close(PF);
		return $fullname;
		}
	return $! . " / " . $^E;
	}
###########
sub RunSQL{
	my $sql=shift;
	$sql=strip($sql);
	if(length($sql)==0){return "No SQL";}
	my $rtn;
		if($sql=~/^show tables$/is){
			my @tables=getDBTables();
			my %list=();
			my $cnt=@tables;
			for(my $x=0;$x<$cnt;$x++){
				$list{$x}{tablename}=$tables[$x];
				}
			$rtn .= hash2Html(\%list,tablename_onclick=>"document.commandForm._runsql.value='show fields \%tablename\%';ajaxSubmitForm(document.commandForm,'CommandResults');document.getElementById('CommandResults').scrollTop=99999");
			}
		elsif($sql=~/^show fields (.+)/is){
			my $table=strip($1);
			my %list=();
			my $cnt=getDBFieldTypes(\%list,$table);
			#$rtn .= "cnt=$cnt<br>\n";
			my %xlist=();
			my $cnt=0;
			foreach my $field (keys(%list)){
				$xlist{$cnt}{fieldname}=$field;
				$xlist{$cnt}{fieldtype}=$list{$field};
				$cnt++;
				}
			$rtn .= hash2Html(\%xlist,fields=>[fieldname,fieldtype]);
			}
		elsif($sql=~/^show schema$/is){
               my @tables=getDBTables();
			my %list=();
			my $cnt=0;
			foreach my $table (sort(@tables)){
				my $schema=getTableSchema($table);
				$list{$cnt}{tablename}=$table;
				$list{$cnt}{schema}=qq|<pre>$schema</pre>|;
				$cnt++;
				}
			$rtn .= hash2Html(\%list,fields=>[tablename,schema]);
			}
		elsif($sql=~/^show index$/is){
			if($dbt=~/^mysql/is){
				my @tables=getDBTables();
				my %list=();
				my $cnt=0;
				foreach my $table (sort(@tables)){
					my %clist=();
					my $cnt=getDBData(\%clist,"show index from $table");
					if(isNum($cnt)){
						my @cfields=@{$clist{fields}};
						for(my $y=0;$y<$cnt;$y++){
							foreach my $cfield (@cfields){
							     $list{$cnt}{$cfield}=$clist{$y}{$cfield};
								}
							}
						$cnt++;
						}
					}
				$rtn .= hash2Html(\%list);
				}
			else{
				my %list=();
				my $sql=qq|select name,tbl_name as tablename,rootpage from sqlite_master where type like 'index'|;
				my $cnt=getDBData(\%list,$sql,"nocount=1");
				if(!isNum($cnt)){$rtn .= qq|SQL Error<br>$cnt<br>$sql<br>\n|;}
				else{$rtn .= hash2Html(\%list);}
				}
			}
		elsif($sql=~/^select\ /is || ($dbt=~/sqlite/is && $sql=~/^explain\ /is) || ($dbt=~/mysql/is && $sql=~/^(desc|explain|show)\ /is)){
			$cnt++;
			my %list=();
			my $cnt=getDBData(\%list,$sql,"nocount=1");
			if(!isNum($cnt)){$rtn .= qq|SQL Error<br>$cnt<br>$sql<br>\n|;}
			else{$rtn .= hash2Html(\%list);}
			}
		elsif($sql=~/^drop table (\_.+)\ /is){
			my $itable=$1;
			$rtn .= "<br>Error:</b>$sql<br>Tables that begin with an underscore are internal tables and cannot be dropped.";
			}
		else{
			my $ck=&executeSQL($sql);
			if($ck==1){$rtn .= qq|$sql<br>Successfully Completed command\n|;}
			else{
				$rtn .= qq|<div style="padding-left:5px;">\n|;
				$rtn .= qq|<b style=\"color:red\">Error</b><br>\n|;
				$rtn .= qq|$DBI::query<br><br>\n$ck\n|;
				$rtn .= qq|</div>\n|;
			}
		}
	return $rtn;
	}
###########
sub AlterSchema{
	my $tablename=shift || $input{_table};
	my $fieldlist=shift || $input{_schema};
	my $nlc=$/;
	my @fields=split(/[$nlc\r\n]+/i, $fieldlist);
	my %Fieldlist=();
	foreach my $fieldstr (@fields){
		my ($fld,$type)=split(/[=\s]+/,$fieldstr,2);
		$fld=strip($fld);
		$type=strip($type);
		#dissallow fields that start with an understore - Internal use only.
		next if length($fld)==0 || length($type)==0;
		next if $fld=~/^\_/s;
		#Force certains field types for built in tables
		if($tablename=~/^\_tabledata$/is){
			if($fld=~/^(formfields|listfields)/is){$type="text NULL";}
	          }
		$Fieldlist{$fld}=$type;
		#print "&nbsp;"x5,"$fld\=\>\"$type\"<br>\n";
		}
	#Fix _fielddata fieldset if needed
	if($tablename=~/^\_fielddata$/is){
		$Fieldlist{behavior}="varchar(255) NULL";
		$Fieldlist{onchange}="varchar(255) NULL";
	     }
	my @ufields=();
	foreach my $fld (sort(keys(%Fieldlist))){
		push(@ufields,$fld=>$Fieldlist{$fld});
		}
	my $paircnt=@ufields;
	if($paircnt == 0){
		print "Alter Schema Error: No User Fields Defined";
		}
	else{
		my @afields=(
			_id			=> "integer primary key",
			_cdate 		=> "datetime NULL",
			_edate 		=> "datetime NULL",
			_cuser		=> "integer NULL",
			_euser		=> "integer NULL",
			);
		if($tablename=~/^_users$/is){push(@afields,_adate=> "datetime NULL");}
		push(@afields,@ufields);

		my $ck=alterDBTable($tablename,@afields);
		if(!isNum($ck)){print qq|<span class="w_red">ERROR:</span> $ck|;}
		elsif($ck==1){print "&nbsp;"x5,"Successfully Altered Schema for $tablename\n";}
		else{
			print qq|<div style="padding-left:20px;">\n|;
			print qq|<b style=\"color:red\">Error</b><br>\n|;
			print qq|$DBI::query<br><br>\n$ck\n|;
			print qq|</div>\n|;
			}
		#remove old records in _fielddata that no longer map to a real field
		cleanDBMetaData($tablename);
		}
	}
###########
sub NewSchema{
	my $tablename=shift || input{_table};
	if(isDBReservedWord($tablename)){return qq|<span class="w_red">ERROR:</span> <u>$tablename</u> is a reserved word|;}
	my $fieldlist=shift || $input{_schema};
	my $rtn='';
	my $nlc=$/;
	my @fields=split(/[$nlc\r\n]+/i, $fieldlist);
	my @ufields=();
	foreach my $fieldstr (@fields){
		my ($fld,$type)=split(/[=\s]+/,$fieldstr,2);
		$fld=strip($fld);
		$type=strip($type);
		#dissallow fields that start with an understore - Internal use only.
		next if length($fld)==0 || length($type)==0;
		next if $fld=~/^\_/s;
		if(isDBReservedWord($fld)){return qq|<span class="w_red">ERROR:</span> <u>$fld</u> is a reserved word|;}
		push(@ufields,$fld=>$type);
		#print "&nbsp;"x5,"$fld\=\>\"$type\"<br>\n";
		}
	#print "&nbsp;"x5,"\)\;<br>\n";
	my $paircnt=@ufields;
	if($paircnt == 0){
		$rtn .= "Create Schema Error: No User Fields Defined";
		}
	else{
		my @afields=(
			_id			=> "integer primary key",
			_cdate 		=> "datetime NULL",
			_edate 		=> "datetime NULL",
			_cuser		=> "integer NULL",
			_euser		=> "integer NULL",
			);
		if($tablename=~/^_users$/is){push(@afields,_adate=> "datetime NULL");}
		push(@afields,@ufields);
		#print "<hr><b>Running createDBTable Command</b><br>\n";
		my $ck=createDBTable($tablename,@afields);
		if($ck==1){$rtn .= "Successfully Created $tablename";}
		else{$rtn .= qq|<b class="w_red">Error: </b> $ck<br>\n|;}
		}
	return $rtn;
	}
#####################
sub readUserTable{
	#reads the _users table and sets $USER hash with values that match this guid.
	my $guid= shift || $ENV{GUID} || return "No GUID";
	my %ulist=();
	my $sql=qq|select * from _users where guid=$guid|;
	my $cnt=getDBData(\%ulist,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){abort("readUserTable Error:<br>$cnt<br>$sql");}
	%USER=();
	if($cnt==0){return "No Record for $guid found";}
	if($cnt!=1){return "Error in readUserTable: $sql<br>$cnt";}
	my @fields=@{$ulist{fields}};
	foreach my $field (@fields){
		my $val=strip($ulist{0}{$field});
		next if length($val)==0;
		$USER{$field}=$val;
		}
	$USER{apikey}=encodeUserAuthCode();
	$USER{password}=~s/./\*/g;
	#change the _adate of the users table.
	my $ok=editDBData("_users","_id=$USER{_id}",_adate=>getDate("YYYY-NM-ND MH:MM:SS"));
	return $ok;
	}
######################
sub encodeUserAuthCode{
	#generate a user auth code  YYYYUsernamePasswordMM
	if(!isNum($USER{_id})){return '';}
	my @auth=(
		str_replace(':','',crypt($ENV{UNIQUE_HOST},$USER{username})),
		str_replace(':','',crypt($USER{username},$USER{password})),
	    str_replace(':','',crypt($USER{password},$USER{username}))
	    );
	$code=encodeBase64(join(':',@auth));
	return $code;
	}
sub str_replace{
	my ($old,$new,$str)=@_;
	$str=~s/\Q$old\E/$new/sg;
	return $str;
	}
#####################
sub userLogin{
	#usage: userLogin();
	#info: reads the _users table and sets $USER hash with values that match this guid.
	my $guid= shift || $ENV{GUID};
	if(!$input{_login} && !length($input{apikey})){
		my $ck=&readUserTable($guid);
		return 1;
		}
	#check to make sure there is a _users table, otherwise create one.
	my $utable=0;
	my @DBTables=getDBTables();
	foreach my $table (@DBTables){
		if($table=~/^\_users$/is){$utable++;}
		}
	if(!$utable){
		#Create _users Table
		abort("No _users table found");
		}
	my %ulist=();
	my $username=strip($input{username});
	my $password=strip($input{password});
	my $oldguid=$guid;
	if($input{apikey} && $input{username} && $ENV{REQUEST_METHOD}=~/^POST$/is){
		my %rec=getDBRecord('-table'=>"_users",'username'=>$input{username});
		if(isNum($rec{_id})){
			my @auth=(
				str_replace(':','',crypt($ENV{UNIQUE_HOST},$rec{username})),
				str_replace(':','',crypt($rec{username},$rec{password})),
			    str_replace(':','',crypt($rec{password},$rec{username}))
			    );
			my @api=split(/[:]/,decodeBase64($input{apikey}));
			#return abort("@auth<br>\n@api");
			if(isEqual($api[0],$auth[0]) && isEqual($api[1],$auth[1]) && isEqual($api[2],$auth[2])){
        		$username=$rec{username};
				$password=$rec{password};
				$oldguid=$rec{guid};
  				}
  			else{
				return "API key authentication failed";
            	}
  			}
    	}
	if(length($username)==0){return "Username is Required";}
	if(length($password)==0){return "Password is Required";}
	#Check for authhost request - authenticate from a different server
	if(length($Config{authhost}) && $Config{authhost} !~ /^\Q$Config{dbhost}\E$/is){
		#Post the authentication request
		my $uahost=&getUniqueHost($Config{authhost});
		my $url="http://" . $Config{authhost} . $ENV{SCRIPT_NAME};
		if(1==2 && $Config{authhost} !~/^localhost$/is && $uahost =~/^\Q$Config{authhost}\E$/is){
			$url="http://www." . $Config{authhost} . $ENV{SCRIPT_NAME};
			}
		my $key=encodeCRC($username  . $password . $guid);
		#abort("auth $url [$uahost] [$Config{authhost}][$key][$guid]");

		my ($head,$body,$code)=postURL($url,
			_auth		=> 'login',
			username 	=> $username,
			password 	=> $password,
			_code		=> $key,
			guid		=> $guid,
			_where		=> $input{_where},
			);

		if($code==200){
			my %XML=();
			my @tmp=();
			my @tmp2=();
			$body=~m/\<WaSQL\>(.+?)\<\/WaSQL\>/sig;
			my $xml=$1;
			while($xml=~m/\<(.+?)\>(.+?)\<\/\1\>/sig){
				my $field=lc($1);
				push(@tmp,$field);
				my $val=strip($2);
				push(@tmp2,$val);
				next if length($val)==0;
                $XML{$field}=$val;
	            }
	         if($XML{_code}==200){
				my @fields=getDBFields("_users");
				my @pairs=();
				foreach my $field (@fields){
					next if $field=~/^\_/is;
					my $val=strip($XML{$field});
					next if length($val)==0;
					$USER{$field}=$val;
					#next if $field=~/^utype$/is;
					push(@pairs,$field=>$val);
					}
				$USER{apikey}=encodeUserAuthCode();
				$USER{password}=~s/./\*/g;
				#Add or Edit the local database with this user record
				my %list=();
				my $sql=qq|select * from _users where username like '$USER{username}'|;
				if(length($input{_where})){$sql .= qq| and $input{_where}|;}
				my $cnt=&getDBData(\%list,$sql,"nocount=1;limit=1");
				if(!isNum($cnt)){abort("userLogin Error:<br>$cnt<br>$sql");}
				if($cnt==1){
					$USER{_id}=$list{0}{_id};
					$USER{_cdate}=$list{0}{_cdate};
					my $edate=getDate("YYYY-NM-ND MH:MM:SS");
					$USER{_edate}=$edate;
					my $ok=editDBData("_users","username like '$USER{username}'",_edate=>$edate,@pairs);
	                }
				elsif($cnt==0){
					my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
					push(@pairs,utype=>$XML{utype} || 1);
					my $id=addDBData("_users",_cdate=>$cdate,@pairs);
					if(!isNum($id)){
						%USER=();
						abort("userLogin Error adding user:<br>$DBI::errstr<br>$DBI::query");
						}
					$USER{_id}=$id;
					$USER{_cdate}=$cdate;
					}
				my $ck=&readUserTable($guid);
				}
			else{return "$XML{_code} - $XML{_msg}";}
			}
		return "auth=1";
		}
	my $sql=qq|select * from _users where username like '$username'|;
	if(length($input{_where})){$sql .= qq| and $input{_where}|;}
	#push(@login,"sql=$sql");
	my $cnt=&getDBData(\%ulist,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){abort("userLogin Error:<br>$cnt<br>$sql");}
	#push(@login,"getDBData=$cnt");
	if($cnt==0){return "No such user found";}
	my $pass=$ulist{0}{password};
	if($pass !~/\Q$password\E$/s){return "Incorrect password";}
	$oldguid=$ulist{0}{guid};
	my @fields=@{$ulist{fields}};
	foreach my $field (@fields){
		my $val=strip($ulist{0}{$field});
		next if length($val)==0;
		$USER{$field}=$val;
		}
	$USER{apikey}=encodeUserAuthCode();
	$USER{password}=~s/./\*/g;
	#add current guid to user record;
	#push(@login,"editing user id $USER{_id}");
	my $ck;
	my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
	$guid=getGuid($guid,1);
	if($guid !~/^\Q$oldguid\E$/is && $input{'_noguid'}==1){
		#abort("$guid=$oldguid");
		$guid=$oldguid;
		}
	$USER{guid}=$guid;
	($ck,$sql)=editDBData("_users","_id=$USER{_id}",guid=>$guid,_adate=>$cdate);
	if(isDBTable("_history")){
		my $new=addDBData("_history",tablename=>"_users",recid=>$USER{_id},fieldname=>"guid",fieldvalue=>$guid,note=>"User $USER{username} logged in using $ENV{REMOTE_BROWSER}. Guid=$guid");
		}
	if(!isNum($ck)){return abort("Error editing User Record<br>\n$sql<br>\n$ck");}
	return 1;
	}
#####################
sub loginForm{
	return userLoginForm(@_);
	}
#####################
sub userLoginForm{
	#usage: $rtn .= userLoginForm(_title=>"Please log in first");
	#info: builds the html user login form
	my %params=@_;
	my $Title=$params{_title} || "Login Form";
	my $action=$params{_action} || $cgiroot || $ENV{SCRIPT_NAME};
	my $rtn='';

	my $view=$params{_view} || $input{_view} || $PAGE{_id};
	$rtn .= qq|<form class="w_form" name="login" method="post" action="$action" style="margin:0px;padding:0px;" enctype="application/x-www-form-urlencoded" onSubmit="return submitForm(this);">\n|;
	if(length($view)){$rtn .= qq|<input type="hidden" name="_view" value="$view">\n|;}
	$rtn .= qq|<input type="hidden" name="guid" value="$ENV{GUID}">\n|;
	$rtn .= qq|<input type="hidden" name="_login" value="1">\n|;
	if(length($input{_action}) && $input{_action}!~/^log off$/is){$rtn .= qq|<input type="hidden" name="_action" value="$input{_action}">\n|;}
	$rtn .= qq|<input type="hidden" name="_m0" value="$input{_m0}">\n| if length($input{_m0});
	$rtn .= qq|<input type="hidden" name="_m1" value="$input{_m1}">\n| if length($input{_m1});
	$rtn .= qq|<input type="hidden" name="_m2" value="$input{_m2}">\n| if length($input{_m2});
	if($params{_where}){$rtn .= qq|<input type="hidden" name="_where" value="$params{_where}">\n|;}
	#include other inputs
	foreach my $key (keys(%params)){
		next if $key=~/^\_(m0}m1|m2|view|login|menu|action|where)$/is;
		next if $key=~/^(username|password|guid)$/is;
		my $val=strip($params{$key});
		next if length($val)==0;
		if($val=~/[^a-z0-9\.\ ]/is){
			$val=encodeHtml($val);
			#val contains extended characters
			$rtn .= qq|<textarea style="display:none" name="$key">$val</textarea>\n|;
			}
		else{
			$val=encodeHtml($val);
			$rtn .= qq|<input type="hidden" name="$key" value="$val">\n|;
			}
		}
	my $user=$params{username} || $input{username};
	my $pass=$params{password};
	my $userTitle=$params{username_title} || 'Username';
	my $passTitle=$params{password_title} || 'Password';
	my $userWidth=$params{username_width} || 150;
	my $passWidth=$params{password_width} || 75;
	$userWidth.='px';$passWidth.='px';
	if($params{_layout}=~/^straight$/is){
		$rtn .= qq|<table cellspacing="3" cellpadding="2" border="0"><tr align="left" valign="bottom">\n|;
		$rtn .= qq|\t<td class="w_smaller">$userTitle\:<br><input title="Enter your $userTitle" class="w_smaller" type="text" maxlength="255" name="username" value="$user" onFocus="this.select();" style="width:$userWidth\;"></td>\n|;
          $rtn .= qq|\t<td class="w_smaller">$passTitle\:<br><input title="Enter your $passTitle" class="w_smaller" type="password" maxlength="255" name="password" value="$pass" onFocus="this.select();" style="width:$passWidth\;"></td>\n|;
          if($params{_register}){
		  	$rtn .= qq|\t<td><a class="w_smaller w_link" href="$params{_register}">Register</a><br><input  type="submit" class="w_formsubmit w_smaller" value="Login"></td>\n|;
          	}
          else{
		  	$rtn .= qq|\t<td><input  type="submit" class="w_formsubmit w_smaller" value="Login"></td>\n|;
		  	}
		$rtn .= qq|</tr>\n|;
		#remind me link
		$rtn .= qq|		<tr><td colspan="3" align="right">\n|;
		$rtn .= qq|			<a title="Click here if you need your login information emailed to you." href="#" onClick="remindMeForm('$cgiroot');return false;" class="w_a" style="font-size:9pt;float:right;">Remind Me</a>\n|;
		$rtn .= qq|		</td></a>\n|;
		$rtn .= qq|</table>\n|;
		}
	else{
		#$rtn .= qq|<table><tr><td>\n|;
	    #$rtn .= qq|<fieldset id="loginform" style="float:left;width:185px;">\n|;
	    #$rtn .= qq|	<legend>$Title</legend>\n|;
	    $rtn .= qq|	<table cellspacing="3" cellpadding="2" border="0"><tr>\n|;
		$rtn .= qq|		<th align="left">$userTitle\:</th>\n|;
	    $rtn .= qq|		<td><input title="Enter your $userTitle" type="text" maxlength="255" name="username" value="$user" onFocus="this.select();" style="width:$userWidth\;"></td>\n|;
	    $rtn .= qq|	</tr><tr>\n|;
	    $rtn .= qq|		<th align="left">$passTitle\:</th>\n|;
		$rtn .= qq|		<td><input title="Enter your $passTitle" type="password" maxlength="255" name="password" value="$pass" onFocus="this.select();" style="width:$passWidth\;"></td>\n|;
		$rtn .= qq|	</tr><tr>\n|;
		$rtn .= qq|		<td><input  type="submit" class="w_formsubmit" value="Login"></td>|;
		#remind me link
		$rtn .= qq|		<td align="right">\n|;
		$rtn .= qq|			<a title="Click here if you need your login information emailed to you." href="#" onClick="remindMeForm('$cgiroot');return false;" class="w_a" style="font-size:9pt;float:right;">Remind Me</a>\n|;
		$rtn .= qq|		</td>\n|;
        $rtn .= qq| </table>\n|;
		}
	 if(!$params{_focus}){
	 	$rtn .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" style="border:0px;" onLoad="document.login.username.focus();">\n|;
     	}
     $rtn .= qq|</form>\n|;
	return $rtn;
	}
#####################
sub remindMeForm{
	#usage: $rtn .= remindMeForm();
	#info: build the html remind me form for user login...
	my $msg=shift || "Enter your email address and we will send you a reminder.";
	my $rtn='';
	my $view=$params{_view} || $PAGE{_id};
	$rtn .= qq|<form class="w_form" name="reminderForm" method="post" action="$ENV{SCRIPT_NAME}" style="margin:0px;padding:0px;" enctype="application/x-www-form-urlencoded" onSubmit="return submitRemindMeForm(this);">\n|;
	$rtn .= qq|<input type="hidden" name="guid" value="$ENV{GUID}">\n|;
	$rtn .= qq|<input type="hidden" name="_remind" value="1">\n|;
	$rtn .= qq|	<div id="reminderMessage" class="w_red">$msg</div>\n|;
	$rtn .= qq|	<div class="w_formtext w_bold" style="margin-top:5px;">Email Address:</div>\n|;
	$rtn .= qq|	<div style="margin-top:5px;"><input type="text" maxlength="255" name="email" mask=".+@.+\..{2,6}" maskmsg="Invalid Email Address" required="1" requiredmsg="Enter the email address registered you registered with." value="$input{email}" onFocus="this.select();" style="width:200px;font-size:8pt;font-family:arial;"></div>\n|;
	$rtn .= qq|	<div align="right" style="margin-right:2px;margin-top:5px;"><input type="submit" class="w_formsubmit" value="Remind Me"></div>\n|;
	$rtn .= qq|</form>\n|;
	$rtn .= qq|<img src="/wfiles/clear.gif" width="1" height="1" border="0" onLoad="document.reminderForm.email.focus();">\n|;
	return $rtn;
	}
#####################
sub userLogout{
	#usage: userLogout();
	#info: Removes GUID from user table for the currently logged in user, thus forcing the user to log in again.
	my $crit;
	if($USER{_id}){
		$crit = qq|_id=$USER{_id}|;
		if(isDBTable("_history")){
			my $new=addDBData("_history",tablename=>"_users",recid=>$USER{_id},fieldname=>"guid",fieldvalue=>$USER{guid},note=>"User $USER{username} logged OUT using $ENV{REMOTE_BROWSER}. Guid=$USER{guid}");
			}
		}
	elsif($ENV{GUID}){
		$crit = qq|guid=$ENV{GUID}|;
		if(isDBTable("_history")){
			my $new=addDBData("_history",tablename=>"_users",recid=>$USER{_id},fieldname=>"guid",fieldvalue=>$ENV{GUID},note=>"Unknown User $USER{username} logged OUT using $ENV{REMOTE_BROWSER}. Guid=$ENV{GUID}");
			}
		}
	my ($ck,$sql)=editDBData("_users",$crit,guid=>'',_adate=>getDate("YYYY-NM-ND MH:MM:SS"));
	%USER=();
	return "$ck<br>$sql";
	}
#####################
sub waSQLCssJs{
	#usage: $rtn .= waSQLCssJs();
	#info: builds the html css and js code needed for wasql..
	return if $ENV{waSQLCssJs}==1;
$ENV{waSQLCssJs}=1;

return <<waSQLCssJsCODE
<!--Begin  Wasql Css and Js Files -->
<link type="text/css" rel="stylesheet" href="/wfiles/min/index.php?g=w_Css" />
<script type="text/javascript" src="/wfiles/min/index.php?g=w_Js"></script>
<!-- End -->
waSQLCssJsCODE
	}
return 1;
