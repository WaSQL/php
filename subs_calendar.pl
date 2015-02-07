use Date::Calc qw(Calendar);
###################
sub calendarArray{
	my $year=shift || getDate("YYYY");
	my $month=shift || getDate("NM");
	my $calstring=Calendar($year,$month,1);
	$calstring=strip($calstring);
	my @lines=split(/[\r\n]+/,$calstring);
# 	      September 2008
# 	Sun Mon Tue Wed Thu Fri Sat
# 	      1   2   3   4   5   6
# 	  7   8   9  10  11  12  13
# 	 14  15  16  17  18  19  20
# 	 21  22  23  24  25  26  27
# 	 28  29  30

	my @rows=();
	#Month & year
	push(@rows,,shift(@lines));
	#days of the week rows
	my (@days)=split(/[\s]+/,shift(@lines));
	push(@rows,[@days]);
	my $firstrow=1;
	foreach my $row (@lines){
		$row=strip($row);
		my @dates=split(/\s+/,$row);
		if($firstrow){
			#The first row may not start on Sunday so prefill it with blanks
			while(scalar @dates < 7){unshift(@dates,'');}
			$firstrow=0;
			}
		else{
			while(scalar @dates < 7){push(@dates,'');}
        	}
        push(@rows,[@dates]);
		}
	return @rows;
	}
return 1;