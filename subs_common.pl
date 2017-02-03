#subs_common.pl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
###############
sub fixPathSlashes{
	my $str=shift;
	my $slash=shift || '';
	if(length($slash)==0){
		$slash='/';
		if($^O =~ /^MSWIN32$/is){$slash="\\";}
	}
	my @tmp=split(/[\\\/]+/,$str);
	return join($slash,@tmp);
}
sub buildDir{
	#usage: my $ck=buildDir($path);
	#info: Recursively builds the directory path.., return 1 if successful, else returns 0
	#tags: dir, file
	my $dir=shift || return "No Dir\n";
	my $index=shift;
	my $slash='/';
	if($^O =~ /^MSWIN32$/is){$slash="\\";}
	my @parts=split(/[\\\/]+/,$dir);
	my @dparts=();
	foreach my $part (@parts){
		push(@dparts,$part);
		my $makedir=join($slash,@dparts);
		if($dir=~/^\//s){$makedir=$slash. $makedir;}
		if(!-d $makedir){
			mkdir($makedir,0755) || return $!;
			if($index){
				#place an index.html file in the directory so it is not browseable
				setFileContents("$makedir/index.html",$index);
            	}
			}
		}
	return 1;
	}
#############
sub bytes2KB{
	#usage: my $kb=bytes2KB($bytes)
	#info: converts bytes to KB
	#tags: bytes, convert
	my $num=shift;
	if(!isNum($num)){return "$num is not a number";}
	my $kb=sprintf("%.0f",($num/1024)) || 0;
	return $kb;
	}
#############
sub bytes2MB{
	#usage: my $mb=bytes2MB($bytes)
	#info: converts bytes to Megabytes
    #tags: bytes, convert
	my $num=shift;
	if(!isNum($num)){return "$num is not a number";}
	my $mb=sprintf("%.1f",($num/1024/1024)) || 0;
	return $mb;
	}
#############
sub bytes2GB{
	#usage: my $gb=bytes2GB($bytes)
	#info: converts bytes to Gigabytes
	#tags: bytes, convert
	my $num=shift;
	if(!isNum($num)){return "$num is not a number";}
	my $gb=sprintf("%.2f",($num/1024/1024/1024)) || 0;
	return $gb;
	}
###############
sub cleanDir{
	#usage: my $ck=cleanDir($path[,$dirs]);
	#info: removes all files and folders(if $dirs) from $path
	#tags: dir, file
	my $path=shift || return "No cleanDir path\n";
	my $dirs=shift;
	my $timestamp=shift;
	my @files=listFiles($path);
	foreach my $file (@files){
		my $afile="$path/$file";
		if(isNum($timestamp)){
			#if timestamp is specified, skip if file is newer than timestamp
			my %stat=fileStats(-file=>$afile);
			if(isNum($stat{mtime}) && $stat{mtime}> 0 && $stat{mtime} < $timestamp){next;}
        	}
		if($dirs && -d $afile){cleanDir($afile,$dirs,$timestamp);}
		elsif(unlink($afile)){}
		else{return "Unable to remove $afile";}
		}
	return 1;
	}
###############
sub cleanup{
	#usually called in the END sub to make sure all dlls get unloaded
	DynaLoader::dl_unload_file($_) foreach (@DynaLoader::dl_librefs);
	}
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
sub createExpandDiv{
	#usage: $html .= createExpandDiv($title,$content[,$color,$open]);
	#info: creates an html div that is expandable by clicking on the + in front.
	my ($title,$content,$color,$open)=@_;
	my $id=encodeCRC($content);
	#defines
	$color ||= '#002E5B';
	my $iconId='expand_icon_' . $id;
	my $sectionId='expand_section_' . $id;
	my $icon=qq|<img src="/wfiles/plus.gif" border="0">|;
	my $display='none';
	if($open){
		$icon=qq|<img src="/wfiles/minus.gif" border="0">|;
		$display='block';
    	}
	#begin div
	my $html=qq|<div style="margin-bottom:3px;" align="left">\n|;
	#build the +/- link
	$html .= qq|\t<div id="$iconId" onClick="expand('$id')" style="float:left;font-size:13pt;width:13px;font-weight:bold;color:#4974D6;cursor:pointer;">$icon</div>\n|;
	#add title
	$html .= qq|<b style="color:$color">$title</b>\n|;
	#add the section message
    $html .= qq|\t<div id="$sectionId" style="display:$display\;color:$color\;margin-left:10px;font-size:9pt;">\n$content\n\t</div>\n|;
	#ending div
    $html .= qq|</div>\n|;
    return $html;
	}
###---HERE---###
################
sub hasChanged{
	#usage: if(hasChanged(\%stat)){...}
	#info: returns 1 if the file has a new modified time than passed in
	#tags: file
	my $hash=shift;
	return 1 if !-e $hash->{file};
	my %stat=fileStats(-file=>$hash->{file});
	return 0 if $stat{mtime}=$hash->{mtime};
	return 1;
	}
################
sub hashKeys{
	#usage: my $rtn .= hashValues(\%input);
	#info: return key=value<br> pairs for said hash
	#tags: hashes, input
	my $hash=shift || return "no hash reference";
	my $tabs=shift || 0;
	my $recurse = shift || 1;
	my $splitter='<br>';
	if(defined $ENV{PATH} && defined $ENV{ComSpec}){$splitter='';}
	my @pairs;
	foreach my $key (sort(keys(%{$hash}))){
		my $val=strip($hash->{$key});
		$key=strip($key);
		next if length($val)==0;
		next if length($key)==0;
		if($recurse==1){
			if(isHash($val)){
				my $str=hashValues($val,$tabs+1);
				push(@pairs,$key,$str);
				}
			elsif(isArray($val)){
				my @tmp=@$val;
				$val="Array[@tmp]";
				push(@pairs," "x$tabs . "$key=$val");
				#push(@pairs,"$splitter\r\n\t" . $val);
				}
            else{push(@pairs," "x$tabs . "$key");}
			}
		else{push(@pairs," "x$tabs . "$key");}
		}
	#if(wantarray){return @pairs;}
	my $pairstr=join("$splitter\r\n",@pairs) . "\r\n";
	return $pairstr;
	}
################
sub hashValues{
	#usage: my $rtn .= hashValues(\%hash,[param1=>val1,...]);
	#info: return key=value<br> pairs for said hash
	#info: Valid Params: recurse=>1 - recurse hashes and arrays
	#info: 				 fmt=>[table,text] return as a table or
	#tags: hashes, input
	my $hash=shift || return "no hash reference";
	my %params=@_;
	if(!isNum($params{tabs})){$params{tabs}=0;}
	if(!isNum($params{recurse})){$params{recurse}=1;}
	if(!length($params{fmt})){$params{fmt}="dos";}

	my $splitter='<br>';
	my @pairs;
	my $rtn='';
	if(isEqual($params{fmt},"table")){
		$rtn .= qq|<table cellspacing="0" cellpadding="2" border="1" class="w_table">\n|;
		if($params{title}){
			$rtn .= qq|	<tr class="w_redback"><th colspan="2">$params{title}</th></tr>\n|;
        	}
        $params{key} ||= "Key";
        $params{value} ||= "Value";
		$rtn .= qq|	<tr class="w_blueback">\n|;
		$rtn .= qq|		<th>$params{key}</th>\n|;
		$rtn .= qq|		<th>$params{value}</th>\n|;
		$rtn .= qq|	</tr>\n|;
		foreach my $key (sort(keys(%{$hash}))){
			if($params{-ignore} && $params{-ignore}=~/\Q$key\E/is){next;}
			my $val=strip($hash->{$key});
			if(isArray($val)){
				my @tmp=@$val;
				$val="Array[@tmp]";
				}
			if($params{-hidezero} && isNum($val) && $val==0){next;}
			if($params{-hideempty} &&  !length(strip($val))){next;}
			$key=strip($key);
			next if length($val)==0;
			next if length($key)==0;
			$val=~s/\;/<br>\n/sg;
			if($params{capitalize}){$key=capitalize($key);}
            $rtn .= qq|	<tr class="w_small" valign="top">\n|;
            $rtn .= qq|		<td nowrap>$key</td>\n|;
            $rtn .= qq|		<td>$val</td>\n|;
            $rtn .= qq|	</tr>\n|;
			}
		$rtn .= qq|</table>\n|;
		return $rtn;
    	}
    if(isEqual($params{fmt},"list")){
		$rtn .= qq|<ul class="w_list">\n|;
		if($params{title}){
			$rtn .= qq|$params{title}\n|;
        	}
		foreach my $key (sort(keys(%{$hash}))){
			my $val=strip($hash->{$key});
			$key=strip($key);
			next if length($val)==0;
			next if length($key)==0;
			$val=~s/\;/<br>\n/sg;
			if($params{capitalize}){$key=capitalize($key);}
            $rtn .= qq|		<li><b>$key</b>: $val</li>\n|;
			}
		$rtn .= qq|</ul>\n|;
		return $rtn;
    	}
	foreach my $key (sort(keys(%{$hash}))){
		my $val=strip($hash->{$key});
		$key=strip($key);
		next if length($val)==0;
		next if length($key)==0;
		if($params{recurse}==1){
			if(isHash($val)){
				my $str=hashValues($val,recurse=>$params{recurse},tabs=>$params{tabs}+1,fmt=>$params{fmt});
				push(@pairs,$key,$str);
				}
			elsif(isArray($val)){
				my @tmp=@$val;
				$val="Array[@tmp]";
				push(@pairs," "x$tabs . "[$key]=$val");
				#push(@pairs,"$splitter\r\n\t" . $val);
				}
            else{push(@pairs," "x$tabs . "[$key]=$val");}
			}
		else{push(@pairs," "x$tabs . "[$key]=$val");}
		}
	#if(wantarray){return @pairs;}
	my $pairstr=join("$splitter\r\n",@pairs) . "\r\n";
	return $pairstr;
	}
################
sub hash2Html{
	#internal usage: print hash2Html(\%hash[,title=>$title,bgcolor=>$bgcolor]);
	my $hash=shift || return "no hash passed to hash2Html";
	my %params=@_;
	my $title=$params{title};
	#Determine hash count
	my $cnt=0;
	if($hash->{count}){$cnt=$hash->{count};}
	else{
		foreach my $key (keys(%{$hash})){
			if(isNum($key)){$cnt++;}
			}
		}
	my $rtnstr;
	if($cnt==0){
		if($DBI::errstr){return $DBI::errstr;}
		return "Hash count is empty";
		}
	#determine hash fields
	my @fields=();
	if($params{fields}){@fields = @{$params{fields}};}
	elsif($hash->{fields}){@fields = @{$hash->{fields}};}
	else{
		foreach my $key (keys(%{$hash->{0}})){
			push(@fields,$key);
			}
		}
	my $fieldcnt=@fields;
	if($fieldcnt==0){return "Unable to determine fields in hash2Html";}

	#Show Data results
	if($title){$rtnstr .= qq|$title\n|;}
	$rtnstr .= qq|<table id="hash2Html" cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse">\n|;
	#$rtnstr .= header row
	my $bgcolor=$params{bgcolor} || '#336699';
	my $color=$params{color} || '#FFFFFF';
	$rtnstr .= qq|<tr align="center" bgcolor="$bgcolor" valign="middle">\n|;

	if($params{_showrow}){$rtnstr .= qq|<td style="color:$color;" nowrap="true">\#</td>|;}
	if($params{_selectrow}){
		$rtnstr .= qq|<td style="color:$color;" nowrap="true"><input type="checkbox" onClick="checkAllByAttribute(this.checked,'name','_selectrow');"></td>|;
		}
	foreach my $field (@fields){
		my $order=$field;
		my $dname;
		my $nowrap=0;
		$dname = $params{"$field\_title"} || ucfirst($field);
		$rtnstr .= qq|<td style="color:$color;" nowrap="true"|;
		if($params{"$field\_tip"}){
			my $tip=$params{"$field\_tip"};
			$rtnstr .= qq| title="$tip"|;
			}
		$rtnstr .= qq|>$dname</td>|;
		}
	$rtnstr .= qq|</tr>\n|;
	my %Totals=();
	my @xs=(0..$cnt);
	if($params{sort}){@xs=sortHashByKey($hash,$params{sort});}
	foreach my $x (@xs){
	#for(my $x=0;$x<$cnt;$x++){
		my $class=isEven($x)?'even':'odd';
		$rtnstr .= qq|<tr valign="top" id="hash2Html\_$x" class="$class">\n|;
		$rtnstr .= qq|<td>$x</td>| if $params{_showrow};
		if($params{_selectrow}){
			my $sval=$params{_selectrow};
			foreach my $pfield (@fields){
				$sval=~s/\%$pfield\%/$hash->{$x}{$pfield}/sig;
				}
			$rtnstr .= qq|<td><input type="checkbox" name="_selectrow" value="$sval"|;
			if($params{_highlightselectedrow}){$rtnstr .= qq| onClick="if(this.checked){document.getElementById('hash2Html\_$x').style.backgroundColor='#FDFED8';}else{document.getElementById('hash2Html\_$x').style.backgroundColor='';}"|;}
			$rtnstr .= qq|></td>|;
			}
		foreach my $field (@fields){
			#next if ! $params{$field};
			my $val=$hash->{$x}{$field};
			my $ori_val=$val;
			my $align=$params{"$field\_align"} || "left";
			if($params{"$field\_encodeHtml"}){$val=encodeHtml($val);}
			if($params{"$field\_total"}){
				$Totals{$field} += $val;
				$align="right";
	            }
            if($params{"$field\_format"}){
				my $format=$params{"$field\_format"};
				$val=sprintf($format,$val);
            	}
            if($params{"$field\_sub"}){
				my $sub=$params{"$field\_sub"};
				$val=&$sub($val);
            	}
			if($params{"$field\_href"}){
				my $pval=$params{"$field\_href"};
				foreach my $pfield (@fields){
					$pval=~s/\%$pfield\%/$hash->{$x}{$pfield}/sig;
					}
				my $target=$params{href_target} || "_self";
				$val = qq|<a href="$pval" target="$target">$val</a>|;
				}
			elsif($params{"$field\_onclick"}){
				my $pval=$params{"$field\_onclick"};
				foreach my $pfield (@fields){
					$pval=~s/\%$pfield\%/$hash->{$x}{$pfield}/sig;
					}
				$val = qq|<a href="#" onClick="$pval\;return false;">$val</a>|;
				}
			if($params{_highlight} && $ori_val=~/\Q$params{_highlight}\E/is){
				if($val=~/[\<]/s && $val=~/[\>]/s){
					$rtnstr .= qq|<td align="$align" bgcolor="#FDFED8">$val</td>|;
					}
				else{
					my $hval=$params{_highlight};
					my $span=qq|<span style="background-color:#FDFED8">$hval</span>|;
					$val =~s/\Q$hval\E/$span/sg;
                         $rtnstr .= qq|<td align="$align">$val</td>|;
    					}
				}
			else{$rtnstr .= qq|<td align="$align">$val</td>|;}
			}
		$rtnstr .= qq|</tr>\n|;
		}
	#Check for Totals Row
	my @totalkeys=keys(%Totals);
	my $totalcnt=@totalkeys;
	if($totalcnt){
		$rtnstr .= qq|<tr align="right" bgcolor="$bgcolor" valign="middle">\n|;
		$rtnstr .= qq|<td></td>| if $params{_showrow};
		$rtnstr .= qq|<td></td>| if $params{_selectrow};
		foreach my $field (@fields){
			if(defined $Totals{$field}){
				my $title="Total " . ucfirst($field);
				$rtnstr .= qq|<td style="color:$color;" title="$title">$Totals{$field}</td>|;
				}
			else{$rtnstr .= qq|<td></td>|;}
			}
		$rtnstr .= qq|</tr>\n|;
		}
	$rtnstr .= qq|</table>\n|;
	return $rtnstr;
	}
################
sub request2XML{
	my $request=shift || %input;
	my $server=shift || %ENV;
	#print "request2XML\n";
	#abort($request->{cost});
	$xml=xmlHeader('version'=>"1.0",'encoding'=>"utf-8");
    $xml .= "<request>\n";
    #Server
    $xml .= "	<server>\n";
    if(!$server){
		$xml .= "		<http_host>".$ENV{'HTTP_HOST'}."</http_host>\n";
		$xml .= "		<remote_addr>".$ENV{'REMOTE_ADDR'}."</remote_addr>\n";
		$xml .= "		<http_referer>".xmlEncode($ENV{'HTTP_REFERER'})."</http_referer>\n";
		$xml .= "		<script_url>".xmlEncode($ENV{'SCRIPT_URL'})."</script_url>\n";
		$xml .= "		<http_user_agent>".xmlEncode($ENV{'HTTP_USER_AGENT'})."</http_user_agent>\n";
		$xml .= "		<server_addr>".$ENV{'SERVER_ADDR'}."</server_addr>\n";
		$xml .= "		<timestamp>".time()."</timestamp>\n";
		}
	else{
		$server{'edit_timestamp'}=time();
		foreach my $field (sort(keys(%{$server}))){
			my $val=$server->{$field};
			my $key=strtolower($field);
			$val=xmlEncodeCDATA($val);
			$xml .= "		<$key>".$val."</$key>\n";
        	}
    	}
	$xml .= "	</server>\n";
	#Data
	$xml .= "	<data>\n";
	#remove request fields we don't care about
	#print "Content-type: text/plain\n\n";
	#add request vals as data
	foreach my $field (sort(keys(%{$request}))){
		my $val=$request->{$field};
		#print "$field = [$val]\n";
		#$val=xmlEncodeCDATA($val);
		if($field=~/^\_/s){$field="u_".$field;}
		if($field=~/^\-/s){$field="d_".$field;}
		next if $field=~/^(x|y)$/i;
		$xml .= "		<$field>".$val."</$field>\n";
    	}
    $xml .= "	</data>\n";
    #User
    if(isNum($USER{_id})){
		$xml .= "	<user>\n";
		#remove request fields we don't care about

		#add request vals as data
		foreach my $field (sort(keys(%USER))){
			$val=$USER{$field};
			next if !length(strip($val));
			$val=xmlEncodeCDATA($val);
			if($field=~/^\_/s){$field="u_".$field;}
			if($field=~/^\-/s){$field="d_".$field;}
			next if $field=~/^(password)$/i;
			$xml .= "		<$field>".$val."</$field>\n";
	    	}
	    $xml .= "	</user>\n";
	    }
    $xml .= "</request>\n";
    return $xml;
	}
################
sub roundedCorners{
	#usage: $roundhtml=roundedCorners($html);
	#info: builds a table with rounded corners around $html
	my $html=shift;
	my %params=@_;
	$params{color} ||= '#000000';
	if(!isNum($params{bottom})){$params{bottom}=1;}
	if(!isNum($params{padding})){$params{padding}=5;}
	my $padding=$params{padding} . "px";
	my $rtn=qq|<table border="0" cellspacing="0" cellpadding="0" style="|;
	if(length($params{width})){
		my $width=$params{width};
		if(isNum($width)){$width . "px";}
		$rtn .= qq|width:$width;|;
    	}
    if(length($params{height})){
		my $height=$params{height};
		if(isNum($height)){$height . "px";}
		$rtn .= qq|height:$height;|;
    	}
	$rtn .= qq|">\n|;
    $rtn .= qq|<tr id="roundedTop" style="height:6px;">\n|;
    $rtn .= qq|	<td style="width:6px;background-color:$params{color}"><img src="/wfiles/corner_topleft.png" border="0" width="6" height="6"></td>\n|;
    $rtn .= qq|	<td style="border-top:1px solid $color;background:$params{color};color:$params{color};font-size:3pt;">.</td>\n|;
    $rtn .= qq|	<td style="width:6px;background-color:$params{color}"><img src="/wfiles/corner_topright.png" border="0" width="6" height="6"></td>\n|;
    $rtn .= qq|</tr>\n|;
    $rtn .= qq|<tr valign="top"><td colspan="3" style="background:$params{color};padding:$padding;border-right:1px solid $params{color};border-left:1px solid $params{color};">|;
    $rtn .= $html;
    $rtn .= qq|</td></tr>\n|;
    if($params{bottom}){
    $rtn .= qq|<tr id="roundedBottom" style="height:6px;">\n|;
    $rtn .= qq|	<td style="width:6px;background-color:$params{color}"><img src="/wfiles/corner_botleft.png" border="0" width="6" height="6"></td>\n|;
    $rtn .= qq|	<td style="border-bottom:1px solid $params{color};background:$params{color};color:$params{color};font-size:3pt;">.</td>\n|;
    $rtn .= qq|	<td style="width:6px;background-color:$params{color}"><img src="/wfiles/corner_botright.png" border="0" width="6" height="6"></td>\n|;
    $rtn .= qq|</tr>\n|;
	   }
    $rtn .= qq|</table>\n|;
	}
################
sub randomSalt {
	#generate a random salt value for crypt
	my @values=(0..9,a..z,A..Z);
	my $cnt=@values;
	my $i=rand($cnt);
	my $j=rand($cnt);
	my $salt=$values[$i] . $values[$j];
	return $salt;
	}
###############
sub readINI{
	#usage: my $section_count=readINI(\%hash,$iniFile[,$null]);
	#info: returns the body of each section in $hash{section}{_body}
	#info: returns key/value pairs as $hash{section}{key}=val
	#tags: file, ini
	my $hash=shift || return "No Hash";
	my $file=shift || return "No File";
	my $null=shift;
	my $crlf=shift;
	open(INI,$file) || return "INI file $file is blank or does not exist";
	my @lines=<INI>;
	close(INI);

	my $header;
	my $linecnt=@lines;
	if(!$linecnt){return "Empty ini File: $file";}
	%{$hash}=();
	foreach my $line (@lines){
		my $sline=strip($line);
		next if length($sline)==0;
		if(!$crlf){$line=strip($line);}
		next if $sline=~/^[\#\;]/;
		#print "$line\n";
		if($line=~/\[(.+?)\]$/s){
			my $name=$1;
	                $header=strip($name);
	                if($null==2){$hash->{$header}{_name}=$name;}
	                else{
				 	$header=lc($header);
				 	$hash->{$header}{_name}=$name;
	                    }

			next;
			}
		$header=strip($header);
		if($null!=2){$header=lc($header);}

		next if length($header)==0;
		$hash->{$header}{_body} .= qq|$line\n|;
		if($null==2){
               $hash->{$header}{$line}=2;
			}
		elsif($line=~/\=/s){
			my ($key,$val)=split(/\=/,$line,2);
			$key=lc(strip($key));
			$val=evalPerl($val);
			$val=strip($val);
			if($null && !length($val)){$val=$null;}
			next if length($key)==0 || length($val)==0;
			next if $key=~/^\_body$/is;
			$hash->{$header}{$key}=$val;
			#print "\thash->{$header}{$key}=$hash->{$header}{$key}\n";
			}
		elsif($null){
               $hash->{$header}{$line}=$null;
               #print "\thash->{$header}{$line}=$hash->{$header}{$line}\n";
			}
		}
	my @heads=keys(%ini);
	my $headcnt=@heads;
	return $headcnt;
	}
#####################
sub readXML{
	#usage: my %hash=readXML($xml[,$roottag]) or my %hash=readXML($xmlfile[,$roottag]);
	#info: reads a simple xml file
	#tags: xml, file
	my $xmlfile=shift || return (error=>"No File");
	my $roottag=shift || "xmlroot";
	my %traverse=@_;
	my $xml='';
	if(-s $xmlfile){
		$xml=getFileContents($xmlfile);
		}
	else{$xml=$xmlfile;}
	if(!length($xml)){
		return (error=>"Empty File");;
    	}
	#$xml=evalPerl($xml);
	my %xmlhash=();
	while($xml=~m/\<\Q$roottag\E.*?\>(.+?)\<\/\Q$roottag\E\>/sig){
		my $buildxml=$1;
		#print "Root: $roottag<br>\r\n";
		#remove comments <!-- APPLICATION INFORMATION -->
		$buildxml=~s/\<\!\-\-.+?\-\-\>//sg;
		#print "buildxml:\r\n$buildxml\r\n---------------------------\r\n\r\n";
		#Read full tags - <name>Billy</name>
		while($buildxml=~m/\<(.+?)\>(.+?)\<\/\1\>/sig){
			my $fulltag=$&;
			my $key=lc(strip($1));
			my $val=removeCDATA(strip($2));
			next if !length($val);
			$xmlhash{$key}=$val;
            $buildxml=~s/\Q$fulltag\E//s;
			}
		#Read full tags with attributes- <name age="25">Billy</name>
		while($buildxml=~m/\<(.+?)\ (.+?)>(.+?)\<\/\1\>/sig){
			my $fulltag=$&;
			my $key=lc(strip($1));
			my $attributes=strip($2);
			my $val=removeCDATA(strip($3));
			my %atts=parseAttributes($attributes);
			if($atts{'key'}){$key=lc(strip($atts{'key'}));}
			elsif($atts{'name'}){$key=lc(strip($atts{'name'}));}
			my $attcnt=0;
			foreach my $akey (keys(%atts)){
				next if $akey=~/^(name|key)$/i;
				$attcnt++;
				$xmlhash{$key}{$akey}=$atts{$akey};
                }
            if($attcnt==0){$xmlhash{$key}=$val;}
            else{$xmlhash{$key}{'value'}=$val;}
            $buildxml=~s/\Q$fulltag\E//s;
			}
		#Read tags with not end tag <resource id="name" />
		while($buildxml=~m/\<([a-z0-9\_\-]+?)[\ \r\n\t]+(.+?)\/\>/sig){
			my $key=lc(strip($1));
			my $attributes=strip($2);
			my $found=$&;
			my $estr;
			if(defined $xmlhash{$key}){
				my $cnt=1;
				while(defined $xmlhash{"$key\.$cnt"}){$cnt++;}
				$key="$key\.$cnt";
                }
            #print "key:$key<br>\n";
            my %atts=parseAttributes($attributes);
			foreach my $akey (keys(%atts)){
				$xmlhash{$key}{$akey}=$atts{$akey};
                }
			}
		}
	#Traverse any additonal keys
	foreach my $inkey (keys(%traverse)){
		my $outkey=$traverse{$inkey};
		#print "Traverse: $inkey [$outkey]\r\n";
		next if ! defined $xmlhash{$inkey};
		my $inxml=$xmlhash{$inkey};
		undef($xmlhash{$inkey});
		#read in any tags that are not $outkey
		while($inxml=~m/\<(.+?)\>(.+?)\<\/\1\>/sig){
			my $key=$1;
			my $val=$2;
			next if $key=~/^\Q$outkey\E$/is;
			#print "Key:$key\r\n";
			next if isXML($val);
			$xmlhash{$inkey}{$key}=$val;
			#print "xmlhash{$inkey}{$key}=$val\r\n";
        	}
		my $sIndex=0;
		while($inxml=~m/\<($outkey)\>(.+?)\<\/\1\>/sig){
			my $tag=$&;
			my $key=lc(strip($1));
			my $val=removeCDATA(strip($2));
			if(length($val)){
				while($val=~m/\<([a-z\:\_\-]+)(.*?)\>(.+?)\<\/\1\>/sig){
					my $xtag=$&;
					my $xkey=lc(strip($1));
					my $attributes=strip($2);
					my $xval=removeCDATA(strip($3));
					#recove cdata tag if it exists  <![CDATA[  value ]]>
					if(length($xval)){
						if(length($attributes)){
							 my %atts=parseAttributes($attributes);
							 foreach my $akey (keys(%atts)){
							 	my $xakey="$xkey\:$akey";
                                $xmlhash{$outkey}{$sIndex}{$xakey}=$atts{$akey};
                             	}
      						}
						$xmlhash{$outkey}{$sIndex}{$xkey}=$xval;
						}
					$val=~s/\Q$xtag\E//s;
					}
				#<media:player width="512" height="296" url="http://www.hulu.com/embed/drrxkjt0Rt8ihzwx-70Lew" />
                while($val=~m/\<([a-z\:\_\-]+)\ (.*?)\/\>/sig){
					my $xtag=$&;
					my $xkey=lc(strip($1));
					my $attributes=strip($2);
					if(length($attributes)){
						 my %atts=parseAttributes($attributes);
						 foreach my $akey (keys(%atts)){
						 	my $xakey="$xkey\:$akey";
                            $xmlhash{$outkey}{$sIndex}{$xakey}=$atts{$akey};
                            }
      					}
					$val=~s/\Q$xtag\E//s;
					}
				$sIndex++;
				}
			$inxml=~s/\Q$tag\E//s;
			}
		#print "inxml:\r\n$inxml\r\n---------------\r\n";
		while($inxml=~/\<\Q$outkey\E(.+?)\>/sig){
			#$Xml{rss}{$sIndex}{source}=$source;
			my $attributes=strip($1);
			my %atts=parseAttributes($attributes);
			foreach my $akey (keys(%atts)){
				$xmlhash{$outkey}{$sIndex}{$akey}=$atts{$akey};
                }
			$sIndex++;
	    	}
    	}
	return %xmlhash;
	}
#####################
sub removeCDATA{
	my $str=shift;
	$str=strip($str);
	#<![CDATA[...]]>
	if($str=~/^\<\!\[CDATA\[/s){
		$str=~s/^\<\!\[CDATA\[//s;
		$str=~s/\]\]\>$//is;
    	}
	return $str;
	}
#####################
sub readXMLEx{
	my $xmlfile=shift || return 1;
	my $debug=shift;
	my $xml='';
	if(-s $xmlfile){$xml=getFileContents($xmlfile);}
	else{$xml=$xmlfile;}
	return 2 if !length($xml);
	my %xmlhash=();
	#remove xml header
	$xml=~s/\<\?xml\ .+?\?\>//s;
	#remove comments <!-- APPLICATION INFORMATION -->
	$xml=~s/\<\!\-\-.+?\-\-\>//sg;
	#level 1 tags
	while($xml=~m/\<(.+?)\s*(.*?)\>(.+?)\<\/\1\>/sig){
		my $key=lc(strip($1));
		my $attribs=strip($2);
		my $val=strip($3);
		if(defined $xmlhash{$key}){
			my $cnt=1;
			while(defined $xmlhash{"$key\.$cnt"}){$cnt++;}
			$key="$key\.$cnt";
            }
		if(length($attribs)){
			my %ahash=parseAttributes($attribs);
			foreach my $akey (keys(%ahash)){$xmlhash{$key}{$akey}=$ahash{$akey};}
        	}

		if($val=~/\<\!\[CDATA\[(.+?)\]\]\>/s){$val=strip($1);}
		next if !length($val);
		if(isXML($val)){$xmlhash{$key}{_xml}=$val;}
		else{
			$xmlhash{$key}{_value}=$val;
			next;
			}
		#level 2 tags
		while($val=~m/\<(.+?)\s*(.*?)\>(.+?)\<\/\1\>/sig){
			my $key2=lc(strip($1));
			my $attribs2=strip($2);
			my $val2=strip($3);
			if(defined $xmlhash{$key}{$key2}){
				my $cnt=1;
				while(defined $xmlhash{$key}{"$key2\.$cnt"}){$cnt++;}
				$key2="$key2\.$cnt";
	            }
			if(length($attribs2)){
				my %ahash=parseAttributes($attribs2);
				foreach my $akey (keys(%ahash)){$xmlhash{$key}{$key2}{$akey}=$ahash{$akey};}
	        	}
	        #print "key2=$key2\n";
			if($val2=~/\<\!\[CDATA\[(.+?)\]\]\>/s){$val2=strip($1);}
			next if !length($val2);
			$xmlhash{$key}{$key2}{_value}=$val2;
			if(isXML($val2)){$xmlhash{$key}{$key2}{_xml}=$val2;}
			else{
				$xmlhash{$key}{$key2}{_value}=$val2;
				next;
				}
			#level 3 tags
			while($val2=~m/\<(.+?)\s*(.*?)\>(.+?)\<\/\1\>/sig){
				my $key3=lc(strip($1));
				my $attribs3=strip($2);
				my $val3=strip($3);
				if(defined $xmlhash{$key}{$key2}{$key3}){
					my $cnt=1;
					while(defined $xmlhash{$key}{$key2}{"$key3\.$cnt"}){$cnt++;}
					$key3="$key3\.$cnt";
		            }
				if(length($attribs2)){
					my %ahash=parseAttributes($attribs2);
					foreach my $akey (keys(%ahash)){$xmlhash{$key}{$key2}{$key3}{$akey}=$ahash{$akey};}
		        	}
		        #print "key3=$key3\n";
				if($val3=~/\<\!\[CDATA\[(.+?)\]\]\>/s){$val3=strip($1);}
				next if !length($val3);
				$xmlhash{$key}{$key2}{$key3}{_value}=$val3;
				if(isXML($val3)){$xmlhash{$key}{$key2}{$key3}{_xml}=$val3;}
				else{
					$xmlhash{$key}{$key2}{$key3}{_value}=$val2;
					next;
					}
				}
			}
		}
	return %xmlhash;
	}
#####################
sub parseAttributes{
	my $attribs=shift;
	my %hash=();
	while($attribs=~m/([a-z\_\-]+?)([\=\s\t]*?)\"(.*?)\"/sig){
		my $key=lc(strip($1));
		my $val=strip($3);
		$hash{$key}=$val;
		}
	return %hash;
	}
###############
sub postLWP{
	my $url=shift;
	my @urlParams=@_;
	#check to make sure LWP is installed
	#Note: to fetch secure sites you will need to install Crypt::SSLeay
	#	>ppm install http://theoryx5.uwinnipeg.ca/ppms/Crypt-SSLeay.ppd
	my $string="use " . "LWP" . "5.64"; 
	eval{$string};
	if($@){return $@;}
	my $browser = LWP::UserAgent->new;
	my $response = $browser->post( $url,[@urlParams] );
	if(!$response->is_success){
		my $errmsg="Can't get $url" . $response->status_line;
		return $errmsg;
		}
	if(wantarray){return %{$response};}
	return $response->decoded_content;
	}
#####################
sub writeXML{
	#usage: writeXML(\%hash,$file[,$roottag]);
	#info: writes the xml file
	#tags: xml, file
	my $hash=shift || return "No Hash";
	my $file=shift || return "No File";
	my $root=shift || "xmlroot";
	my @keys=keys(%{$hash});
	@keys=sortTextArray(@keys);
	open(FH,">$file") || return $^E;
	binmode FH;
	print FH qq|<?xml version="1.0" encoding="utf-8"?>\r\n|;
	print FH "<$root>\r\n";
	foreach my $key (@keys){
		my $val=$hash->{$key};
		if(isHash($val)){
			my @hkeys=keys(%{$val});
			@hkeys=sortTextArray(@hkeys);
			foreach my $hkey (@hkeys){
				my $hval=$val->{$hkey};
                if($hval=~/[\<\>]/){print FH qq|\t\t<$hkey><![CDATA[$hval]]></$hkey>\r\n|;}
				else{print FH qq|\t\t<$hkey>$hval</$hkey>\r\n|;}
            	}
        	}
		elsif($val=~/[\<\>]/){print FH qq|\t<$key><![CDATA[$val]]></$key>\r\n|;}
		else{print FH qq|\t<$key>$val</$key>\r\n|;}
    	}
    print FH "</$root>\r\n";
	close FH;
	return 1;
	}
#####################
sub genXML{
	#usage: genXML(\%hash[,$roottag]);
	#info: generates the xml file
	#tags: xml, file
	my $hash=shift || return "No Hash";
	my $root=shift || "xmlroot";
	my @keys=keys(%{$hash});
	@keys=sortTextArray(@keys);
	my $xml = qq|<?xml version="1.0" encoding="utf-8"?>\r\n|;
	$xml .=  "<$root>\r\n";
	foreach my $key (@keys){
		my $val=$hash->{$key};
		if(isHash($val)){
			my @hkeys=keys(%{$val});
			@hkeys=sortTextArray(@hkeys);
			foreach my $hkey (@hkeys){
				my $hval=$val->{$hkey};
                if($hval=~/[\<\>]/){$xml .=  qq|\t\t<$hkey><![CDATA[$hval]]></$hkey>\r\n|;}
				else{$xml .=  qq|\t\t<$hkey>$hval</$hkey>\r\n|;}
            	}
        	}
		elsif($val=~/[\<\>]/){$xml .=  qq|\t<$key><![CDATA[$val]]></$key>\r\n|;}
		else{$xml .=  qq|\t<$key>$val</$key>\r\n|;}
    	}
    $xml .=  "</$root>\r\n";
	return $xml;
	}
###############
sub removeEOLN{
	#usage: my $string=removeEOLN($string);
	#info: removed end of line, tabs, and spaces from $string
	#tags: parse, strip
	my $str=shift;
	if(length($str)==0){return;}
	#remove html tags
	$str=~s/[\r\n\s\t]+/\ /sg;
	return $str;
	}
###############
sub removeHtml{
	#usage: my $nohtml=removeHtml($html);
	#info: removed all html tags from string
	#tags: parse, strip
	my $str=shift;
	if(length($str)==0){return;}
	#remove html tags
	$str=~s/<.+?>//sg;
	return $str;
	}
###############
sub underMaintenance{
	my $note=shift;
	#usage: returns a under maintenance banner
	$rtn='';
	$rtn .= '<html>'."\n";
	$rtn .= '<head>'."\n";
	$rtn .= '	<link type="text/css" rel="stylesheet" href="/wfiles/min/index.php?g=w_Css" />'."\n";
	$rtn .= '	<script type="text/javascript" src="/wfiles/min/index.php?g=w_Js"></script>'."\n";
	$rtn .= '</head>'."\n";
	$rtn .= '<body>'."\n";
	$rtn .= '<div align="center">'."\n";
	$rtn .= '	<div style="width:600px;border:1px solid #000">'."\n";
	$rtn .= '		<div style="background:url(/wfiles/back_blue.jpg);height:100px;">'."\n";
	$rtn .= '			<div style="padding-top:15px;font-size:30pt;color:#FFF;">Under Maintenance</div>'."\n";
	$rtn .= '			<div style="padding-top:2px;font-size:15pt;color:#FFF;">' . $ENV{'HTTP_HOST'} . '</div>'."\n";
	$rtn .= '		</div>'."\n";
	$rtn .= '		<div align="left" style="padding:10px;font-size:15pt;">Our site is currently undergoing maintenance to upgrade our systems in order to better serve you.</div>'."\n";
	$rtn .= '		<div align="left" style="padding:10px;font-size:12pt;">We apologize for any inconvenience during this short outage and thank you in advance for your patience and understanding.</div>'."\n";
	if($note){
		$rtn .= '		<div align="left" style="padding:10px;font-size:16pt;" class="w_bold w_dblue">'.$note.'</div>'."\n";
    	}
	$rtn .= '		<div align="left" style="padding:10px;font-size:11pt;">Sincerely,<br><br>Customer Service Team</div>'."\n";
	$rtn .= '	</div>'."\n";
	$rtn .= '</div>'."\n";
	$rtn .= '</body></html>'."\n";
	return $rtn;
	}
###############
sub updateINI{
	#usage: my $section_count=updateINI($iniFile,$header,$key,$val);
	#info: updates inifile
	#tags: file, ini
	my $file=shift || return "No File";
	my $nheader=shift || return "No Header";
	my $nkey=shift || return "No key";
	my $nval=shift;
	open(INI,$file) || return "INI file $file is blank or does not exist";
	my @lines=<INI>;
	close(INI);

	my $header='';
	my $linecnt=@lines;
	if(!$linecnt){return "Empty ini File: $file";}
	my $change=0;
	my $foundheader=0;
	my $foundkey=0;
	#print "updateINI: nheader=[$nheader]\n";
	for(my $x=0;$x<$linecnt;$x++){
		$line=strip($lines[$x]);
		next if length($line)==0;
		next if $line=~/^[\#\;]/;
		if($line=~/^\[(.+?)\]$/s){
			my $name=$1;
			if($foundheader && !$foundkey && length($nval)){
				#Add new key value pair
				#print "Add new key/value pair: $nkey\=$nval\n";
				$lines[$x]="$nkey\=$nval\n" . $lines[$x];
				$change++;
				last;
				}

               $header=lc(strip($name));
               #print "Header Found: [$header]\n";
			next;
			}
		$header=lc(strip($header));
		next if length($header)==0;
		next if $header !~/^\Q$nheader\E$/is;
		$foundheader++;
		#print "Header: $header\n";
		#print "nval,[$nval], x=$x\n";
		if(length($nval)==0){
			$lines[$x]="$nkey\n";
			$change++;
			#print "$header\:\-\>$nkey\n";
			#remove any other entries in this header
			my $y=$x;
			while($y<$linecnt){
				$y++;
				last if $lines[$y]=~/^\[(.+?)\]$/s;
				delete($lines[$y]);
				}
			last;
			}
		if($line=~/\=/s){
			my ($key,$val)=split(/\=/,$line,2);
			$key=lc(strip($key));
			$val=strip($val);
			if($key=~/^\Q$nkey\E$/is){
				$lines[$x]="$key=$nval\n";
				$foundkey++;
				$change++;
				last;
				}
			}
		}
	if(!$foundheader){
		$change++;
		if($header=~/^\Q$nheader\E$/is){
			if(length($nval)==0){
				#print "Add to End\n$nkey\n\n";
				push(@lines,$nkey);
				}
			else{
				#print "Add to End\n$nkey=$nval\n\n";
				push(@lines,"$nkey\=$nval");
				}
			}
		else{
			push(@lines,"\n","[$nheader]");
			if(length($nval)==0){
				#print "Add to End\n$nkey\n\n";
				push(@lines,$nkey);
				}
			else{
				#print "Add to End\n$nkey=$nval\n\n";
				push(@lines,"$nkey\=$nval");
				}
			}
		}
	if($change && open(INF,">$file")){
		binmode(INF);
		foreach my $line (@lines){
			$line=strip($line);
			print INF "$line\r\n";
			}	
		close(INF);
		return 1;
	 	}
	return 0;
	}
##############
sub userEnv {
	my $rtn='';
	$rtn .= qq|<div style="font-size:10pt"><b><u>User Environment</u></b><br>\n|;
	my @keys=qw(REMOTE_ADDR REMOTE_BROWSER REMOTE_OS HTTP_REFERER HTTP_USER_AGENT QUERY_STRING REMOTE_OS_LANG REMOTE_OS_NAME REMOTE_OS_REVISION REMOTE_OS_TITLE REMOTE_PORT REQUEST_METHOD REQUEST_URI);
	@keys=sortTextArray(@keys);
	foreach my $env (@keys){
		$rtn .= qq|<b>$env: </b>$ENV{$env}<br>\n|;
		}
	$rtn .= qq|</div>\n|;
	return $rtn;
	}
################
sub redirSTDOUT{
	#usage: redirSTDOUT([$logfile]);
	#info: redirects STDOUT to $logfile.  $logfile defaults to  $progpath/$progname.log
	#tags: file, system
	my $append=shift;
	my $maxMB=shift || 5;
	my $LogFile="$progpath/$progname\.log";
	my $openfile=">$LogFile";
	if($append){
		$openfile=">$LogFile";
		#Check to see if $LogFile is larger than 5MB, If so rename it first
		my $max=int(1048576*$maxMB);
		if(-e $LogFile && -s $LogFile > $max){
			my $oldLog="$progpath/" . time() . "$progname\.log";
			rename($LogFile,$oldLog);
        	}
    	}
	if(open(STDOUT,$openfile)){
		select(STDOUT);
		$|=1;
		open(STDERR,$openfile);
		select(STDERR);
		$|=1;
		binmode(STDOUT);
		binmode(STDERR);
		print "$progname LOG FILE: " . localtime() . "\r\n";
		return 1;
		}
	return 0;
	}
################
sub redirSTDERR{
    #usage: redirSTDERR([$logfile]);
	#info: redirects STDERR to $logfile.  $logfile defaults to  $progpath/$progname.log
	#tags: file, system
	my $LogFile=shift || "$progpath/$progname\_err\.log";
	if(open(STDERR,">$LogFile")){
		select(STDERR);
		$|=1;
		return 1;
		}
	return 0;
	}
################
sub round{
	#usage: my $rnum=round($number[,$decimal]);
	#info: rounds $number to the nearest $decimal (defaults to 0)
	my $number = shift;
	my $decimal = shift || 0;
	my $format='%.'.$decimal.'f';
	return sprintf($format,$number);
	}
################
sub ceil{
	#usage: my $num=ceil($number);
	#info: rounds $number up to nearest whole number
	my $float = shift;
	my $number=$float;
	if($number==int($float)){}
	elsif($float>0){$number=int($float+1);}
	elsif($float<0){$number=int($float-1);}
	return $number;
	}
################
sub floor{
	#usage: my $num=floor($number);
	#info: rounds $number down to nearest whole number
	my $float = shift;
	return $float < 0 ? int($float) - 1 : int($float);
	}
################
sub rmDir{
	#usage my $ok=rmDir($dirpath);   or   my $ok=rmDir($file);
	#info: Recursively removes all files and subdirectories. 
	#info: Returns 1 if it passes or the error string on failure
	#tags: file, system
    my $dir = shift || return 0;
    my $FH;
    if (-f $dir){
        #$dir is a file
        unlink($dir) || return $!;
		}
	elsif (-d $dir && opendir($FH,$dir)) {
		#$dir is a directory - recurse through files and delete the files;
		my @cfiles=grep(/\w/,readdir($FH));
		closedir($FH);
		foreach my $cfile (@cfiles){&RmDir("$dir/$cfile");}
        rmdir($dir) || return $!;
    	}
    else{
	    return "$dir is not a file or a dir";
	    }
    return 1;
	}
sub RmDir{return rmDir(@_);}
################
sub buildCookie {
	#usage: &buildCookie(name=>$name [,value=>$value, expire=>$expireDays, path=>$path, domain=>$domain, secure=>1]);
	#info: builds a cookie string for writing to the header when a page is written to a browser
	#info: Also create an ENV value for cookie.
	#info: expire defaults to days but can also be specified in years, hours, minutes, or seconds. "1 year" or "4 hours" or "15 min" or "10 sec"
	#tag: cookie
	my %params=@_;
	return if length($params{name})==0;
	# Converts = in value
	$params{value} =~ s/\=/%3D/g;
	my $name=$params{name};
	#remove this cookie from set_cookie if it exists
	$ccnt=@{$ENV{SET_COOKIE}};
	$HEADER{$name}='';
	delete($HEADER{$name});
	$cgi->cookie(-name=>$name,-value=>'');
	if($ccnt){
	    my @Cookies=();
	    foreach $cookie (@{$ENV{SET_COOKIE}}){
			my ($ckey,$cval) = split(/\=/,$cookie);
			if($ckey=~/^\Q$name\E$/is){}
			else{push(@Cookies,"$ckey=$cval");}
			}
        @{$ENV{SET_COOKIE}}=@Cookies;
		}
	#Build cookie string
	my $string = "$name\=$params{value}\;";
	if($params{expire}){
		my $days=$params{expire};
		#print "exp=$params{expire}\n";
		#assume days unless specified
		#365=1 year, 15 min=15 minutes, 10 sec=10 seconds, 5 yrs=5 years
		my $seconds=0;
		if($params{expire}=~/([0-9]+)\ *?([a-z]+)/is){
			my $num=int(strip($1));
			my $type=strip($2);
			if($type=~/sec/is){$seconds=$num;}
			elsif($type=~/min/is){$seconds=int($num*60);}
			elsif($type=~/hours|hrs/is){$seconds=int($num*60*60);}
			elsif($type=~/day/is){$seconds=int($num*24*60*60);}
			elsif($type=~/year|yrs/is){$seconds=int($num*365*24*60*60);}
			}
		else{$seconds=int($days*24*60*60);}
		my $now=time();
		my $future=int($now+$seconds);
		#print "future=$future\n";
		my $exptime=&getDate('AD, ND-AM-YYYY MH:MM:SS GMT',$future);
		$string .= " expires=$exptime\;";
		}
	if($params{path}){$string .= " path=$params{path}\;";}
	if($params{domain}){$string .= " domain=$params{domain}\;";}
	if($params{secure}){$string .= " secure\;";}
	$string .= " ";
	#Add cookie value to HEADER hash
	push(@{$ENV{SET_COOKIE}},$string);
	$ENV{$name} = $params{value};
	return "Set-Cookie: " . $string;
	}
#############
sub convert2Bytes{
	#usage my $bytes=convert2Bytes("25 MB");
	my $str=shift || return 0;
	# 3 kb
	my $bytes=0;
	if($str=~/([0-9]+?)\ *?k/is){
		#kilobytes - 1 Kilobyte is 1024 Bytes
		my $num=int($1);
		$bytes=int($num*1024);
		}
	elsif($str=~/([0-9]+?)\ *?m/is){
		#megabytes
		my $num=int($1);
		$bytes=int($num*1024*1024);
		}
	elsif($str=~/([0-9]+?)\ *?g/is){
		#gigabytes
		my $num=int($1);
		$bytes=int($num*1024*1024*1024);
		}
	return $bytes;
	}

#############
sub convert2Seconds{
	#usage: my $s=convert2Seconds("12 months");
	my $timestr=shift || return 0;
	my $interval=0;
	if($timestr=~/([0-9]+?)\ *?mon/is){
		#months  12 mon | 12 months
		my $months=int($1);
		$interval=int(60*60*24*30*$months);
		}
	elsif($timestr=~/([0-9]+?)\ *?d/is){
		#days  4 d | 21 days
		my $days=int($1);
		$interval=int(60*60*24*$days);
		}
	elsif($timestr=~/([0-9]+?)\ *?h/is){
		#days  4 h | 21 hours
		my $num=int($1);
		$interval=int(60*60*$num);
		}
	elsif($timestr=~/([0-9]+?)\ *?min/is){
		#minutes  4 h | 21 hours
		my $num=int($1);
		$interval=int(60*$num);
		}
	return $interval;
	}
############
sub copyFile {
	#usage: $cp=copyFile($oldfile,$newfile[,$mode]);
	#info:  Copies $oldfile to newfile.
	#info:  If $mode=1, it will overwrite an existing file.
	#info:  Returns -1 if $oldfile does not exist, 1 if successful, 2 is it already existed, and 0 if failed.
	#tags: file, copy
	my ($old,$new,$mode)=@_;
	#unless mode==1, if $new exists, return 2
	if($mode!=1 && (-e $new || -d $new)){return 2;}
	my @oldfiles=();
	my @newfiles=();
	my $FH;
	#check to see if you are copying a directory
	if(-d $old){
		mkdir($new,0744) || return -2;
		opendir($FH,$old) || return -3;
		my @dfiles=grep(/\w/,readdir($FH));
		closedir($FH);
		foreach my $dfile (@dfiles){
			push(@oldfiles,"$old/$dfile");
			push(@newfiles,"$new/$dfile");
			}
		}
	else{
		push(@oldfiles,$old);
		push(@newfiles,$new);
		}
	my $fcount=@oldfiles;
	my $cnt=0;
	#print "FileCount: $fcount<br>\n";
	for(my $x=0;$x<$fcount;$x++){
		#print "Writefile: $newfiles[$x]<br>\n";
		open(WF,">>$newfiles[$x]")|| return "Writefile error: $^E";
		#print "Readfile: $oldfiles[$x]<br>\n";
		open(RF,$oldfiles[$x]) || return "Readfile error: $^E";
		binmode(RF);binmode(WF);
		while(<RF>){print WF $_;}
		close(RF);close(WF);
		$cnt++;
		}
	return $cnt;
	}
####################
sub csvParseFile {
	#internal usage: $cnt=csvParseFile(\%List,$csvfile);
	#internal info: builds a hash of csv values  hash{x}{field}=value. returns number of items found.
	my $List=shift;
	my $file=shift || return;
	if(!-s $file){return;}
	if(!open(CSV,$file)){return;}
	my $lcnt=0;
	%{$List}=();
	my @header=();
	my %Cols=();
	while(<CSV>){
		my $csvline=strip($_);
		#ignore comment lines
		next if $csvline=~/^[\#\;]/is;
		my @parts=csvParseLine($csvline);
		if($lcnt==0){@header=@parts;}
		else{
			my $hcnt=@header;
			for(my $x=0;$x<$hcnt;$x++){
				my $col=lc(strip($header[$x]));
				my $val=$parts[$x];
				$Cols{$col}=1;
				if(length($val) && length($col)){$List->{$lcnt}{$col}=$val;}
				}
			}
		$lcnt++;
		}
	close(CSV);
	#build special values - columns and count.
	my @cols=sort(keys(%Cols));
	$List->{fields}=[@cols];
	$List->{count}=$lcnt;
	return $lcnt;
	}
####################
sub csvParseLine{
	#internal usage: my @parts=csvParseLine($csvline);
	#internal info: parses a csv line and returns it as an array.
	my $line=shift || return;
	$line=~s/""/\%02/g;
    while($line=~/("[^"]*?")/){
        my $match=$1;
        $match=~s/\"//g;
        $match=~s/\,/\%01/g;
        $line=~s/("[^"]*")/$match/;
        }
    #split by commas and assign. Note: split function will not return proper fields if only commas ,,,,,
	my @parts=();
	while($line=~/(.*?)\,/sig){
		my $tag=$&;
		my $val=$1;
		$val=~s/\%01/\,/sg;
		$val=~s/\%02/\"/sg;
		$val=~s/^\"$//s;
		push(@parts,$val);
		$line=~s/\Q$tag\E//is;
		}
	#Get last item on end of $line.
	if(length($line)){
		$line=~s/\%01/\,/sg;
		$line=~s/\%02/\"/sg;
		$line=~s/^\"$//s;
		push(@parts,$line);
		}
	return @parts;
	}
################
sub checkDebug{
	#Debug settings:
	# 0 - off
	# 1 - print or show sub file and line
	# 2 - print or show sub file and line and pause after each
	# if the debug value is followed by a :string then only show if the package/sub contains string
	# if the debug value is followed by a :number then only show if the current line is greater
	my $in=shift;
	my @msgs=@_;
	#($package, $filename, $line, $subroutine,$hasargs, $wantarray, $evaltext, $is_require) = caller(1);
	my ($package, $filename, $line) = caller();
	if(!$in){return 1;}
	my ($debug,$str)=split(/\:/,$in,2);
	#skip if debug=0 or is blank
	return 0 if !isNum($debug) || $debug==0;
	#check for line arg
	if(isNum($str) && $str < $line){return 1;}
	elsif(length($str)){
		return 1 if $package!~/\Q$str\E/is && $filename!~/\Q$str\E/is;
    	}
    my $ctime=time();
	my $cdate=getDate("ND/NM/YY MH:MM:SS");
	my $sub=$subroutine || $package;
    my $msg=qq|$sub line $line|;
    if(scalar @msgs){$msg .= "[" . join(',',@msgs) . "]";}
    $msg .= " at $cdate";
    if($debug==1){
		print "$msg\r\n";
		return 1;
    	}
	elsif($debug==2){
		print "$msg\r\n";
		print "\tPress <ENTER> to continue\r\n";
		my $x=<STDIN>;
		}
	return 1;
	}
################
sub decodeBase64{
	#usage: $string=decodeBase64($encs);
	#info: decodes a string that was encoded with Base64 encoding.
    local($^W) = 0; # unpack("u",...) gives bogus warning in 5.00[123]
    my $str = shift;
    my $res = "";
    $str =~ tr|A-Za-z0-9+=/||cd;            # remove non-base64 chars
    $str =~ s/=+$//;                        # remove padding
    $str =~ tr|A-Za-z0-9+/| -_|;            # convert to uuencoded format
    while ($str =~ /(.{1,60})/gs) {
        my $len = chr(32 + length($1)*3/4); # compute length byte
        $res .= unpack("u", $len . $1 );    # uudecode
    }
    $res;
	}
###############
sub decodeQP {
	#usage: $dstring=decodeQP($string);
	#info: decode a Quoted Printed string
	#tags: decode
    my $string = shift;
    $string =~ s/[ \t]+?(\r?\n)/$1/g;  # rule #3 (trailing space must be deleted)
    $string =~ s/=\r?\n//g;            # rule #5 (soft line breaks)
    $string =~ s/=([\da-fA-F]{2})/pack("C", hex($1))/ge;
    return $string;
	}
################
sub encodeBase31{
	#usage: my $enc=encodeBase31($number);
	#info: convert a decimal number into a string using Base31 encoding
	#info: Base31 encoding removes problems that may arise where a number encoded is a bad word
	#tags: encode
	my $decimal=shift || return "No number to encode to base36";
	my $string = '';
	$base = 31;
	my $decimal=strip($decimal);
    if ($decimal !~/^[0-9]{1,16}$/){
		return 'Value must be a positive integer';
		}
	#maximum character string is 36 characters
	my $charset = '0123456789BCDFGHJKLMNPQRSTVWXYZ';
	do {
		#get remainder after dividing by BASE
	    my $remainder = $decimal % $base;
		# get CHAR from array
	    my $char      = substr($charset, $remainder, 1);
		#prepend to output
	    $string    = "$char$string";
		$decimal   = ($decimal - $remainder) / $base;
	   }
	while ($decimal > 0);
	return $string;
	}
sub strlen{
	my $string=shift;
	return length($string);
	}
sub strtolower{
	my $str=shift;
	return uc($str);
	}
#-----------------------
sub encrypt{
	my ($string,$key)=@_;
	my $result='';
	for($i=0; $i<strlen($string); $i++) {
		my $char = substr($string, $i, 1);
		my $keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char)+ord($keychar));
		$result.=$char;
		}
	return encodeBase64($result);
	}
#-----------------------
sub decrypt{
	my ($string,$key)=@_;
	my $result = '';
	$string = decodeBase64($string);
	for($i=0; $i<strlen($string); $i++) {
		my $char = substr($string, $i, 1);
		my $keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char)-ord($keychar));
		$result.=$char;
		}
	return $result;
	}
#-----------------------
sub decodeBase31{
    #usage: my $string=decodeBase31($enc);
    #info: convert a Base 31 encoded string into a number
	#info: Base31 encoding removes problems that may arise where a number encoded is a bad word
	#tags: decode
	my $string=shift;
	if(!length(strip($string))){return "No base36 string to decode";}
	my $decimal = 0;
	my $base=31;
	#maximum character string is 36 characters
	my $charset = '0123456789BCDFGHJKLMNPQRSTVWXYZ';
	do {
		#extract leading character
		my $char   = substr($string, 0, 1);
		#drop leading character
		$string = substr($string, 1);
		#get offset in $charset
		$pos = index($charset, $char);
	    if ($pos == -1) {
			return "Illegal character ($char) in INPUT string";
	      }
		$decimal = ($decimal * $base) + $pos;
		} 
	while(length($string));
	return $decimal;
	}
########################################
sub encodeBase64($;$){
	#usage: $enc=encodeBase64($string);
	#info: encodes $string to Base64 encoding
	#tags: encode
	#Note: this routine does not handle encoding large data well. use encode_base64 from MIME instead
    my $res = "";
    my $eol = $_[1];
    $eol = "\n" unless defined $eol;
    pos($_[0]) = 0;                          # ensure start at the beginning
    while ($_[0] =~ /(.{1,45})/gs) {
        $res .= substr(pack('u', $1), 1);
        chop($res);
    	}
    $res =~ tr|` -_|AA-Za-z0-9+/|;               # `# help emacs
    # fix padding at the end
    my $padding = (3 - length($_[0]) % 3) % 3;
    $res =~ s/.{$padding}$/'=' x $padding/e if $padding;
    # break encoded string into lines of no more than 76 characters each
    if (length $eol) {$res =~ s/(.{1,76})/$1$eol/g;}
    $res;
	}
###############
sub encodeCRC_Table{
	my @table=(
    0x00000000, 0x04c11db7, 0x09823b6e, 0x0d4326d9,
    0x130476dc, 0x17c56b6b, 0x1a864db2, 0x1e475005,
    0x2608edb8, 0x22c9f00f, 0x2f8ad6d6, 0x2b4bcb61,
    0x350c9b64, 0x31cd86d3, 0x3c8ea00a, 0x384fbdbd,
    0x4c11db70, 0x48d0c6c7, 0x4593e01e, 0x4152fda9,
    0x5f15adac, 0x5bd4b01b, 0x569796c2, 0x52568b75,
    0x6a1936c8, 0x6ed82b7f, 0x639b0da6, 0x675a1011,
    0x791d4014, 0x7ddc5da3, 0x709f7b7a, 0x745e66cd,
    0x9823b6e0, 0x9ce2ab57, 0x91a18d8e, 0x95609039,
    0x8b27c03c, 0x8fe6dd8b, 0x82a5fb52, 0x8664e6e5,
    0xbe2b5b58, 0xbaea46ef, 0xb7a96036, 0xb3687d81,
    0xad2f2d84, 0xa9ee3033, 0xa4ad16ea, 0xa06c0b5d,
    0xd4326d90, 0xd0f37027, 0xddb056fe, 0xd9714b49,
    0xc7361b4c, 0xc3f706fb, 0xceb42022, 0xca753d95,
    0xf23a8028, 0xf6fb9d9f, 0xfbb8bb46, 0xff79a6f1,
    0xe13ef6f4, 0xe5ffeb43, 0xe8bccd9a, 0xec7dd02d,
    0x34867077, 0x30476dc0, 0x3d044b19, 0x39c556ae,
    0x278206ab, 0x23431b1c, 0x2e003dc5, 0x2ac12072,
    0x128e9dcf, 0x164f8078, 0x1b0ca6a1, 0x1fcdbb16,
    0x018aeb13, 0x054bf6a4, 0x0808d07d, 0x0cc9cdca,
    0x7897ab07, 0x7c56b6b0, 0x71159069, 0x75d48dde,
    0x6b93dddb, 0x6f52c06c, 0x6211e6b5, 0x66d0fb02,
    0x5e9f46bf, 0x5a5e5b08, 0x571d7dd1, 0x53dc6066,
    0x4d9b3063, 0x495a2dd4, 0x44190b0d, 0x40d816ba,
    0xaca5c697, 0xa864db20, 0xa527fdf9, 0xa1e6e04e,
    0xbfa1b04b, 0xbb60adfc, 0xb6238b25, 0xb2e29692,
    0x8aad2b2f, 0x8e6c3698, 0x832f1041, 0x87ee0df6,
    0x99a95df3, 0x9d684044, 0x902b669d, 0x94ea7b2a,
    0xe0b41de7, 0xe4750050, 0xe9362689, 0xedf73b3e,
    0xf3b06b3b, 0xf771768c, 0xfa325055, 0xfef34de2,
    0xc6bcf05f, 0xc27dede8, 0xcf3ecb31, 0xcbffd686,
    0xd5b88683, 0xd1799b34, 0xdc3abded, 0xd8fba05a,
    0x690ce0ee, 0x6dcdfd59, 0x608edb80, 0x644fc637,
    0x7a089632, 0x7ec98b85, 0x738aad5c, 0x774bb0eb,
    0x4f040d56, 0x4bc510e1, 0x46863638, 0x42472b8f,
    0x5c007b8a, 0x58c1663d, 0x558240e4, 0x51435d53,
    0x251d3b9e, 0x21dc2629, 0x2c9f00f0, 0x285e1d47,
    0x36194d42, 0x32d850f5, 0x3f9b762c, 0x3b5a6b9b,
    0x0315d626, 0x07d4cb91, 0x0a97ed48, 0x0e56f0ff,
    0x1011a0fa, 0x14d0bd4d, 0x19939b94, 0x1d528623,
    0xf12f560e, 0xf5ee4bb9, 0xf8ad6d60, 0xfc6c70d7,
    0xe22b20d2, 0xe6ea3d65, 0xeba91bbc, 0xef68060b,
    0xd727bbb6, 0xd3e6a601, 0xdea580d8, 0xda649d6f,
    0xc423cd6a, 0xc0e2d0dd, 0xcda1f604, 0xc960ebb3,
    0xbd3e8d7e, 0xb9ff90c9, 0xb4bcb610, 0xb07daba7,
    0xae3afba2, 0xaafbe615, 0xa7b8c0cc, 0xa379dd7b,
    0x9b3660c6, 0x9ff77d71, 0x92b45ba8, 0x9675461f,
    0x8832161a, 0x8cf30bad, 0x81b02d74, 0x857130c3,
    0x5d8a9099, 0x594b8d2e, 0x5408abf7, 0x50c9b640,
    0x4e8ee645, 0x4a4ffbf2, 0x470cdd2b, 0x43cdc09c,
    0x7b827d21, 0x7f436096, 0x7200464f, 0x76c15bf8,
    0x68860bfd, 0x6c47164a, 0x61043093, 0x65c52d24,
    0x119b4be9, 0x155a565e, 0x18197087, 0x1cd86d30,
    0x029f3d35, 0x065e2082, 0x0b1d065b, 0x0fdc1bec,
    0x3793a651, 0x3352bbe6, 0x3e119d3f, 0x3ad08088,
    0x2497d08d, 0x2056cd3a, 0x2d15ebe3, 0x29d4f654,
    0xc5a92679, 0xc1683bce, 0xcc2b1d17, 0xc8ea00a0,
    0xd6ad50a5, 0xd26c4d12, 0xdf2f6bcb, 0xdbee767c,
    0xe3a1cbc1, 0xe760d676, 0xea23f0af, 0xeee2ed18,
    0xf0a5bd1d, 0xf464a0aa, 0xf9278673, 0xfde69bc4,
    0x89b8fd09, 0x8d79e0be, 0x803ac667, 0x84fbdbd0,
    0x9abc8bd5, 0x9e7d9662, 0x933eb0bb, 0x97ffad0c,
    0xafb010b1, 0xab710d06, 0xa6322bdf, 0xa2f33668,
    0xbcb4666d, 0xb8757bda, 0xb5365d03, 0xb1f740b4
	);
	return @table;
	}
sub encodeCRC_Reflect{
    my( $data, $nBits ) = @_;
    my $pat1 = $nBits == 8 ? 'C' : 'N';
    my $pat2 = $nBits == 8 ? 'B8' : 'B32';
    return unpack $pat1, pack $pat2,
        scalar reverse unpack $pat2, pack $pat1 , $data;
	}
sub encodeCRC {
    my( $message ) = shift;
    my $nBytes = length $message;
    my $remainder = 0xFFFFFFFF;
    my $data;
	my @crcTable=encodeCRC_Table();
    for my $byte( unpack 'C*', $message ) {
        $data = encodeCRC_Reflect( $byte, 8 ) ^ ( $remainder >> ( 24 ) );
        $remainder = $crcTable[$data] ^ ( $remainder << 8 );
    	}
    return abs(encodeCRC_Reflect( $remainder, 32 ) ^ 0xFFFFFFFF);
	}
###############
sub encodeHtml {
	#usage: $html=encodeHtml($html)
	#info: encode html chars that would mess in  a browser
	#tags: encode
	my $string=shift;
	if(length($string)==0){return $string;}
	my %char=();
	$char{"\<"}='&#60;';
	$char{"\>"}='&#62;';
	while($string=~m/\<(.+?)\>/sig){
		my $ctag=$&;
		my $newtag=$ctag;
		my $tagval=$1;
		next if $tagval=~/^br$/i;
		foreach my $ch (keys(%char)){
			$newtag=~s/\Q$ch\E/$char{$ch}/g;
			}
		$string=~s/\Q$ctag\E/$newtag/sig;
		}
	%char=();
	$char{"\@"}='&#64;';
	$char{"\("}='&#40;';
	$char{"\)"}='&#41;';
	$char{"\""}='&#34;';
	foreach my $ch (keys(%char)){$string=~s/\Q$ch\E/$char{$ch}/g;}

	return $string;
	}
####################  Quoted Print Encode/Decode ##########
sub encodeQP {
	#usage: $estring=encodeQP($string);
	#info: encodes a string into Quoted Printable format
	#tags: encode
    my $string = shift;
    $string =~ s/([^ \t\n!-<>-~])/sprintf("=%02X", ord($1))/eg;  # rule #2,#3
    $string =~ s/([ \t]+)$/
    	join('', map { sprintf("=%02X", ord($_)) }
	   	split('', $1)
      	)/egm;                        # rule #3 (encode whitespace at eol)
    # rule #5 (lines must be shorter than 76 chars, but we are not allowed
    # to break =XX escapes.  This makes things complicated :-( )
    my $brokenlines = "";
    $brokenlines .= "$1=\n"
	while $string=~s/(.*?^[^\n]{73} (?:
		 [^=\n]{2} (?! [^=\n]{0,1} $) # 75 not followed by .?\n
		|[^=\n]    (?! [^=\n]{0,2} $) # 74 not followed by .?.?\n
		|          (?! [^=\n]{0,3} $) # 73 not followed by .?.?.?\n
	    ))//xsm;
	return "$brokenlines$string";
	}
####################
sub encodeSpecialChars{
	my $str=shift;
	$str=~s/\&/\&amp\;/sg;
	$str=~s/\</\&lt\;/sg;
	$str=~s/\>/\&gt\;/sg;
	$str=~s/\™/\&trade\;/sg;
	$str=~s/\©/\&copy\;/sg;
	$str=~s/\®/\&reg\;/sg;
	$str=~s/\»/\&raquo\;/sg;
	$str=~s/\«/\&laquo\;/sg;
	$str=~s/\"/\&quot\;/sg;
	$str=~s/\—/\&mdash\;/sg;
	return $str;
	}
####################
sub encodeText{
	#internal info: Encode and decode large blocks of text using Base64 Encoding with a twist to enable keys.
	my $text=shift;
	my $offset=shift;
    my $enctext = encodeMapText($text,$offset);
    return "<encmt>" . $enctext . "</encmt>";
	}
####################
sub decodeText {
	#internal info: Encode and decode large blocks of text using Base64 Encoding with a twist to enable keys.
	my $text=shift;
	my $offset=shift;
	my $key="IGJlbG93LCB3ZSB3aWxAIHVzZSB0aGUgQ9J5cHQ6OlR3b2Zpc2XgbW9kdWxlLiBUd29muXNoIGlz";
	my $rtnstr='';
	my $found=0;
	while($text=~m/<enc>(.+)<\/enc>/sig){
		my $str=$1;
		if($str=~/\Q$key\E$/s){
			$found++;
			$str=~s/\Q$key\E$//s;
			$rtnstr .= decodeBase64($str);
        	}
    	}
    while($text=~m/<encmt>(.+)<\/encmt>/sig){
		my $str=$1;
		$rtnstr .= decodeMapText($str,$offset);
		$found++;
    	}
    if(!$found){return $text;}
    return $rtnstr;
	}
####################
sub encodeMapText{
	#internal info: Encode  in a way that still allows text search. Not very strong encryption but it keeps prying eyes away.
	my $text=shift;
	my $offset=shift || 17;
	if(!isNum($offset)){$offset=17;}
	my %Map=();
	my @letters=(a..z,A..Z);
	my @letters_map=();
	my @pre=();
	for(my $x=0;$x<scalar @letters;$x++){
		if($x < $offset){push(@pre,$letters[$x]);}
		else{push(@letters_map,$letters[$x]);}
		}
	push(@letters_map,@pre);
	for(my $x=0;$x<scalar @letters;$x++){
		$Map{$letters[$x]}=$letters_map[$x];
    	}
	my @text_chars=split(//,$text);
	my $encoded='';
	foreach my $char (@text_chars){
		if(length($Map{$char})){$encoded .= $Map{$char};}
		else{
			$encoded .= $char;
			}
    	}
    return $encoded;
	}
###################
sub decodeMapText{
	my $text=shift;
	my $offset=shift || 17;
	if(!isNum($offset)){$offset=17;}
	my %Map=();
	my @letters=(a..z,A..Z);
	my @letters_map=();
	my @pre=();
	for(my $x=0;$x<scalar @letters;$x++){
		if($x < $offset){push(@pre,$letters[$x]);}
		else{push(@letters_map,$letters[$x]);}
		}
	push(@letters_map,@pre);
	for(my $x=0;$x<scalar @letters;$x++){
		$Map{$letters_map[$x]}=$letters[$x];
    	}
	my @text_chars=split(//,$text);
	my $decoded='';
	foreach my $char (@text_chars){
		if(length($Map{$char})){$decoded .= $Map{$char};}
		else{
			$decoded .= $char;
			}
    	}
    return $decoded;
	}
###############
sub encodeURL {
	#usage: $string=encodeURL($string);
	#info: url encodes specified string
	#tags: encode, url
	my $esc = shift;
    $esc =~ s/^\s+|\s+$//gs;
    $esc =~ s/([^a-zA-Z0-9_\-.])/uc sprintf("%%%02x",ord($1))/eg;
    $esc =~ s/ /\+/g;
    $esc =~ s/%20/\+/g;
    return $esc;
	}
##############
sub decodeURL{
	#usage: my $string=decodeURL($url);
	#info: decodes a url into key=value&key2=value2 string
	#tags: decode, url
    my $in=shift;
    #%26 is &, so convert & to <~amp~>
    $in =~ s/\%26/\<\~amp\~\>/g;
    $in =~ s/\%amp\%/\&/g;
	$in =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;
	$in =~ s/\<\~amp\~\>/\&/ig;
	return $in;
	}
###########
sub evalPerl{
	#internal usage: my $body=evalPerl($body);
	#internal info: processes perl inside of perl tags found in $body.  (<Perl>code goes here</perl>)
	#internal Note: How to process PHP code. Take PHP code <?php(.+?)?> and place the code in a file at the webroot and then use wget to view the page for its output then return the output.
	my $str=shift; 
	#Check for ELS - Embedded Language Script (PHP, ASP, pl, cgi, etc...)
	while ($str=~/\<\?([a-z]+?)[\r\n](.+?)\?\>/sig){
		my $tag=$&;
		my $ext=$1;
		my $code=strip($2);
		my $els=uc($ext);
		my $result="Error: Unable to process Embedded $els Script";
		if(-d $docroot){
			my $file=encodeCRC($tag . time() . $ENV{GUID}) . ".$ext";
			my $afile="$docroot/" . $file;
			unlink($afile) if -e $afile;
			if(open(ELS,">$afile")){
				binmode(ELS);
				print ELS qq|\<\?php\n|;
				print ELS $code;
				print ELS qq|\?\>\n|;
				close(ELS);
				my $url="http://$ENV{HTTP_HOST}/$file";
				my ($head,$body,$code)=getURL($url);
				if($code==200){$result=$body;}
                else{$result="ELS $code Error processing Embedded $els Script [$file]<hr><pre>$head</pre>$body";}
                unlink($afile);
            	}
            else{$result="Embedded $els Script Open File Error: $^E";}
        	}
        $str=~s/\Q$tag\E/$result/is;
    	}
	#Process embeded Perl
	while ($str=~/\<perl(.*?)\>(.+?)\<\/perl\>/sig){
		my $tag=$&;
		my $paramstr=strip($1);

		my $code=strip($2);
		#remove any beginning and ending comments <!--  -->
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		my $result=evalCode($code,1);
		if(length($result) && length($paramstr) && $paramstr=~/id\=\"(.+?)\"/is){
			my $id=lc(strip($1));
			if(length($id)){$PerlID{$id}=$result;}
	        }
		$str=~s/\Q$tag\E/$result/is;
		}
	while ($str=~/\<\?(.+?)\?\>/sig){
		my $tag=$&;
		my $code=strip($1);
		#Do not process xml defination strings as Perl
		next if $code=~/^(xml|php)\ /is;
		#Remove comment tag if it exists
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		my $result=evalCode($code,1);
		$str=~s/\Q$tag\E/$result/is;
		}
	return $str;
	}
############
sub evalCode{
	#internal usage: $result=evalCode($code);
	#internal info: Pass in @eval string. returns value or error.
	my $evalstr=shift || return;
	my $return=shift;
	my $val=eval($evalstr);
	if ($@){
		my $error=$@;
		$val=qq|<div style="border:1px dashed #999999;padding:5px;"><b style="color:#ff0000">\*</b><b style="color:#336699"> There is an error in your code.</b> <b style="color:#ff0000">\*</b><br>\n|;
		$val .= qq|$error<br>|;
		my $eline;
		if($error=~/\)\ line\ ([0-9]+?)[\.\,]/is){
			$eline=int($1);
			$val .= qq|<div style="font-size:11px;">(Line number $eline has been highlighted below.)</div>\n|;
			}
		$val .= qq|<hr size="1">\n|;
		$val .= qq|<b style="color:#336699">Code With Errors:</b>\n|;
		my @lines=split(/[\r\n]+/,$evalstr);
		my $linecnt=0;
		$val .= qq|<table cellspacing="0" cellpadding="0" border="0">\n|;
		foreach my $line (@lines){
			$line=strip($line);
			$linecnt++;
			$val .= qq|<tr><td bgcolor="#336699" style="font-size:11px;padding-right:3px;padding-left:3px;color:#FFFFFF;" align="right">$linecnt</td>|;
			if($eline && $eline==$linecnt){
				$val .= qq|<td style="padding-left:3px;border-bottom:1px solid #ff0000;" bgcolor="#ffffcc">|;
				}
			elsif($line=~/^\#/){
				$val .= qq|<td style="padding-left:3px;color:#006600;">|;
				}
			else{$val .= qq|<td style="padding-left:3px;">|;}
			$val .= encodeHtml(strip($line));
			$val .= qq|</td></tr>\n|;
			}
		$val .= qq|</table>\n|;
		$val .= qq|</div>\n\n|;
		if(!$return){
			printHeader("$progname - Code Error");
			print $val;
			printFooter();
			exit;
			}
		}
	return $val;
	}
################
sub fileStats{
	my %params=@_;
	my $file=$params{-file};
	my $dtstr=$params{-date};
	my %stats=();
	my $err=fileStat(\%stats,$file,$dtstr);
	if(length($err)){
		$stats{-error}=$err;
    	}
	return %stats;
	}
################
sub fileStat{
	#usage: my $err=fileStat(\%hash,$file);
	#info: return a hash with the stat info for the specified file
	#tags: file
	my $fstat=shift || return "No hash";
	my $file=shift || return "No File passed";
	my $dtstr=shift || "FD, FM ND, YYYY, RH:MM:SS PM";
	%{$fstat}=();
	#$file=~s/[\/\\]+/\//sg;
	if(!-e $file){return "$file does not exist";}
	#use the special _ variable to use the stat in the -e on the line above..
	my @stats = stat(_);
	# 	0 dev      device number of filesystem
	$fstat->{dev}=$stats[0];
	# 	1 ino      inode number
	$fstat->{ino}=$stats[1];
	# 	2 mode     file mode  (type and permissions)
	$fstat->{mode}=$stats[2];
	my $octmode=sprintf("%o", $stats[2]);
	$fstat->{permissions}=substr($octmode,length($octmode)-3,length($octmode));
	#determine rwx
	my $ck=substr($fstat->{permissions},0,1);
	$fstat->{readable}=$ck=~/(4|5|6|7)/?1:0;
	$fstat->{writable}=$ck=~/(2|3|6|7)/?1:0;
	$fstat->{executable}=$ck=~/(1|3|5|7)/?1:0;
	# 	3 nlink    number of (hard) links to the file
	$fstat->{nlink}=$stats[3];
	# 	4 uid      numeric user ID of file's owner
	$fstat->{uid}=$stats[4];
	#getpwuid only works in unix perl
	$OS ||= uName();
	#if($OS !~ /windows/is){$fstat->{owner}=getpwuid($stats[4]);}

	# 	5 gid      numeric group ID of file's owner
	$fstat->{gid}=$stats[5];
	#getgrgid only works in unix perl
	#if($OS !~ /windows/is){$fstat->{group}=getgrgid($stats[4]);}
	# 	6 rdev     the device identifier (special files only)
	$fstat->{rdev}=$stats[6];
	# 	7 size     total size of file, in bytes
	$fstat->{size}=$stats[7];
	$fstat->{size_kb}=sprintf("%.0f",($stats[7]/1024)) || 0;
	$fstat->{size_mb}=sprintf("%.1f",($stats[7]/1024/1024)) || 0;
	$fstat->{size_gb}=sprintf("%.2f",($stats[7]/1024/1024/1024)) || 0;
	# 	8 atime    last access time since the epoch
	$fstat->{atime}=$stats[8];
	$fstat->{accessed}=getDate($dtstr,$stats[8]);
	# 	9 mtime    last modify time since the epoch
	$fstat->{mtime}=$stats[9];
	$fstat->{modified}=getDate($dtstr,$stats[9]);
	# 	10 ctime    inode change time (NOT creation time!) since the epoch
	$fstat->{ctime}=$stats[10];
	$fstat->{created}=getDate($dtstr,$stats[10]);
	# 	11 blksize  preferred block size for file system I/O
	$fstat->{blksize}=$stats[11];
	# 	12 blocks   actual number of blocks allocated
	$fstat->{blocks}=$stats[12];
	#File name
	my @tmp=split(/[\/\\]/,$file);
	$fstat->{file}=$file;
	$fstat->{name}=pop(@tmp);
	#File Type and Path
	$fstat->{type}="unknown";
	if(-S _){
		$fstat->{type}="Socket";
		}
	elsif(-p _){
		$fstat->{type}="Named Pipe";
		}
	elsif(-d _){
		$fstat->{type}="Directory";
		}
	elsif(-T _){
		$fstat->{type}="Text";
		}
	elsif(-B _){
		$fstat->{type}="Binary";
		}
	# Path
	if($OS=~/^windows$/is){$fstat->{path}=join("\\",@tmp);}
	else{$fstat->{path}=join('/',@tmp);}
	#Parent
    $fstat->{parent}=pop(@tmp);
    #ParentPath
    if($OS=~/^windows$/is){$fstat->{parentpath}=join("\\",@tmp);}
	else{$fstat->{parentpath}=join('/',@tmp);}
	#File extension
	@tmp=split(/\.+/,$fstat->{name});
	$fstat->{extension}=pop(@tmp);
	return;
	}
################
sub fixCR{
	#usage: fixCR($file);
	#info: Fixes carriage returns to match the current operating system
	my $file=shift;
	return 0 if !-e $file;
	my $end="\n";
	if($^O =~ /^MSWIN32$/is){$end="\r\n";}
	my @lines=getFileContents($file);
	if(!open(FH,">$file")){return 0;}
	binmode FH;
	foreach my $line (@lines){
		$line=~s/[\r\n]+$//s;
		print FH $line . $end;
       	}
	close(FH);
	return 1;
	}
################
sub fixMicrosoft{
	#usage: fix Microsoft quotes
	my $s=shift;
    #   Map strategically incompatible non-ISO characters in the
    #   range 0x82 -- 0x9F into plausible substitutes where
    #   possible.
	$s =~ s/â€“/-/g;
	$s =~ s/â€”/-/g;
    $s =~ s/\x82/,/g;
    $s =~ s-\x83-<em>f</em>-g;
    $s =~ s/\x84/,,/g;
    $s =~ s/\x85/.../g;
    $s =~ s/\x88/^/g;
    $s =~ s-\x89- °/°°-g;
    $s =~ s/\x8B/</g;
    $s =~ s/\x8C/Oe/g;
    $s =~ s/\x91/`/g;
    $s =~ s/\x92/'/g;
    $s =~ s/\x93/"/g;
    $s =~ s/\x94/"/g;
    $s =~ s/\x95/*/g;
    $s =~ s/\x96/-/g;
    $s =~ s/\x97/--/g;
    $s =~ s-\x98-<sup>~</sup>-g;
    $s =~ s-\x99-<sup>TM</sup>-g;
    $s =~ s/\x9B/>/g;
    $s =~ s/\x9C/oe/g;

    #   Supply missing semicolon at end of numeric entity if
    #   Billy's bozos left it out.

    $s =~ s/(&#[0-2]\d\d)\s/$1; /g;

    #   Fix dimbulb obscure numeric rendering of &lt; &gt; &amp;
    $s =~ s/&#038;/&amp;/g;
    $s =~ s/&#060;/&lt;/g;
    $s =~ s/&#062;/&gt;/g;
    #	Translate Unicode numeric punctuation characters into ISO equivalents

    $s =~ s/&#8208;/-/g;    	# 0x2010 Hyphen
    $s =~ s/&#8209;/-/g;    	# 0x2011 Non-breaking hyphen
    $s =~ s/&#8211;/--/g;   	# 0x2013 En dash
    $s =~ s/&#8212;/--/g;   	# 0x2014 Em dash
    $s =~ s/&#8213;/--/g;   	# 0x2015 Horizontal bar/quotation dash
    $s =~ s/&#8214;/||/g;   	# 0x2016 Double vertical line
    $s =~ s-&#8215;-<U>_</U>-g; # 0x2017 Double low line
    $s =~ s/&#8216;/`/g;    	# 0x2018 Left single quotation mark
    $s =~ s/&#8217;/'/g;    	# 0x2019 Right single quotation mark
    $s =~ s/&#8218;/,/g;    	# 0x201A Single low-9 quotation mark
    $s =~ s/&#8219;/`/g;    	# 0x201B Single high-reversed-9 quotation mark
    $s =~ s/&#8220;/"/g;    	# 0x201C Left double quotation mark
    $s =~ s/&#8221;/"/g;    	# 0x201D Right double quotation mark
    $s =~ s/&#8222;/,,/g;    	# 0x201E Double low-9 quotation mark
    $s =~ s/&#8223;/"/g;    	# 0x201F Double high-reversed-9 quotation mark
    $s =~ s/&#8226;/&#183;/g;  	# 0x2022 Bullet
    $s =~ s/&#8227;/&#183;/g;  	# 0x2023 Triangular bullet
    $s =~ s/&#8228;/&#183;/g;  	# 0x2024 One dot leader
    $s =~ s/&#8229;/../g;  	# 0x2026 Two dot leader
    $s =~ s/&#8230;/.../g;  	# 0x2026 Horizontal ellipsis
    $s =~ s/&#8231;/&#183;/g;  	# 0x2027 Hyphenation point
	$s =~ s/&amp;/&/g;
    return $s;
    }
################
sub formatError{
	#internal usage: print formatError($@);   or    my $errstr=formatError($@); print $errstr;
	#internal info:Formats eval errors into a more readable format and highlights the line with the error.
	#internal info:Returns a string that can be printed.
	my $error=shift || return;
	my $val=qq|<div style="border:1px dashed #999999;padding:5px;"><b style="color:#ff0000">\*</b><b style="color:#336699"> There is an error in your code.</b> <b style="color:#ff0000">\*</b><br>\n|;
	$val .= qq|$error<br>|;
	my $eline;
	if($error=~/\)\ line\ ([0-9]+?)[\.\,]/is){
		$eline=int($1);
		$val .= qq|<div style="font-size:11px;">(Line number $eline has been highlighted below.)</div>\n|;
		}
	$val .= qq|<hr size="1">\n|;
	$val .= qq|<b style="color:#336699">Code With Errors:</b>\n|;
	my @lines=split(/[\r\n]+/,$evalstr);
	my $linecnt=0;
	$val .= qq|<table class="w_table" cellspacing="0" cellpadding="0" border="0">\n|;
	foreach my $line (@lines){
		$linecnt++;
		$val .= qq|<tr><td bgcolor="#336699" style="font-size:11px;padding-right:3px;padding-left:3px;color:#FFFFFF;" align="right">$linecnt</td>|;
		if($eline && $eline==$linecnt){
			$val .= qq|<td style="padding-left:3px;border-bottom:1px solid #ff0000;" bgcolor="#ffffcc">|;
			}
		elsif($line=~/^\#/){
			$val .= qq|<td style="padding-left:3px;color:#006600;">|;
			}
		else{$val .= qq|<td style="padding-left:3px;">|;}
		$val .= $line;
		$val .= qq|</td></tr>\n|;
		}
	$val .= qq|</table>\n|;
	$val .= qq|</div>\n\n|;
	return $val;
	}
################
sub formatFixed{
	#usage: my $num=formatFixed($num[,$decimals]);
	#converts $num to a fixed number. $decimals defaults to 2
	my $num=shift;
	my $d=shift;
	if(!length($d)){$d=2;}
	my $template="%." . $d . "f";
	my $fnum=sprintf($template,$num);
	return $fnum;
	}
####################
sub formatComma{
	#usage: my $num=formatNumber(1234565452);
	#info: returns the human readable number (e.g. 1,234,565,452
	#tags: number
	my $num=shift;
	return $num if !isNum($num);
	my $num = reverse $num;
  	$num =~ s/(\d\d\d)(?=\d)(?!\d*\.)/$1,/g;
  	return scalar reverse $num;
	}
################
sub formatPre{
	#usage: my $html=formatPre($text);
	#info: inserts <br /> at the end to each line before the end of line characters
	my $text=shift;
	$text=strip($text);
	my @lines=();
	if($text=~/\r/s){
		#Found windows carriage returns - windows EOLN
		@lines=split(/\r\n/,$text);
		}
	else{
		#linux based EOLN
		@lines=split(/\n/,$text);
		}
	return join("<br />\r\n",@lines);
	}
################
sub formatCsv{
	#internal usage: my $line=formatCsv(@parts);
	#internal formats an array into a Comma Separated Value string
	my @parts=@_;
	for(my $x=0;$x<scalar @parts;$x++){
		$parts[$x]=strip($parts[$x]);
		$parts[$x]=~s/\"/\"\"/sg;
		$parts[$x]=~s/[\r\n]+/\ /sg;
		if($parts[$x]=~/\,/s){$parts[$x]=qq|"$parts[$x]"|;}
		}
	return join(',',@parts);
	}
###############
sub getDirSize{
	#usage: my $bytes=getDirSize($dir);
	#info: returns the dir size recursively
	#tags: file
	my $dir=shift;
	return 0 if !-d $dir;
	my $size=0;
	my @files=listFiles($dir);
	foreach my $file (@files){
		my $afile="$dir/$file";
		if($^O =~ /^MSWIN32$/is){$afile="$dir\\$file";}
		if(-d $afile){$size += getDirSize($afile);}
		else{$size += -s $afile;}
    	}
    return $size;
	}
################
sub getFileContents{
	#usage: my $contents=getFileContents($file);
	#info: returns the contents of $file
	#tags: file
	my $file=shift || return;
	#print "$file\n$afile\n";exit;
	open(FH,$file) || return;
	my @lines=<FH>;
	close(FH);
	if(wantarray){return @lines;}
	return join('',@lines);
	}
################
sub getFileExtension{
	#usage: my $ext=getFileExtension($filename);
	#info: returns the extension of $filename
	#tags: file, parse
	my $file=shift || return '';
	#File extension
	@tmp=split(/\.+/,$file);
	my $ext=pop(@tmp);
	return $ext;
	}
####################
sub getFileName{
	#usage: my $name=getFileName($afile[,$stripext]);
	#info: returns just the filename of $afile where $afile is a the full path to the file: c:\temp\simple\file.txt
	#tags: file, parse
	my $file=shift;
	my $stripext=shift;
	my @tmp=split(/[\\\/]+/,$file);
	my $name=pop(@tmp);
	if($stripext){
		@tmp=split(/\./,$name);
		pop(@tmp);
		$name=join('.',@tmp);
    	}
	return $name;
	}
####################
sub getFilePath{
	#usage: my $path=getFilePath($afile);
	#info: returns just the path of $afile where $afile is a the full path to the file: c:\temp\simple\file.txt
	#tags: file, parse
	my $file=shift;
	$file=fixPathSlashes($file);
	return $file if -d $file;
	my @tmp=split(/[\\\/]+/,$file);
	my $name=pop(@tmp);
	my $path=join('/',@tmp);
	if($^O =~ /^MSWIN32$/is){$path=join("\\",@tmp);}
	return $path;
	}
################
sub getInput{
	#usage: my %input=(); &getInput(\%input);
	#info: returns command line arguments passed in as a hash
	#tags: system
	my $hash=shift || return "No Hash Reference";
	my %params=@_;
	#print STDOUT "getArgs Params:\n" . &hashValues(\%params) . "\n";
	%{$hash}=();
	my $cnt=0;
	my @parts=();
	my $cmdcnt=0;
	my $instr;
	my %keys=();
	binmode(STDIN);   # we need these for DOS-based systems
	binmode(STDOUT);  # and they shouldn't hurt anything else
	binmode(STDERR);
	$type = $params{type} || $ENV{'CONTENT_TYPE'};
	$len  = $params{length} || $ENV{'CONTENT_LENGTH'};
	$meth = $params{method} || $ENV{'REQUEST_METHOD'};
	if(length($params{pairs})){$instr = $params{pairs};}
	elsif($meth=~/^(GET|HEAD)$/is){$instr = $ENV{'QUERY_STRING'};}
	elsif(length($type) && $type =~ m/^multipart\/form-data/is) {
		#Process Multipart Form Data -Not Yet Implimented

		}
	elsif($meth=~/^POST$/is){
		#Read the socket response and store as $body
		my $code;
		my $bytes=int(8*1024);
		$instr='';
		#read until $n is less that $readsize
		#my $gcnt='';
		#my @nlog=();
		while(1){
			my $pbody=''x$bytes;
			my $n=read(STDIN, $pbody,$bytes);
			#push(@nlog,$n);
			$n=int($n+0);
			last if $n==0;
			last if !defined $n;
			$instr .= $pbody;
			last if $n < $bytes;	#$n returns the size to bytes actually read.
			sleep(1);	#Required, else it does not return all the data...
			next;
			}
		#abort("HERE\n<hr> [@nlog]\n<hr>$instr\n<hr>");
		$instr=strip($instr);
		#$got = read(STDIN, $instr, $len);
		}
	#Dont convert yet or values with ; and  & will be trunkated
	#$instr =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;
	if($instr=~m/^\<\?xml\ /is || $instr=~m/^\<xml\ /is){
		$instr=&Parse_XML($instr);
		}
	#print STDOUT "Inside [$instr]\n";
	@parts = split(/[&;]/,$instr);
	my $cmdline=0;
    if($params{argv}){
	    push(@parts, @{$params{argv}});
	    $cmdline=1;
    	}
    my $partcnt=@parts;
	foreach $i (0 .. $partcnt) {
		# Convert plus to space
		$parts[$i] =~ s/\+/ /g;
		# Split into key and value.
		my ($key, $val) = split(/\=/,$parts[$i],2); # splits on the first =.
		#remove any / or - at the beginning of command line args
		if($cmdline){$key=~s/^[\-\/]+//s;}

		# Convert %XX from hex numbers to alphanumeric
		$key =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;
		$val =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;
		#print STDOUT "getArgs($key, $val)\n";
		# Associate key and value
		$key=lc(strip($key));
		next if length($key)==0;
		$val=strip($val);
		#print "$cmdcnt && length($val)==0 && length($params{args})\n";
		#if($cmdcnt && length($val)==0 && length($params{args})==0){$val=1;}
		if($params{argv} && length($val)==0){$val=1;}
		next if length($val)==0;
		$hash->{$key}  .= ':' if (defined($hash->{$key}) and $val ne '');
		$hash->{$key}  .= $val;
		#print STDOUT "($key, $val)\n";
		$keys{$key}=1;
		$cnt++;
		}
	my @tmp=sort(keys(%keys));
	if($params{trim}){return $cnt;}
	$hash->{_keys}=join(":",@tmp);
	$hash->{_count}=$cnt;
	return $cnt;
	}
################
sub getCookie{
	#usage: $val=getCookie($key);
	#info:  returns the value of $key if it is a cookie.
	#info: Returns no value if no $key is not found
	#tags: cookie
	my $key=shift || return;
	my $found='';
# 	print "Content-Type: text/html; charset=iso-8859-1\n\n";
# 	print "Key: $key<br>\n";
# 	print "FOUND COOKIE: $ENV{HTTP_COOKIE}<br>\n";
	if($ENV{HTTP_COOKIE}){
	    my @Cookies = split(/\; /,$ENV{HTTP_COOKIE});
	    foreach $cookie (@Cookies){
			my ($ckey,$cval) = split(/\=/,$cookie);
			if($ckey=~/^\Q$key\E$/is){
# 				print "Match<br>\n";
				$found=$cval;
				}
			}
		}
	return $found;
	}
############
sub getDate {
    #Usage: &Get_Date("NM-ND-YY MH:MM PM"[,$timeInSecs]);
    #Info: N=numeric, M=month/military, D=day, A=abbreviated, F=full, MM=minutes, SS=seconds. NM=numeric Month, MH=military hour, RH=regular hour
    #Info: Codes:NM=numeric month (12), AM=abbreviated month (Jan), FM=full month (January)
    #Info:   YY=2 digit Year, YYYY=4 digit year
    #Info:   MD=Day of month, WD=day of week, AD=abbreviated day (Tues), ND=numeric Day (21), FD-full day (Tuesday)
    #Info:   MH=military hour, RH=hour, MM=minute,SS=second,PM=ampm
    #tags: system, datetime
    my $datestring=shift;
    my $tme=shift;
    @full_months=("January","February","March","April","May","June","July","August","September","October","November","December");
    @abb_months=("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
    @full_days=("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
    @abb_days=("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
    $sec=0;$min=0;$hour=0;$mday=0;$mon=0;$year=0;$wday=0;$yday=0;$isdst=0;
    my $t=time();
    #print "tmeA:$tme\n";
    if($tme=~/^\+/is){
            $tme=$t + int($tme);
            }
    elsif($tme=~/^\-/is){
            $tme=$t - int($tme);
            }
    #print "tmeB:$tme\n";
    if ($tme >0){($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($tme);}
    else{($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);}
    #Set Month
    if($datestring=~/NM\+([1-9]+)/s){
	    my $num=$1;
	    $datestring=~s/NM\+$num/NM/g;
	    $mon += $num;
	    }
    $datestring=~s/MD/$mday/g;
    $datestring=~s/WD/$wday/g;
    $datestring=~s/AM/$abb_months[$mon]/g;
    $datestring=~s/FM/$full_months[$mon]/g;
    $mon = eval($mon+1);
    if($mon<10){$mon="0$mon";}
    $datestring=~s/NM/$mon/g;
    #Set Year
    if($datestring=~/YY\+([1-9]+)/s){
	    my $num=$1;
	    $datestring=~s/YY\+$num/YY/g;
	    $year += $num;
	    }
    $realyear = $year + 1900;
    $shortyear=substr($realyear,2,2);
    $datestring=~s/YYYY/$realyear/g;
    $datestring=~s/YY/$shortyear/g;
    #Set Day
    if($datestring=~/ND\+([1-9]+)/s){
	    my $num=$1;
	    $datestring=~s/ND\+$num/ND/g;
	    $mday += $num;
	    }
    $mday = eval($mday+0);
    if($mday < 10){$mday="0$mday";}
    $datestring=~s/ND/$mday/g;
    $datestring=~s/AD/$abb_days[$wday]/g;
    $datestring=~s/FD/$full_days[$wday]/g;
    #Set Hour, minute, second ampm
    if($datestring=~/(MH|RH)\+([1-9]+)/s){
	    my $str=$1;
	    my $num=$2;
	    $datestring=~s/$str\+$num/$str/g;
	    $hour += $num;
	    }
    $hour=eval($hour+0);
    $mh=$hour;
    #Set ampm
    if($hour>=12){$ampm="pm";}
    else{$ampm="am";}
    $datestring=~s/PM/$ampm/g;
    #set hour and mh (military hour)
    if($hour > 12){$hour=$hour-12;}
    if($hour < 10){$hour="0$hour";}
    if($mh < 10){$mh="0$mh";}
    $datestring=~s/MH/$mh/g;
    $datestring=~s/RH/$hour/g;
    #set min and sec
    if($datestring=~/MM\+([1-9]+)/s){$min += $1;}
    $min=eval($min+0);
    $sec=eval($sec+0);
    if($min < 10){$min="0$min";}
    if($sec < 10){$sec="0$sec";}
    $datestring=~s/MM/$min/g;
    $datestring=~s/SS/$sec/g;
    #Return
    return $datestring;
    }
##################
sub getGMTOffset{
	my $str=shift;
	#usage: my $offset=getGMTOffset([1]);
	#info: returns the offset from Greenwich mean time 
	#info: returns a 4 digit string. e.g. -0600 if 1 is passed in
	my @loc = localtime(time());
	my @gm = gmtime(time());
	my $offset=$loc[2]-$gm[2];
	return $offset if !$str;
	if($offset < 0){
		$offset='-0'.abs($offset+0) . '00';
		}
	else{
		$offset='+0'.abs($offset+0) . '00';
		}
	}
##################
sub getInternetTime{
	#usage: my $itime=getInternetTime();
	#info: returns the current time in internet time format;
	#tags: datetime
	#Get GMT Time
	my ($sec,$min,$hour) = gmtime(time);
	#Calcuate Swatch Internet Time
	my $itime=sprintf "%06.2f", ((3600*(++$hour % 24)) + (60*$min) + $sec)/86.4;
	return $itime;
	}
##################
sub getGuid{
	#Check for existing guid
	#print STDOUT "getGuid\n";
	my $guid=getCookie("GUID");
	my $forcenew=shift;
	if(!$forcenew && length($guid)){$ENV{GUID}=$guid;}
	else{
		#Create a GUID
		#Set the GUID as a cookie
		my @envs=('REMOTE_ADDR','REMOTE_PORT','HTTP_HOST','UNIQUE_ID','HTTP_USER_AGENT');
		$gstr='';
		foreach my $key (@envs){$gstr.=$ENV{$key};}
		my $t1=encodeCRC($gstr);
		my $t2=int(rand(time()/1000));
		my $t3=time();
		#$ENV{WaSQL_GSTR}="GSTR:$t1+$t2+$t3";
		$guid=int($t1+$t2+$t3);
		#Set Cookie to expire in 10 years
		&buildCookie(
			name=>"GUID",
			value=>$guid,
			expire=>3650,
			path=>"/"
			);
		$ENV{GUID}=$guid;
		}
	return $guid;
	}
##################
sub getProcessCount{
	my $process=shift || return 0;
	my $debug=shift || 0;
	if($^O =~ /^MSWIN32$/is){
		# arg /C return the count only
    	$cmd="tasklist | find /I /C \"$process\"";
    	if($debug==1){return $cmd;}
    	my @lines=cmdResults($cmd);
    	return $lines[0];
	}
	$cmd="ps aux|grep $process";
	if($debug==1){return $cmd;}
	my @lines=cmdResults($cmd);
	$cnt=0;
	foreach my $line (@lines){
		next if $line=~/grep/i;
		if($line=~/\Q$process\E/i){$cnt++;}
	}
	return $cnt;
}
##################
sub getUserInfo{
	my $info='';
	$info .= qq|<table cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse">\n|;
	$info .= qq|<tr><th colspan="2">User Information</th></tr>\n|;
	$info .= qq|<tr><th>Property</th><th>Value</th></tr>\n|;
	if($USER{_id}){
		foreach my $key (sort(keys(%USER))){
			my $val=strip($USER{$key});
			if(length($val)){$info .= qq|<tr><td style="font-size:9pt;font-family:arial">$key</td><td style="font-size:9pt;font-family:arial">$val</td></tr>\n|;}
        	}
    	}
    #Get User info from ENV
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">GUID</td><td style="font-size:9pt;font-family:arial">$ENV{GUID}</td></tr>\n|;
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">HTTP_REFERER</td><td style="font-size:9pt;font-family:arial">$ENV{HTTP_REFERER}</td></tr>\n| if length($ENV{HTTP_REFERER});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">REMOTE_BROWSER</td><td style="font-size:9pt;font-family:arial">$ENV{REMOTE_BROWSER}</td></tr>\n| if length($ENV{REMOTE_BROWSER});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">REMOTE_ADDR</td><td style="font-size:9pt;font-family:arial">$ENV{REMOTE_ADDR}</td></tr>\n| if length($ENV{REMOTE_ADDR});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">REMOTE_OS</td><td style="font-size:9pt;font-family:arial">$ENV{REMOTE_OS}</td></tr>\n| if length($ENV{REMOTE_OS});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">HTTP_X_FORWARDED_FOR</td><td style="font-size:9pt;font-family:arial">$ENV{HTTP_X_FORWARDED_FOR}</td></tr>\n| if length($ENV{HTTP_X_FORWARDED_FOR});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">HTTP_HOST</td><td style="font-size:9pt;font-family:arial">$ENV{HTTP_HOST}</td></tr>\n| if length($ENV{HTTP_HOST});
    $info .= qq|<tr><td style="font-size:9pt;font-family:arial">HTTP_USER_AGENT</td><td style="font-size:9pt;font-family:arial">$ENV{HTTP_USER_AGENT}</td></tr>\n| if length($ENV{HTTP_USER_AGENT});
    $info .= qq|</table>\n|;
    return $info;
	}
##################
sub uName{
	#usage: my $os=uName();
	#info: returns the os that the script is running on.
	#tags: system
	if($^O =~ /^MSWIN32$/is){return "Windows";}
	else{
		my $str=`uname`;
		return $str;
		}
	return "unknown";
	}
##################
sub gifInfo{
	#internal usage: ($width,$height,$size,$type) = &gifInfo($giffile);
	#internal info:  Reads the width, height, size, and type from a .gif file
	my $file=shift || return;
	if(! -s $file){return;}
	my ($w,$w2,$width,$h,$h2,$height,$size,$type) = () ;
	open(GIF,$file) || return;
	read(GIF,$type,3);
	seek(GIF,6,0);
	read(GIF,$w,1);
	read(GIF,$w2,1);
	$width=ord($w)+ord($w2)*256 ;
	read(GIF,$h,1);read(GIF,$h2,1);
	$height=ord($h)+ord($h2)*256;
	close(GIF);
	$size= -s $file;
	return ($width,$height,$size,$type);
	}
###############
sub inputValues{
	my $show_all=shift || 0;
	my $rtn = '';
	foreach my $key (sort(keys(%input))){
		next if $key=~/^(input_count|input_fields)$/is;
		next if $key=~/^\_/s && !$show_all;
		my $val=strip($input{$key});
		next if !length($val);
		$rtn .= qq|$key="$val"<br>\n|;
    	}
    return $rtn;
	}
###############
sub in_array{
	my $str=shift;
	my @arr=@_;
	#print "in_array: $str -- @arr\n";
	if(grep /$str/i, @arr){return 1;}
	return 0;
	}
###############
sub isArray {
	#usage: if(isArray($val)){...}
	#info: return 1 if $val is an array
	#tags: validate, array
  my $a = shift;
  return (ref($a) eq 'ARRAY') ? 1 : 0;
  }
###############
sub isCompiled{
	if ($^X =~ /(perl)|(perl\.exe)$/i) {return 0;}
	return 1;
	}
###############
sub isDST{
	#usage: if(isDST()){...}
	#info: returns 1 if the server is on daylight savings time, else returns 0
	#tags: validate, system, datetime
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	if($isdst){return 1;}
	return 0;
	}
###############
sub isEmail{
	#usage: if(isEmail($email)){...}
	#info: returns 1 if string is a valid email address, else returns 0
	#tags: validate, email
	my $str=shift || return 0;
	if($str=~/^.+@.+\..{2,6}$/is){return 1;}
	return 0;
	}
###############
sub isEqual{
	my ($str1,$str2,$i)=@_;
	return 1 if isNum($str1) && isNum($str2) && $str1==$str2;
	return 0 if !length(strip($str1)) || !length(strip($str2));
	if($i){
 		return 1 if $str1=~/^\Q$str2\E$/is;
 		}
 	else{
		return 1 if $str1=~/^\Q$str2\E$/s;
    	}
	return 0;
	}
###############
sub isEven{
	#usage: if(isEven($num)){...}
	#info: returns 1 if number is even, else returns 0
	#tags: validate, math
	my $num=shift || return 1;
	my $tnum=$num/2;
	return 0 if $tnum=~/\.[1-9]+$/s;
	return 1;
	}
############
sub isFactor{
	#usage: if(isFactor(num,fnum)){...}
	#info: returns 1 if num is divisible by fnum, else returns 0
	#tags: validate, math
	my $num = shift || return 0;
	my $factor = shift || return 0;
	my $calc=$num/$factor;
	return 0 if $calc=~/\.[1-9]+$/s;
	return 1;
	}
###############
sub isHash {
	#usage: if(isHash($val)){...}
	#info: return 1 if $val is an hash
	#tags: validate, hashes
	my $a = shift;
	return (ref($a) eq 'HASH') ? 1 : 0;
	}
###############
sub isHtml{
	#usage: if(isHtml($str)){...}
	#info: returns 1 if string contains HTML, else returns 0
	#tags: validate, html
	my $str=shift || return 0;
	if($str=~/\<.+?\>/is){return 1;}
	return 0;
	}
###############
sub isMobileDevice{
	#info: return true if this is a mobile device
	#check for HTTP_X_ or HTTP_UA_ in the Server Environment keys
	foreach my $key (keys(%ENV)){
		if($key=~/^HTTP\_(X|UA)\_/is && $key!~/REWRITE/is){return 1;}
		}
	return 0;
	}
###############
sub isXML{
	#usage: if(isXml($str)){...}
	#info: returns 1 if string contains HTML, else returns 0
	#tags: validate, xml
	my $str=shift || return 0;
	if($str=~/\<.+?\>/is){return 1;}
	return 0;
	}
############
sub isNum{
	#usage: if(isNum($str)){...}
	#info:  return 1 if $str is a number
	#tags: validate, math
	my $str=shift;
	if($str=~/^\-*?[0-9\.]+$/is){
		#make sure there is only one decimal  (125.265.21.23 is not a number)
		my @parts=split(/\./,$str);
		if(scalar @parts < 3){return 1;}
		}
	return 0;
	}
############
sub isText{
	#usage: if(isText($str)){...}
	#info:  return 1 if $str is alphanbetic
	#tags: validate
	my $str=shift;
	if($str=~/^[A-Z\-\ ]+$/is){return 1;}
	if($str=~/^[A-Z\-\ 0-9]+$/is && $str=~/[a-z]/is){return 1;}
	return 0;
	}
############
sub isSub {
	#usage: if(isSub("isNum"[,$packageName])){...}
	#info: returns 1 if $str if a valid loaded subroutine in main;
	#tags: validate
	my $str=shift || return 0;
	my $hash=shift || \%main::;
	return 1 if defined $hash->{$str};
	return 0;
	}
############
sub isUrl{
	#usage: if(isUrl($str)){...}
	#info: returns 1 if $str if a url
	#tags: validate
	my $str=shift || return 0;
	return 1 if defined $str && $str=~/^http/is;
	return 0;
	}
##### Jpg_Info ############
sub jpgInfo{
	#usage: ($width,$height,$size,$type) = &jpgInfo($jpgpath)
	#info:  Reads the width, height, size, and type from a .jpg file
	my $file=shift || return;
	if(! -s $file){return;}
	my ($size,$type, $tag, $marker, $buffer, $lhob, $llob, $blocklen) = ();
	my ($whob, $wlob, $hhob, $hlob, $width, $height) = ();
	my ($M_SOF0,$M_SOF1,$M_SOF2,$M_SOF3,$M_SOI,$M_EOI,$M_SOS,$M_BLK)=();
	$size= -s $file;
	## required jpeg markers
	$M_SOF0 = "\xc0";$M_SOF1 = "\xc1";$M_SOF2 = "\xc2";$M_SOF3 = "\xc3";
	$M_SOI  = "\xd8";$M_EOI  = "\xd9";$M_SOS  = "\xda";$M_BLK  = "\xff";
	open (JPEG, "<".$file) || return;
	binmode (JPEG) || return;
	read (JPEG, $type, 2) || return;
	## check for jpeg file type (start of image) marker
	if (!($type eq $M_BLK.$M_SOI)){
		close(JPEG) || return;
		return;
		}
	for (;;){
		## check for block tag
		read (JPEG, $tag, 1) || return;
		if ($tag ne $M_BLK){
			close(JPEG) || return;
			return;
			}
		## get marker type & block length
		read (JPEG, $marker, 1) || return;
		read (JPEG, $lhob, 1) || return;
		read (JPEG, $llob, 1) || return;
		$blocklen = (ord($lhob) * 256) + ord($llob) - 2;
		## check for any start of field marker
		if ( $marker ge $M_SOF0 && $marker le $M_SOF3 ){
			## ignore data precision
			read (JPEG, $buffer, 1) || return;
			## read the height and width.
			read (JPEG, $hhob, 1) || return;
			read (JPEG, $hlob, 1) || return;
			$height = (ord($hhob) * 256) + ord($hlob);
			read (JPEG, $whob, 1) || return;
			read (JPEG, $wlob, 1) || return;
			$width = (ord($whob) * 256) + ord($wlob);
			# ignore components & rest of file...
			close(JPEG) || return;
			return ($width,$height,$size,$type);
			}
		else{
			if ($marker eq $M_SOS || $marker eq $M_EOI ){
				## past header data; size indeterminable
				close(JPEG) || return;
				return;
				}
			## skip to next marker
			read (JPEG, $buffer, $blocklen);
			close(JPEG);
			return;
			}
		}
	}
###############
sub listDirs{
	#usage: @dirs=listDirs($startdir,$recurse,$nopath);
	#info:  returns the count or an array of dirs found in $startdir
	#tags: system, file
	my $startdir=shift || $ENV{DOCUMENT_ROOT} || $progpath || './';
	my $recurse=shift;
	my $nopath=shift;
	my $FH;
	#print "listDirs($startdir,$recurse)\n";
	opendir($FH,$startdir);
	my @cfiles=grep(/\w/,readdir($FH));
	closedir($FH);
	if(!$recurse && $nopath){
		if(wantarray){return @cfiles;}
		my $cnt=@cfiles;
		return $cnt;
		}
	my %Dirlist=();
	my @dirs=();
	foreach my $cfile (@cfiles){
		my $afile="$startdir/$cfile";
		#skip if not a directory
		next if !-d $afile;
  		$Dirlist{$afile}=1;
		if($recurse==1){
			my @rdirs=listDirs($afile,1);
			push(@dirs,@rdirs);
			}
		}
	my @tmp=sort(keys(%Dirlist));
	push(@dirs,@tmp);
	@dirs=sort(@dirs);
	if(wantarray){return @dirs;}
	my $cnt=@dirs;
	return $cnt;
	}
###############
sub listFiles{
	#usage: @files=listFiles($dir[$ext,$stripext]); or $cnt=listFiles($dir[$exe,$stripext]);
	#info:  returns the count or an array of files found in $dir with extension $ext. If $stripext, strip the extension off the filename
	#tags: system, file
	my $dir=shift || './';
	my $fileext=shift;
	my $stripext=shift;
	my $sort=shift;
	my $DH;
	if(!opendir($DH,$dir)){return "opendir Error: $!";}
	my @cfiles=grep(/\w/,readdir($DH));
	closedir($DH);
	if($sort){
		if($sort=~/^(mdate|date)/is){
			#sort my modified date
			@cfiles = sort { -M "$dir/$a" <=> -M "$dir/$b" } @cfiles
        	}
        elsif($sort=~/^adate/is){
			#sort by accessed date
			@cfiles = sort { -A "$dir/$a" <=> -A "$dir/$b" } @cfiles
        	}
        elsif($sort=~/^cdate/is){
			#sort by create date
			@cfiles = sort { -C "$dir/$a" <=> -C "$dir/$b" }  @cfiles
        	}
        elsif($sort=~/^size/is){
			#sort by size
			@cfiles = sort { -s "$dir/$a" <=> -s "$dir/$b" }  @cfiles
        	}
        #desc ?
        if($sort=~/\ desc$/is){@cfiles=reverse(@cfiles);}
    	}

	my @files=();
	foreach my $cfile (@cfiles){
		$cfile=~/(.+)\.(.+)$/is;
		my $name=$1;my $ext=$2;
		if($fileext){
			my @fexts=split(/\|/,$fileext);
			foreach my $fext (@fexts){
				if($ext =~/^\Q$fext\E$/is){
					if($stripext){push(@files,$name);}
					else{push(@files,$cfile);}
					}
				}
			}
		else{
			if($stripext){push(@files,$name);}
			else{push(@files,$cfile);}
			}
		}
	#print "lfiles:@files<br>\n";
	if(wantarray){return @files;}
	my $cnt=@files;
	return $cnt;
	}
###################
sub loadModule{
	my @mods=@_;
	foreach my $mod (@mods){
		my $str='use ' . $mod . ';';
		eval($str);
		if($@){abort("Error Loading $mod","-\t$@");}
    	}
    return 1;
	}
###################
sub loadScript{
	#internal usage: my $err=loadScript('subs_special.pl');
	#internal info: loads script. returns a blank value if no errors, otherwise returns error message
	my $script=shift || return "Error: No Script File to load";
	my $str='';
	if(!-e $script){return "Error: $script does not exist.";}
	if($script =~/\.pm$/is){$str=qq|use $script|;}
	else{$str=qq|require \'$script\'\;|;}
	eval($str);
	if($@){abort("Error: $@");}
	return '';
	}
###################
sub openFile{
	#depreciated - use getFileContents instead
	return "openFile is depreciated. use getFileContents instead";
    }

###################
sub parseEnv {
	#internal usage: $remote_os=parseEnv();
	#internal info: parses $ENV{HTTP_USER_AGENT} and sets environment variables: REMOTE_BROWSER and REMOTE_OS:
	#internal info: Also parses $ENV{HTTP_HOST} to set $ENV{UNIQUE_HOST}
	#
	#Sample Values from multiple browsers
	# Mozilla/4.04 [en] (X11; U; Linux 2.0.30 i586)
	# Mozilla/3.0 WebTV/1.2 (compatible; MSIE 2.0)
	# Mozilla/2.0 (compatible; MSIE 3.0; AOL 3.0; Windows 3.1)
	# Mozilla/2.0 (compatible; MSIE 3.0B; Win32)
	# Mozilla/4.01 (Macintosh; I; PPC)
	# Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)
	# Mozilla/4.75 [en] (Win98; U)  -- netscape
	# Mozilla/5.0 (Windows; U; WinNT4.0; en-US; rv:0.9.4) Gecko/20011128 Netscape6/6.2.1
	# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
	# Mozilla/5.0 (compatible; AvantGo 3.2;ProxiNet; Danger hiptop 1.0)
	# Opera/6.0 (Windows 2000; U) [en] 
	my $agent=$ENV{HTTP_USER_AGENT};
	if ($agent=~/WebTV/i){$ENV{REMOTE_BROWSER}='WebTV';}
 	elsif ($agent=~/MSp*IE/i){
		$agent=~/.+?\((.+?)\)/;
		my @agentparts=split(/\;/,$1);
		foreach my $agentpart (@agentparts){
			$agentpart=~s/^\ +//;
			$agentpart=~s/\ +$//;
			if($agentpart=~/^(msp*ie)/i){$ENV{REMOTE_BROWSER}=$agentpart;}
			if($agentpart=~/^(win|lin|mac)/i){$ENV{REMOTE_OS}=$agentpart;}
			}
		}
	elsif ($agent=~/netscape/i){
		$agent=~/(netscape.+)/i;
		$ENV{REMOTE_BROWSER}=$1;
		$agent=~/.+?\((.+?)\)/;
		my @nparts=split(/\;/,$1);
		$ENV{REMOTE_OS}=$nparts[2];
		}
	elsif ($agent=~/AvantGo/is){
		$agent=~/AvantGo\/(.+?)\ /is;
		$ENV{REMOTE_BROWSER} .= "AvantGo $1";
		$agent=~/.+?\((.+?)\)/;
		my @nparts=split(/\;/,$1);
		$ENV{REMOTE_OS}=pop(@nparts);
		}
	elsif ($agent=~/Mozilla/i){
		$agent=~/mozilla\/(.+?)\ /i;
		$ENV{REMOTE_BROWSER} .= "Netscape $1";
		$agent=~/.+?\((.+?)\)/;
		my @nparts=split(/\;/,$1);
		$ENV{REMOTE_OS}=$nparts[0];
		}
	elsif ($agent=~/^Opera/i){
		$agent=~/^opera\/(.+?)\ /i;
		$ENV{REMOTE_BROWSER} .= "Opera $1";
		$agent=~/.+?\((.+?)\)/;
		my @nparts=split(/\;/,$1);
		$ENV{REMOTE_OS}=$nparts[0];
		}
	elsif ($agent=~/^IMin1/i){
		$agent=~/^IMin1\/(.+?)\ /i;
		$ENV{REMOTE_BROWSER} .= "IMin1 $1";
		$agent=~/.+?\((.+?)\)/;
		$ENV{REMOTE_OS}=$1;
		}
	else{
		$ENV{REMOTE_OS}='Unknown';
		$ENV{REMOTE_BROWSER}='Unknown';
		}
	#Unique Host
	if(!$ENV{UNIQUE_HOST}){
		$ENV{UNIQUE_HOST}=&getUniqueHost($ENV{HTTP_HOST});
		}
	#Request Path
	if(length($ENV{REQUEST_URI})){
		my $uri=$ENV{REQUEST_URI};
		$uri=~s/\?.*//sg;
		$uri=~s/^(http|https):\/\/$ENV{UNIQUE_HOST}//sg;
		if($ENV{SCRIPT_NAME}){
			$uri=~s/$ENV{SCRIPT_NAME}//sg;
			}
		$uri=~s/^[\\\/]+//sg;
		if(length($uri) && $uri!~/\/admin$/is){
			my @parts=split(/[\\\/]/,$uri);
			my $last=pop(@parts);
			if($last!~/\./s){push(@parts,$last);}
			$ENV{REQUEST_PATH}=join('/',@parts);
			}
    	}
	parseRemoteOS();
	if(wantarray){return ($ENV{REMOTE_OS},$ENV{REMOTE_BROWSER},$ENV{UNIQUE_HOST});}
	return $ENV{REMOTE_OS};
	}
#########################
sub parseRemoteOS{
	my $UserAgent = shift || $ENV{HTTP_USER_AGENT};
	#$ENV{parseRemoteOS}="true";
	#parse out the os section
	#Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.2b) Gecko/20020923 Phoenix/0.1
	my ($name,$cpu,$title,$lang,$ver);
	my $found=0;
	if($UserAgent=~/^(.+?)\((.+?)\)/s){
		my @parts=split(/\;/,$2);
		my $partscnt=@parts;
		#$ENV{parseRemoteOSparts}=$partscnt . "[@parts]";
		foreach my $part (@parts){
			$part=strip($part);
			if($part=~/^(Windows|X11|Macintosh|Linux|OS\/2)$/is){$ENV{REMOTE_OS_NAME}=$part;}
			elsif($part=~/(Windows|Linux|Mac|WinNT|PPC|FreeBSD)/is){$ENV{REMOTE_OS_TITLE}=$part;}
			elsif($part=~/^Win[0-9a-z\.]{2,3}$/is){$ENV{REMOTE_OS_TITLE}=$part;}
			elsif($part=~/^Warp\ [0-9a-z\.]{2,3}$/is){$ENV{REMOTE_OS_TITLE}=$part;}
			elsif($part=~/^([0-9]+?)x([0-9]+?)$/is){$ENV{REMOTE_OS_RESOLUTION}=$part;}
			elsif($part=~/^[a-z]{2,2}\-[a-z]{2,2}$/is){$ENV{REMOTE_OS_LANG}=$part;}
			elsif($part=~/^[a-z]{2,2}$/is){$ENV{REMOTE_OS_LANG}=$part;}
			elsif($part=~/^rv:(.+)/is){$ENV{REMOTE_OS_REVISION}=$1;}
        	}
        }
	}
#########################
sub getUniqueHost{
	#internal usage: my $uhost=getUniqueHost("login.mydomain.com");
	#internal info: return the unique host name (mydomain.com)
	my $inhost=shift || return;
	my $uhost;
	if($inhost=~/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/){$uhost=$inhost;}
	elsif($inhost=~/([A-Z0-9\-]+)\.([A-Z0-9\-]+)\.([A-Z0-9\-]+)/is){$uhost=$2 . '.' . $3;}
	else{$uhost=$inhost;}
	$uhost=lc($uhost);
	return $uhost;
	}
#########################
sub getUserInput{
	my $msg=shift;
	my @vals=@_;
	my $valcnt=@vals;
	#print "getUserInput valcnt:$valcnt\n";
	#check for vals - only allow those as choices if passed in
	if($valcnt > 0){
		my $choice='';
		while(!isNum($choice) || $choice < 0 || $choice > $valcnt){
			print "$msg\n" if length($msg);
			for(my $x=0;$x<$valcnt;$x++){
		    	print "  $x - $vals[$x]\n";
			}
			print "Selection:";
			my $str=<STDIN>;
			$str=strip($str);
			if(isNum($str)){$choice=$str;}
		}
		return $vals[$choice];
	}
	print $msg if length($msg);
	my $str=<STDIN>;
	$str=strip($str);
	return $str;
	}
#########################
sub Parse_XML {
	#Take XML and turn it into a key/value data stream (key=value&key2=value2...)
	my $xmlin=shift;
	my ($xmlparams,$xmlbody);
	my @pairs=("_xml=1");
	if($xmlin=~m/\<\?*xml(.*?)\>(.+)\<\/xml\>/is){
		$xmlparams=$1;
		$xmlbody=$2;
		}
	else{$xmlbody=$xmlin;}
	#<name>Billy</name>
	while($xmlbody=~m/\<(.+?)\>(.+?)\<\/\1\>/sig){
		my $tag=$&;
		my $key=lc(strip($1));
		my $val=strip($2);
		if(length($val)){
			#Convert spaces to +
			$val=encodeURL(strip($val));
			push(@pairs,"$key=$val");
			}
		}
	my $stream=join("\&",@pairs);
	return $stream;
	}
###########
sub printHeader{
	#internal usage: &printHeader();
	#internal info: reads the %HEADER hash and creates a header from it.
	#internal info: possible HEADER hash values are Content-Type, Content-Length, Cache-Control, max-age, and Set-Cookie
	#internal info: Note: Set-Cookie is an array.
	#Set Header
	my $ctype=shift;
	if($HEADER{redirect}){
		print $cgi->redirect(-uri=>$HEADER{redirect},-nph=>1);
		$HEADER{printed}=1;
		return;
    	}
	if($ctype){
            if($ctype=~/^html$/is){
               $ctype = "text/html";
               }
            elsif($ctype=~/^xml$/is){
               $ctype = "text/xml";
               }
            }
	elsif($HEADER{'Content-Type'}){$ctype=$HEADER{'Content-Type'};}
        else{$ctype="text/html";}
	# Set-Cookie
	my @cookies=(@{$ENV{SET_COOKIE}});
	foreach my $cookie (@cookies){
		$cookie=strip($cookie);
		next if length($cookie)==0;
		print "Set-Cookie: " . $cookie . "\n";

		}
	# Cache-Control
	if(length($HEADER{'max-age'})){print "Cache-Control: max-age=" . $HEADER{'max-age'} . "\n";}
	elsif(length($HEADER{'Cache-Control'})){
		#Cache-Control: no-cache
		#Cache-Control: no-store
		#Cache-Control: private
		#Cache-Control: must-revalidate
		print "Cache-Control: " . $HEADER{'Cache-Control'} . "\n";
		}
	#ETag
	if(length($HEADER{'ETag'})){print qq|ETag: "| . $HEADER{'ETag'} . qq|"\n|;}
	# Content-Length
	if(length($HEADER{'Content-Length'})){print "Content-Length: " . $HEADER{'Content-Length'} . "\n";}
	# Expires
	if(length($HEADER{'Expires'})){print "Expires: " . $HEADER{'Expires'} . "\n";}
	# Content-Type
	if(length($HEADER{'Content-Type'})){print "Content-Type: " . $HEADER{'Content-Type'} . "\n";}
	else{print "Content-Type: text/html; charset=iso-8859-1\n";}
	# Content-Disposition
	if(length($HEADER{'Content-Disposition'})){
		#Content-Type: application/msword; name="myfile.doc"
		#Content-Disposition: attachment; filename="$filename"|;
		print "Content-Disposition: " . $HEADER{'Content-Disposition'} . "\n";
		}
	print "\n";
	$HEADER{printed}=1;
	return;
	}
##################
sub printLog{
	my @msgs=@_;
	my $utime=time();
	my $logFile="$progpath/$progname\.log";
	#Check to see if $logFile is larger than 5MB, If so rename it first
	my $maxMB=5;
	my $max=int(1024*1024*$maxMB);
	if(-e $logFile && -s $logFile > $max){
		my $oldLog=$logFile . "\.bak";
		unlink($oldLog) if -e $oldLog;
		rename($logFile,$oldLog);
        }
    unshift(@msgs,$utime);
	if(!open(FH,">>$logFile")){return $^E;}
	binmode(FH);
	#Log Format  $utime,$stime,...
	print FH formatCsv(@msgs) . "\r\n";
	close(FH);
	return 1;
	}
############
sub pushFile {
	#usage: pushFile($filename[,1,$newname]);
	#info: pushes a $filename to the browser. If 1 is passed, it appends date to filename (test_2004.04.02.txt)
	#tags: file
	my $sfile=shift || return "No File Passed to push";
	my $append=shift;
	my $asname=shift;
	if(!-s $sfile){return "$sfile is empty";}
	my $buffer='';
	my ($sf,$sfc);
	my ($sfn,$sfe)=$sfile=~/(.+)\.(.+)/;
	if(open(SF, $sfile)){
		binmode (SF);
		read(SF, $sf, -s $sfile);
		close(SF);
		binmode(STDOUT);
		my $sflen=length($sf);
		#get name of file if file has a path.
		my @tmp=split(/\//,$sfile);
		$fname=pop(@tmp);
		my $spath=join('/',@tmp);
		$sfile=~s/[ \\\/]+/\_/g;
		my $newname=$asname || $fname;
		if($append){
			my $tname=$asname || $fname;
			$tname=~s/\.$sfe$//s;
			my $ndate=getDate("YYYYNMND");
			$newname=qq|$tname\_$ndate\.$sfe|;
			$newname=~s/\[[0-9]+\]//sg;
			}
		if($sfe=~/^(gif|png)$/is){$sfc="image/$1";}
		elsif($sfe=~/^(jpeg|jpg)$/is){$sfc="image/jpeg";}
		elsif($sfe=~/^(txt|html|htm|css|js)$/is){$sfc="text/html";}
		elsif($sfe=~/^doc$/is || -T $sfile){$sfc=qq|application/msword\; name="$fname"\nContent-Disposition: attachment; filename="$newname"|;}
		elsif($sfe=~/^wpd$/is || -T $sfile){$sfc=qq|application/wordperfect\; name="$fname"\nContent-Disposition: attachment; filename="$newname"|;}
		elsif($sfe=~/^pdf$/is || -T $sfile){
			if(length($HEADER{'Content-Disposition'})){
				$sfc=qq|application/pdf\; name="$fname"\nContent-Disposition: $HEADER{'Content-Disposition'}|;
				}
			else{
				$sfc=qq|application/pdf\; name="$fname"\nContent-Disposition: attachment; filename="$newname"|;
				}
			}
		else{$sfc=qq|application/$sfe\; name="$fname"\nContent-Disposition: attachment; filename="$newname"|;}
		#print qq|Content-type: text/html\n\n|;
		print qq|Content-type: $sfc\n\n|;
		print $sf;
		}
	else{
		return qq|Could not open $sfile: $!\n|;
		}
	return 1;
	}
################
sub setFileContents{
	#usage: my $contents=getFileContents($file);
	#info: returns the contents of $file
	#tags: file
	my $file=shift || return "No file";
	my $data=shift || return "No data";
	open(FH,">$file") || return $^E;
	binmode FH;
	print FH $data;
	close(FH);
	return 1;
	}
################
sub shuffleArray {
	#fisher_yates_shuffle
	#internal usage: @array=shuffleArray(@array);
	#internal info: shuffles array items using the fisher_yates shuffle
	my $array = shift; 
	my $i; 
	for ($i = @$array; --$i; ) { 
		my $j = int rand ($i+1);
		@$array[$i,$j] = @$array[$j,$i]; 
		}
	}
###############
sub sleepMS{
	#internal usage: sleepMS(250); or sleepMS(.25);
	#internal info: sleeps for 250 milliseconds. If you pass a fraction, then I assume you mean a fraction of a second.
	my $ms=shift || return 0;
	my $fraction=0;
	if($ms > 0){
		$ms=int($ms);
		#convert milliseconds to a fraction
		$fraction=$ms/1000;
		}
	else{$fraction=$ms;}
	#use the select statement to sleep for a fraction of a second.
	select(undef, undef, undef, $fraction);
	return 1;
	}
#####################
sub sortHashByKey{
	#usage:my @keys=sortHashByKey(\%hash,'age');
	#info: returns and array of keys sorted as specified
	#tags: sort, hashes
	my $hash=shift;
	my $sortkey=shift;
	#$Build{$project}{state}='queued';
	my @mainkeys=keys(%{$hash});
	my $firstkey='';
	foreach my $mainkey (@mainkeys){
		if(length($mainkey)){
			$firstkey=$mainkey;
			last;
			}
    	}
	if(!length($sortkey)){
		#default to main key as the sort value
		my @tmp=sort(keys(%{$hash}));
		@tmp=sortTextArray(@tmp);
		$input{sortHashByKey}="$sortkey is not defined";
		return @tmp;
		}
	my @sortkeys=();
	my $log =qq|sortHashByKey ($sortkey)<br>\n|;
	my %exist=();
	foreach my $mainkey (@mainkeys){
		$log .= qq|mainkey: $mainkey<br>\n|;
		if(!scalar @sortkeys){push(@sortkeys,$mainkey);}
		else{
			my $mval=$hash->{$mainkey}{$sortkey};
			my @new=();
			my $found=0;
			my $cmpText=0;
			foreach my $ckey (@sortkeys){
				if(isText($hash->{$ckey}{$sortkey})){$cmpText++;}
				}
			foreach my $ckey (@sortkeys){
				#$log .= qq|ckey:[$ckey][$exist{$ckey}]<br>\n|;
    			next if !length($ckey);
				$exist{$ckey}=1;
				my $cval=$hash->{$ckey}{$sortkey};
				my $compare=-2;
				if($cmpText && isText($mval)){
					$compare=$mval cmp $cval;
					}
				else{$compare=$mval <=> $cval;}
				$log .= qq|\tcompare: $mainkey\[$mval] $compare $ckey\[$cval]<br>\n|;
                if($compare==-1 && !$exist{$mainkey}){
					#$cval is less than $sval
					$log .= qq|\tAdding [$mainkey] to end of array<br>\n|;
					$exist{$mainkey}=1;
					push(@new,$mainkey);
					$found++;
                	}
				push(@new,$ckey);
            	}
            if(!$found && !$exist{$mainkey}){push(@new,$mainkey);}
            @sortkeys=@new;
            %exist=();
            foreach my $key (@sortkeys){$exist{$key}=1;}
        	}
        $log .= qq|\tsortkeys:[@sortkeys]<br>\n|;
    	}
    #$input{sortHashByKey}=$log;
    return @sortkeys;
	}
#####################
sub sortTextArray{
	#usage: @array=sortTextArray(@array);
	#info:sorts a text array properly
	#tags: sort, array
	my @in=@_;
	my @new = sort { uc($a) <=> uc($b) || uc($a) cmp uc($b) } @in;
	return @new;
	}
###############
#http://www.perlmonks.org/?node_id=311534
sub sort_grt {
	my @array=@_;
    	my @grt_sorted =
	        map {substr $_ => $max_l}
	        sort
	        map {uc () . ("\0" x ($max_l - length)) . $_} @array;
	return @grt_sorted;
	}
###################
sub splitEmail{
	#usage: my ($email,$name)=splitEmail($email);
	#info: returns email,name
	#tags: parse, email
	my $string=shift;
	my ($name,$email);
	if($string=~/(.*?)\<(.+@.+\..{2,6})\>/is){
		$email=strip($2);
		$name=strip($1);
		}
	elsif($string=~/^.+@.+\..{2,6}$/is){
		$email=strip($string);
		}
	return ($email,$name);
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
################
sub sumString{
	#internal usage: $sum=sumString($string);
	#internal info: kind of like a crc but for string less than 1000 characters
	my $str= shift || return;
	#split string into an array
	my @chars=split(//,$str);
	my $sum=0;
	my $charcnt=@chars;
	my $modnum=int($charcnt/3);
	if($modnum<2){$modnum=2;}
	for($x=0;$x<$charcnt;$x++){
		my $char=$chars[$x];
		#uppercase every other character
		if(($x/2) !~/\./){$char=uc($char);}
		else{$char=lc($char);}
		#get the characters decimal equivilent
		my $num=ord($char);
		if(($x/$modnum) !~/\./){
			#print "Times: num:$num, char:$char, sum:$sum\n";
			$sum = $sum * $num;
			}
		else{
			#print "Add: num:$num, char:$char, sum:$sum\n";
			$sum += $num;
			}
		}
	return $sum;
	}
###############
sub capitalize{
	#usage: my $str=capatalize($str);
	#info: capatalizes all words in $str
	#tags: string
	my $str=shift;
	my @words=split(/[\s\_]+/,$str);
	my $wcnt=@words;
	for(my $x=0;$x<$wcnt;$x++){
		$words[$x]=ucfirst($words[$x]);
	     }
	my $rtn= join(" ",@words);
	return $rtn;
	}
####################
sub verboseSize{
	#usage: my $size=verboseSize(($bytes|$file|$dir)[,$format]);
	#info: returns the human readable size of the bytes or file or directory given.
	#tags: bytes
	my $bytes=shift;
	my $format=shift;
	if(-f $bytes){$bytes=-s $bytes;}
	elsif(-d $bytes){$bytes=getDirSize($bytes);}
	$bytes=int($bytes);
    my @sizes=('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    my $sizecnt = @sizes;
    my $i=0;
    for ($i=0; $bytes > 1024 && $i < $sizecnt && defined $sizes[$i+1]; $i++){$bytes /= 1024;}
    if(!defined $format){$format=$i<3?'%.1f %s':'%.2f %s';}
    return sprintf($format, $bytes,$sizes[$i]);
	}
####################
sub verboseSpeed{
	#usage: my $size=verboseSpeed($Mhz);
	#info: return the human readable size of Mhz given
	#tags: Mhz
	my $Mhz=shift;
	if(($Mhz/1000)>1){return sprintf("%.2f",($Mhz/1000)) . " GHz";}
	else{return $Mhz . " MHz";}
	}
###############
sub verboseTime {
	#usage: $vtime=verboseTime($number_of_seconds);
	#info: given seconds, returns a string of the number of seconds in verbose time (4 days 9 hours 23 minutes 6 seconds)
	#tags: datetime
	my $num=shift;
	#print "uptime=>$num\n";
	my ($years,$days,$hrs,$min,$sec)=(0,0,0,0,0);
	#('day',86400,'hour',3600,'minute',60);
	if($num>31536000){
		$years=int($num/31536000);
		$num=($num-($years*31536000));
		}
	if($num>86400){
		$days=int($num/86400);
		$num=($num-($days*86400));
		}
	if($num>3600){
		$hrs=int($num/3600);
		$num=($num-($hrs*3600));
		}
	if($num>60){
		$min=int($num/60);
		$num=($num-($min*60));
		}
	$sec=int($num);
	my $string='';
	$string .= $years . ' yrs ' if $years;
	$string .= $days . ' days ' if $days;
	if(!$days){
		$string .= $hrs . ' hrs ' if $hrs;
		$string .= $min . ' mins ' if $min;
		$string .= $sec . ' secs ' if $sec;
		}
	if(wantarray){return ($string,$years,$days,$hrs,$min,$sec);}
	return $string;
	}
###############
sub xmlEncode{
	my $str=shift;
	$str=fixMicrosoft($str);
	$str=~s/\r\n/\n/sg;
	$str=~s/[\x00-\x08\x0b\x0c\x0e-\x1f]//sg;
	$str=encodeSpecialChars($str);
	return $str;
	}
###############
sub xmlEncodeCDATA($val='' ) {
	#info: returns xml encoded string and handles CDATA
	my $val=shift;
	if(isXML($val)){return "<![CDATA[\n" . xmlEncode($val) . "\n]]>";}
    return xmlEncode($val);
	}
###############
sub xmlHeader{
	my $params=@_;
	my $version=length($params{version})?$params{version}:"1.0";
	my $encoding=length($params{encoding})?$params{encoding}:"ISO-8859-1";
	return '<?xml version="'.$version.'" encoding="'.$encoding.'"?>'."\n";
	}
########################################
return 1;
