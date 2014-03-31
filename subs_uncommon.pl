####################
sub calculateDistance{
	#usage: my $miles=calculateDistance(40.6245,-111.8245,34.0361,-118.4224);
	#info: calculates approximate distance in miles between two latitude,longitude sets.
	#info: 	Approximate distance in miles = sqrt(x * x + y * y)
	#info: 	where x = 69.1 * (lat2 - lat1)
	#info: 	and   y = 69.1 * (lon2 - lon1) * cos(lat1/57.3)
	my ($lat1,$lon1,$lat2,$lon2)=@_;
	my $x=abs(69.1*($lat2-$lat1));
	my $y=abs(69.1*($lon2-$lon1)*cos($lat1/57.3));
	my $miles=sprintf("%.2f",sqrt($x*$x + $y*$y));
	return $miles;
	}
##############
sub cssButton{
	my $str=shift || return;
	my $rtn=qq|<span style="margin:0px;padding:0px;border:1px solid #336699;color:#000000;font-size:12px;font-family:arial;width:15px;background:#E0DFE3;">&nbsp;$str</span>|;
	return $rtn;
	}
###############
sub churn{
	my $msg=shift;
	if($msg){
		my $len=length($msg);
		print "\b"x$len . $msg;
		return 1;
	     }
	my @busy=('|','/','-');
	if(!defined $Churn){$Churn=0;}
	my $bcnt=@busy;
	$Churn=$Churn>$bcnt?0:$Churn+1;
	my $ch=$busy[$Churn];
	print "\b$ch";
	select(undef,undef,undef,.0001);
	return 1;
	}
################
sub hash2Csv{
	#usage: print hash2Html(\%hash[,title=>$title,bgcolor=>$bgcolor]);
	my $hash=shift || return "no hash passed to hash2Html";
	my %params=@_;
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
	if($hash->{fields}){@fields = @{$hash->{fields}};}
	else{
		foreach my $key (keys(%{$hash->{0}})){
			push(@fields,$key);
			}
		}
	my $fieldcnt=@fields;
	if($fieldcnt==0){return "Unable to determine fields in hash2Html";}

	#build header row
     $rtnstr .= join(',',@fields) . "\r\n";
	for(my $x=0;$x<$cnt;$x++){
		my @parts=();
		foreach my $field (@fields){
			#next if ! $params{$field};
			my $val=$hash->{$x}{$field};
			$val=~s/\"/\"\"/sg;
			if($val=~/\,/s){push(@parts,"\"$val\"");}
			else{push(@parts,$val);}
			}
		$rtnstr .= join(',',@parts) . "\r\n";
		}
	return $rtnstr;
	}
################
sub hash2Dos{
	#usage: print hash2Dos(\%hash[,title=>$title,bgcolor=>$bgcolor]);
	my $hash=shift || return "no hash passed to hash2Dos";
	my %params=@_;
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
	if($params{'fields'}){@fields = @{$params{'fields'}};}
	else{
		foreach my $key (keys(%{$hash->{0}})){
			push(@fields,$key);
			}
		}
	my $fieldcnt=@fields;
	if($fieldcnt==0){return "Unable to determine fields in hash2Html";}
	#build header row
	$rtnstr .= "-"x78 . "\r\n";
     $rtnstr .= join(',',@fields) . "\r\n";
     $rtnstr .= "-"x78 . "\r\n";
     #build data rows
	for(my $x=0;$x<$cnt;$x++){
		my @parts=();
		foreach my $field (@fields){
			my $val=$hash->{$x}{$field};
			$val=~s/\"/\"\"/sg;
			if($val=~/\,/s){push(@parts,"\"$val\"");}
			else{push(@parts,$val);}
			}
		$rtnstr .= join(',',@parts) . "\r\n";
		}
	return $rtnstr;
	}
##############
sub hash2Treeview{
	#usage: my $html=hash2Treeview(\%hash);
	#info: builds a tree view menu from a hash and passes the key names as input along with any params when a link is clicked
	my $hash=shift || return "No hash in hash2Treeview";
	if(!isHash($hash)){return "Not a hash";}
	my %params=@_;
	my @pairs=();
	foreach my $key (keys(%params)){
		my $val=encodeURL($params{$key});
		push(@pairs,"$key=$val");
	     }
	my $paramstr=join('&',@pairs);
	my $html = qq|<ul class="mktree" id="w_nav" nowrap>\n|;
	my @items=keys(%{$hash});
	#abort("here","[@items]",$hash);
	foreach my $item (@items){
		my $val=$hash->{$item} || 1;
		#abort($item,$val);
		$html .=  hash2TreeviewItem($item,$val,$paramstr);
	     }
	$html .= qq|</ul>\n|;
	return $html;
	}
sub hash2TreeviewItem{
	my $item=shift || return '';
	my $val=shift;
	my $linkstr=shift;
	my $treeview=shift;
	$treeview .= ":$item";
	$treeview=~s/^\:+//s;
	my $html = qq|<li><a class="menu" href="$ENV{SCRIPT_NAME}?$linkstr\&treeview=$treeview">$item</a>|;
	if(isHash($val)){
		foreach my $key (sort(keys(%{$val}))){
			my $xval=$val->{$key};
			if(1==1 || isHash($xval) || isArray($xval)){
				$html .= qq|\n<ul>\n|;
				$html .= hash2TreeviewItem($key,$xval,$linkstr,$treeview);
				$html .= qq|</ul>\n|;
				}
	          }
		}
	elsif(isArray($val)){
          $html .= qq|\n<ul>\n|;
          foreach my $xkey (sort(@{$val})){
               $html .= hash2TreeviewItem($xkey,1,$linkstr,$treeview);
	          }
	     $html .= qq|</ul>\n|;
		}
     $html .= qq|</li>\n|;
     return $html;
	}
################
sub hash2XML{
	#usage: print hash2XML(\%hash);
	my $hash=shift || return "no hash passed to hash2XML";
	my $tabcnt=shift || 0;
	my $rtn='';
	if(isHash($hash)){
		foreach my $key (sort(keys(%{$hash}))){
			$rtn .= "\t"x$tabcnt . qq|<$key>\n|;
			my $ktabcnt=$tabcnt+1;
			my $val=$hash->{$key};
			if(isHash($val) || isArray($val)){
				$tabcnt++;
				$rtn .= hash2XML($val,$tabcnt);
				}
			else{
				if($val=~/[\<\>]/){$rtn .= "\t"x$tabcnt . qq|<![CDATA[$val]]>\n|;}
				else{$rtn .= "\t"x$tabcnt . "$val\n";;}
				}
			$rtn .= qq|</$key>\n|;
		     }
   		}
   	elsif(isArray($hash)){
          foreach my $key (sort(@{$hash})){
			$rtn .= "\t"x$tabcnt . qq|<$key>\n|;
			my $ktabcnt=$tabcnt+1;
			my $val=$hash->{$key};
			if(isHash($val) || isArray($val)){
				$tabcnt++;
				$rtn .= hash2XML($val,$tabcnt);
				}
			else{
				if($val=~/[\<\>]/){$rtn .= "\t"x$tabcnt . qq|<![CDATA[$val]]>\n|;}
				else{$rtn .= "\t"x$tabcnt . "$val\n";;}
				}
			$rtn .= qq|</$key>\n|;
		     }
		}
	return $rtn;
	}
###############
sub hexColor {
	#usage: my $hex=hexColor();
	#info: returns the random hex color
	my $hexcolor = "#";
	my $count = 3;
 	my $hexnum = 0;
 	my @hex = qw(FF CC 99 66 33 00);
	while ($count > 0) {
  		$hexnum = int( rand(6) );
		$hexcolor .= $hex[$hexnum];
  		$count--;
		}
	return $hexcolor;
	}
###############
sub htmlBarChart{
	#usage: $rtn .= htmlBarChart(\%hash,label=>"Warnings per day");
	#info: generates an html bar chart based on value in hash{$xvalue}{bar}=$val;
	#info: Additional xvalue options: link, text, title, color, textcolor
	#info: Additional params: font, title, titlesize,titlecolor,label, labelsize, height, barwidth, textsize, ticksize, sort
	#info: 	my %Report=();
	#info: 	$Report{12}{bar}=35;
	#info: 	$Report{12}{title}="Mary had a little lamb = 35";
	#info: 	$Report{13}{bar}=135;
	#info: 	$Report{14}{bar}=65;
	#info: 	$Report{14}{text}=65;
	#info: 	$Report{15}{bar}="50:50:20";
	#info: 	$Report{15}{text}="50:50:20";
	#info: 	$Report{15}{title}="Bob 50/120:Sam 50/120:Ed 20/120";
	#info: 	$Report{15}{link}=qq|http://www.google.com|;
	#info: 	return htmlBarChart(\%Report,label=>"Warnings per day",height=>300)
	my $hash=shift || return;
	#hash{xvalue}=yvalue;
	my %params=@_;
	$params{barwidth} ||= '20px';
	$params{labelsize} ||= '14px';
	$params{height} ||= 250;
	$params{textsize} ||= '9pt';
	$params{ticksize} ||= '8pt';
	$params{font} ||= 'arial';
	my $rtn='';
	my $blank = '&' . 'nbsp;';
	#determine max bar
	my $max=0;
	foreach my $key (keys(%{$hash})){
		my $value=0;
		if($hash->{$key}{bar}=~/\:/s){
			my @vals=split(/\:/,$hash->{$key}{bar});
			foreach my $val (@vals){$value += $val;}
	          }
		else{$value=$hash->{$key}{bar};}
		$max=$value>$max?$value:$max;
		}
	return "" if $max==0;
	my $guid=encodeCRC($hash . time() . $params{title} . $params{label});
	my $tdcount=2;
	my @xvalues=();
	if($params{sort}=~/^text/is){
	 	@xvalues=sortTextArray(keys(%{$hash}));
		}
	else{
		foreach my $key (keys(%{$hash})){
			my $ikey=int($key);
			push(@xvalues,$ikey);
	          }
	     @xvalues = sort {$a <=> $b} @xvalues;
	     #@xvalues=sort(@xvalues);
		}
	#Count up the columns - $tdcount
	foreach my $xvalue (@xvalues){$tdcount++;}
	#Draw the bar chart
	my $heightpx=$params{height} . "px";
	$rtn .= qq|<table cellspacing="0" cellpadding="0" border="0">\n|;
	#Title row
	if($params{title}){
		$params{titlesize} ||= "18pt";
		$params{titlecolor} ||= '#000';
		my $font=$params{titlefont} || $params{font};
		$rtn .= qq|<tr align="center"><td style="font-size:$params{titlesize};font-family:$font;text-transform: uppercase;color:$params{titlecolor};" colspan="$tdcount">$params{title}</td></tr>\n|;
		}
	$rtn .= qq|<tr valign="bottom" align="center" style="height:$heightpx;">\n|;
	#Tick column
	my $tickwidth=int(length($max)*6);
	$tickwidth .= "px";
	$rtn .= qq|    <td style="border-left:1px solid #000;width:$tickwidth;border-bottom:1px solid #000;" align="left">\n|;
	my $tick=$max;
	my $incriment=($tick/10);
	my $tickheight=int(($params{height}-10)/10) . "px";
	#while($tick>($incriment)){
	for(my $t=0;$t<10;$t++){
		my $ticktxt=sprintf("%.0f",$tick);
		$rtn .= qq|<!-- t=$t, incriment=$incriment, tick=$tick  -->\n|;
		$rtn .= qq|    <div style="padding-left:2px;font-size:$params{ticksize}; font-family:arial; color:#c0c0c0;width:30px;height:$tickheight;border:0px;border-top:1px solid #c0c0c0;">$ticktxt</div>\n|;
		$tick=$tick-$incriment;
		}
	$rtn .= qq|    </td>\n|;
	#Bar columnms
	foreach my $xvalue (@xvalues){
		my $color=$params{color} || $hash->{$xvalue}{color} || hexColor();
		my $xcolor=$params{xcolor} || $hash->{$xvalue}{xcolor} || "#c0c0c0";
		my $yvalue=$hash->{$xvalue}{bar};
		my $txt=$hash->{$xvalue}{text};
	     my $title= $hash->{$xvalue}{title};
		my $div='';
		my $height = 0;
		my $showval=0;
          if($yvalue=~/\:/s){
			#multiple values bar
			my @vals=split(/\:/,$yvalue);
			my @txts=split(/\:/,$txt);
			my @titles=split(/\:/,$title);
			my @colors=split(/\:/,$color);
			my $tval=0;
			foreach my $val (@vals){$tval += $val;}
			my $theight = abs((($tval * $params{height})/$max)-2);
			$showval=$theight;
			my $mcnt=@vals;
			$div .= qq|<div id="$guid\_bar\_$xvalue" yvalue="$yvalue" txt="$txt" title="$title" color="$color" style="width:$params{barwidth};padding:0px;border-left:1px solid #c0c0c0;border-right:1px solid #c0c0c0;border-top:1px solid #c0c0c0;">\n|;
			for(my $m=0;$m<$mcnt;$m++){
				$color=$colors[$m] || $params{color} || $hash->{$xvalue}{color} || hexColor();
				my $val=$vals[$m] || next;
				$txt=$txts[$m];
				$title=$titles[$m] || $txts[$m] . "$val/$tval";
				if(length($txt)){
					my $textcolor=$params{textcolor} || $hash->{$xvalue}{textcolor} || hexColor();
					my @chars=split(//,$txt);
					$txt=qq|<span style="color:$textcolor;">| . join('<br>',@chars) . "</span>";
			          }
                    $txt ||= qq|<span style="font-size:3px;">$blank</span>|;
			     if($tval){$height = int(abs($theight*$val/$tval)) || 1;}
			     else{next;}
				my $px=$height . "px";
				my $border='';
				if($m != 0){$border="border-top:1px solid #c0c0c0";}
				my $font=$params{textfont} || $params{font};
				$div .= qq|<div style="overflow:hidden;background-color:$color;height:$px;width:$params{barwidth};$border;padding:0px;font-size:$params{textsize};font-family:$font;" title="$title">$txt</div>\n|;
	               }
	          $div .= qq|</div>\n|;
	          }
		else{
			$title ||= $yvalue;
			if($txt){
				my $textcolor=$params{textcolor} || $hash->{$xvalue}{textcolor} || hexColor();
				my @chars=split(//,$txt);
				my $ccnt=@chars;
				if($ccnt>1){$txt=qq|<span style="color:$textcolor;">| . join('<br>',@chars) . "</span>";}
		          }
		     $txt ||= qq|<span style="font-size:3px;">$blank</span>|;
			$height = abs(int(($yvalue/$max) * $params{height}));
			my $px=$height . "px";
			$showval=$yvalue;
			my $border="border-left:1px solid #c0c0c0;border-right:1px solid #c0c0c0;border-top:1px solid #c0c0c0;";
			my $font=$params{textfont} || $params{font};
			$div .= qq|<div yvalue="$yvalue" pheight="$params{height}" max="$max" id="$guid\_bar\_$xvalue" style="overflow:hidden;background-color:$color;height:$px;width:$params{barwidth};$border;padding:0px;font-size:$params{textsize};font-family:$font;" title="$title">$txt</div>\n|;
			}

		my $link=$hash->{$xvalue}{link};
		if($params{showval} !=1){$showval='';}
		my $mouseover="document.getElementById('$guid\_bar\_$xvalue').style.borderColor='#000';document.getElementById('$guid\_$xvalue').style.color='#000';";
		my $mouseout="document.getElementById('$guid\_bar\_$xvalue').style.borderColor='#c0c0c0';document.getElementById('$guid\_$xvalue').style.color='$xcolor';";
		if(length($hash->{$xvalue}{info})){
			my $info=$hash->{$xvalue}{info};
			$info=~s/\'/\\\'/sg;
			$info=~s/\"/\\\"/sg;
			$info=~s/[\r\n]+/\ /sg;
			$mouseover .= "document.getElementById('info\_$guid').innerHTML='" . $info . "';";
			$mouseout .= "document.getElementById('info\_$guid').innerHTML='';";
	          }
		if(length($link)){
			$rtn .= qq|    <td style="border-bottom:1px solid #000;" onMouseOver="$mouseover" onMouseOut="$mouseout">\n$showval\n<a href="$link" target="_new" style="text-decoration:none;">\n$div\n</a>\n</td>\n|;
			}
		else{
			$rtn .= qq|    <td style="border-bottom:1px solid #000;" onMouseOver="$mouseover" onMouseOut="$mouseout">\n$showval\n$div\n</td>\n|;
			}
		}
	$rtn .= qq|    <td style="width:$params{barwidth};border-bottom:1px solid #000;">$blank</td>\n|;
	$rtn .= qq|</tr>\n|;
	#yvalue row
	$rtn .= qq|<tr valign="top" align="center">\n|;
	$rtn .= qq|	<td></td>\n|;
	foreach my $xvalue (@xvalues){
		my $xcolor=$params{xcolor} || $hash->{$xvalue}{xcolor} || "#c0c0c0";
		$rtn .= qq|    <td id="$guid\_$xvalue" style="font-size:$params{textsize};font-family:$params{font};text-transform: uppercase;color:$xcolor;">$xvalue</td>\n|;
		}
	$rtn .= qq|    <td></td>\n|;
	$rtn .= qq|</tr>\n|;
	#label
	if($params{label}){
		my $font=$params{labelfont} || $params{font};
		$rtn .= qq|<tr align="center"><td style="font-size:$params{labelsize};text-transform: uppercase;color:#000;" colspan="$tdcount">$params{label}</td></tr>\n|;
		}
	#Make a row for info
	$params{infosize} ||= "11pt";
	$params{infocolor} ||= "#000";
	$params{infoheight} ||= "20px";
	my $iwidth=int($params{barwidth}*$tdcount)+ int($tdcount*2);
	$iwidth .= "px";
	$rtn .= qq|<tr><td id="info\_$guid" style="width:$iwidth;height:$params{infoheight};font-size:$params{infosize};color:$params{infocolor};" colspan="$tdcount"></td></tr>\n|;
	$rtn .= qq|</table>\n|;
	return $rtn;
	}
###############
sub soundex{
	local (@s, $f, $fc, $_) = @_;
	push @s, '' unless @s;	# handle no args as a single empty string
  	foreach (@s){
    	$_ = uc $_;
    	tr/A-Z//cd;
    	if ($_ eq ''){$_ = $soundex_nocode;}
    	else{
	      	($f) = /^(.)/;
	      	tr/AEHIOUWYBFPVCGJKQSXZDTLMNR/00000000111122222222334556/;
	      	($fc) = /^(.)/;
	      	s/^$fc+//;
	      	tr///cs;
	      	tr/0//d;
	      	$_ = $f . $_ . '000';
	      	s/^(.{4}).*/$1/;
	    	}
	  	}
	wantarray ? @s : shift @s;
	}
#############
sub wiki2html{
	#usage: $html=wiki2html($str);
	my $str=shift || return;
	#http://wiki.beyondunreal.com/wiki/Wiki_Markup
	#----  <hr>
	#At the beginning, - <h1>, -- <h2>, --- <h3>, + padding-left:5px
	#Unorderd lists *, **
	#Ordered lists #, ##
	#tables [col1][col2]
	#''sdfs'' <i>, ''''sdfs'''' <b>, ''''''sddfs'''''' <b><i>
	#links [[ ]]
	#-> -task item - show checkbox and remember it.
	#
	#############################
	#Remove html tags from string
	my %Html=();
	my $htmlcnt=0;
	my $taskform=0;
	while($str=~m/\<(.+?)\>(.*?)\<\/\1\>/sig){
		my $tag=$&;
		my $placeholder=qq|<!--!! wiki $htmlcnt !!-->|;

		$Html{$htmlcnt}=$tag;
		$htmlcnt++;
		$str=~s/\Q$tag\E/$placeholder/is;
		}
	#process wiki markup
	my @lines=split(/\r\n/,$str);
	my $linecnt=@lines;
	my $dot='&#' . '9679' . ';';
	my $space='&' . 'nbsp' . ';';
	my %Marker=(
		ul => 0,
		ol => 0,
		table => 0,
		);
	for(my $x=0;$x<$linecnt;$x++){
		$lines[$x]=strip($lines[$x]);
		my $original=$lines[$x];
		my $break=1;
		#----  <hr>
		$lines[$x]=~s/\-{4,4}/\<hr size\=\"1\"\>/sig;
		#At the beginning, - <h1>, -- <h2>, --- <h3>
		if($lines[$x]=~/^(\-{1,3})(.+)/s){
			my $dashes=$1;
			my $txt=$2;
			my $count = $dashes =~ tr/\-//;
			$lines[$x]=qq|<h$count>$txt</h$count>|;
			$break=0;
		   	}
		#Task item at the beginning 0- = new, 0-/ = checked
		if($lines[$x]=~/^0\-{1,1}(\/*)(.+)/s){
			my $check=$1;
			my $txt=$2;
			$txt=~s/^\///sg;
			$taskform++;
			my $crc=encodeCRC($lines[$x]);
			$lines[$x]=qq|<div class="task"><input type="checkbox" name="$crc"|;
			if(length($check)){$lines[$x] .= qq| checked|;}
			$lines[$x] .= qq| DISABLED> $txt</div>|;
			$break=0;
		   	}
		if($lines[$x]=~/^(\++)/s){
			my $dashes=$1;
			my $count = $dashes =~ tr/\+//;
			my $pad=int($count*15);
			$pad .= "px";
			my $blank='&' . 'nbsp;';
			$lines[$x]=~s/\++//s;
			$lines[$x]=qq|<p style="text-indent:$pad">| . $lines[$x];
			$break=0;
		   	}
		#Unorderd lists *, **
		if($lines[$x]=~/^([\*\.\0]+)(.+)/s){
			my $stars=$1;
			my $txt=$2;
			my $count = $stars =~ tr/[\*\.\0]//;
			#$txt .= qq|<!-- ul: $Marker{ul} ,$count -->|;
			if($count > $Marker{ul}){
				$lines[$x]="<ul><li>" . $txt;
				$Marker{ul}=$count;
	                        }
			elsif($count == $Marker{ul}){
				$lines[$x]="<li>" . $txt;
	                        }
	                else{
				$lines[$x] = "</ul>";
				if(length($txt)){$lines[$x] .= "<li>" . $txt ;}
				$Marker{ul}=$count;
				}
			$break=0;
		   	}
		elsif($Marker{ul}){
			for(my $u=0;$u<$Marker{ul};$u++){
				$lines[$x] = "</ul>" . $lines[$x];
	                        }
	                $Marker{ul}=0;
	                }
	        #Orderd lists #, ##
		if($lines[$x]=~/^([\#]+)(.+)/s){
			my $stars=$1;
			my $txt=$2;
			my $count = $stars =~ tr/[\#]//;
			#$txt .= qq|<!-- ul: $Marker{ul} ,$count -->|;
			if($count > $Marker{ol}){
				$lines[$x]="<ol><li>" . $txt;
				$Marker{ol}=$count;
	                        }
			elsif($count == $Marker{ol}){
				$lines[$x]="<li>" . $txt;
	                        }
	                else{
				$lines[$x] = "</ol>";
				if(length($txt)){$lines[$x] .= "<li>" . $txt ;}
				$Marker{ol}=$count;
				}
			$break=0;
		   	}
		elsif($Marker{ol}){
			for(my $u=0;$u<$Marker{ol};$u++){
				$lines[$x] = "</ol>" . $lines[$x];
	                        }
	                $Marker{ol}=0;
	                }
		if(length($lines[$x])==0){$break=0;}
		#''sdfs'' <i>, ''''sdfs'''' <b>, ''''''sddfs'''''' <b><i>
                $lines[$x]=~s/\'{6,6}(.+?)\'{6,6}/<b><i>\1<\/i><\/b>/sig;
                $lines[$x]=~s/\'{4,4}(.+?)\'{4,4}/<b>\1<\/b>/sig;
                $lines[$x]=~s/\'{2,2}(.+?)\'{2,2}/<i>\1<\/i>/sig;

		#links << >>
		if($lines[$x]=~/\<\<(.+?)\>\>/s){
			my $ref=$1;
			$lines[$x]=~s/\<\<(.+?)\>\>/\<a href=\"\1\"\ target="_new">\1<\/a>/sg;
			}
		#tables [col1][col2]
		my @cols=();
	        while($lines[$x]=~/\[(.+?)\]/sg){
			push(@cols,$1);
	                }
	        my $colcnt=@cols;
		if($colcnt){
			$break=0;
			$lines[$x]='';
			if(!$Marker{table}){
				$lines[$x] .= qq|<table cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse">\n|;
				$Marker{table}=1;
				}
			$lines[$x] .= "<tr>";
			foreach my $col (@cols){$lines[$x] .= qq|<td>$col</td>|;}
                        $lines[$x] .= "</tr>";
			}
		elsif($Marker{table}){
			$lines[$x] = "</table>";
			$Marker{table}=0;
	                }
		#$lines[$x]=qq|<!--colcnt:$colcnt, table:$Marker{table}, Original: $original -->\n| . $lines[$x];
		if($break){$lines[$x] .= "<br>";}
		}
	if($Marker{ul}){
		for(my $u=0;$u<$Marker{ul};$u++){
			push(@lines,"</ul>");
	                }
		$Marker{ul}=0;
	        }
	if($Marker{ol}){
		for(my $u=0;$u<$Marker{ol};$u++){
			push(@lines,"</ol>");
	                }
		$Marker{ol}=0;
	        }
	if($Marker{table}){
		push(@lines,"</table>");
		$Marker{table}=0;
		}
	my $rstr = join("\r\n",@lines);
	#############################
	#Add html tags back in
	foreach my $x (keys(%Html)){
		my $tag=$Html{$x};
		$rstr=~s/<!--!! wiki $x !!-->/$tag/is;
		}
	return $rstr;
	}
################
sub xml2Hash {
	#usage: xml2Hash(\%hash,$xml);
	#http://builder.com.com/5100-6371-5363190-2.html#%3Cb%3EListing%20H%3C/b%3E
	my $hash=shift || return;
	my $xml=shift;
	my $nolc=shift || 0;
	if(!length($xml)){
		print "No xml";
		return;
		}
	#print "looping through xmlstr\n";
	study($xml);
	#Get the outside node
	if($xml=~m/\<(.+?)\ *(.*?)\>(.+)\<\/\1\>/is){
		my $node=strip($1);
		my $attributes=strip($2);
		my $nodevalue=$3;
		$hash->{$node}=1;
		print "Node: $node\n";
#		print "Attributes: $attributes\n";
		while($attributes=~m/([a-z\_\-]+?)([\=\s]*?)\"(.*?)\"/sig){
			$hash->{$node}{Attributes}{$1}=$3;
			print "hash->{$node}{Attributes}{$1}=$hash->{$node}{Attributes}{$1}\n";
	          }
	     xml2Hash($hash->{$node},$nodevalue);
		}
	}
################
sub xmlTransform {
	my $xsl=shift || return;
	my $xml=shift || return;
	#<xsl:for-each select="//entry"> ... </xsl:for-each>
	#<xsl:for-each select="file"></xsl:for-each>
	#<xsl:value-of select="name"/>
	#xsl:for-each
	while($xsl=~m/\<xsl\:for\-each select="(.+?)"\>(.+)\<\/xsl\:for\-each\>/sig){
		my $loopkey=$1;
		my $loopxsl=$2;
		my $looptag=$&;
		$loopkey=~s/^[\/]+//s;
		while($xml=~m/\<$loopkey\>(.+?)\<\/$loopkey\>/sig){
			my $loopxml=$1;
			
		     }
		my $xval=join('',@xvals);
		$xsl=~s/\Q$xtag\E/$xval/is;
		}
	return $xsl;
	}
return 1;